<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Authorization - provides Authentication (using A1) and ACL through a simple to use
 * interface to the developer.
 *
 * @package A2
 * @author Wouter
 * @author devolonter <devolonter@enerdesign.ru>
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 */
abstract class A2_Core extends Acl {

	public    $a1;                // the Authentication library (used to retrieve user)
	protected $_guest_role;       // name of the guest role (used when no user is logged in)
	protected $_config;           // Config storage
	protected $_common_resource;  // common resources array
	protected static $_instances; // Singleton Instances

	/**
	 * Return an instance of A2 class
	 *
	 * @staticvar array $_instances
	 * @param string  $name
	 * @param boolean $load_from_db
	 * @return object
	 */
	public static function instance($name = 'a2', $load_from_db = TRUE)
	{
		if ( ! isset(self::$_instances[$name]) AND ($load_from_db === TRUE))
		{
			$a2_config = Kohana::config($name);
			$a1_config = Kohana::config($a2_config['lib']['class']);
			$class_name = 'A2_Driver_'.$a1_config['driver'];
			self::$_instances[$name] = new $class_name($name, TRUE);
		}
		else
		{
			self::$_instances[$name] = new A2($name, FALSE);
		}

		return self::$_instances[$name];
	}

	/**
	 * Build A2 from config and database with the help of Sprig orm library
	 *
	 * @param string  $name
	 * @param boolean $load_from_db
	 * @return void
	 */
	public function __construct($name = 'a2', $load_from_db = TRUE)
	{
		// Reading config
		$this->_config = Kohana::config($name);

		// Setting config name
		$this->_config['name'] = $name;

		$this->_common_resource = ! empty($this->_config['common_resource']) ? $this->_config['common_resource'] : NULL;

		if ($load_from_db === TRUE)
		{
			$this->_init_data();
		}

		// Create instance of Authenticate lib (a1, auth, authlite)
		$instance = new ReflectionMethod($this->_config->lib['class'],'instance');

		$params = !empty($this->_config->lib['params'])
			? $this->_config->lib['params']
			: array();

		$this->a1 = $instance->invokeArgs(NULL, $params);

		// Throw exceptions?
		$this->_exception = $this->_config->exception;

		// Guest role
		$this->_guest_role = $this->_config['guest_role'];

		// Add Guest Role as role
		if ( ! array_key_exists($this->_guest_role,$this->_config['roles']))
		{
			$this->add_role($this->_guest_role);
		}

		// Load ACL data
		$this->load();
	}

	/**
	 * Load ACL data (roles/resources/rules)
	 *
	 * This allows you to add context specific rules
	 * roles and resources.
	 *
	 * @param  array|Kohana_Config  configiration data
	 */
	public function load()
	{
		// Roles
		if ( isset($this->_config['roles']))
		{
			foreach ( $this->_config['roles'] as $role => $parent)
			{
				$this->add_role($role,$parent);
			}
		}

		// Resources
		if ( isset($this->_config['resources']))
		{
			foreach($this->_config['resources'] as $resource => $parent)
			{
				$this->add_resource($resource,$parent);
			}
		}

		// Rules
		if(isset($this->_config['rules']))
		{
			foreach(array('allow','deny') as $method)
			{
				if ( isset($this->_config['rules'][$method]))
				{
					foreach ( $this->_config['rules'][$method] as $rule)
					{
						// create variables
						$role = $resource = $privilege = $assertion = NULL;

						// extract variables from rule
						extract($rule);

						// create assert object
						if ( $assertion )
						{
							if ( is_array($assertion))
							{
								$assertion = count($assertion) === 2
									? new $assertion[0]($assertion[1])
									: new $assertion[0];
							}
							else
							{
								$assertion = new $assertion;
							}
						}

						// this is faster than calling $this->$method
						if ( $method === 'allow')
						{
							$this->allow($role,$resource,$privilege,$assertion);
						}
						else
						{
							$this->deny($role,$resource,$privilege,$assertion);
						}
					}
				}
			}
		}
	}

	/**
	 * Check if logged in user (or guest) has access to resource/privilege.
	 *
	 * @param  mixed        Resource
	 * @param  string       Privilege
	 * @param  boolean      Override exception handling set by config
	 * @return boolean      Is user allowed
	 * @throws A2_Exception In exception modus, when user is not allowed
	 */
	public function allowed($resource = NULL, $privilege = NULL, $exception = NULL)
	{
		if ( ! is_bool($exception))
		{
			// take config value
			$exception = $this->_exception;
		}

		// retrieve user
		$role = ($user = $this->a1->get_user()) ? $user : $this->_guest_role;

		$result = $this->is_allowed($role,$resource,$privilege);

		if ( ! $exception || $result === TRUE )
		{
			return $result;
		}
		else
		{
			$error = $resource !== NULL
				? $resource instanceof Acl_Resource_Interface ? $resource->get_resource_id() : (string) $resource
				: 'resource';

			$error .= '.' . ($privilege !== NULL
				? $privilege
				: 'default');

			if( ! $message = Kohana::message('a2', $error))
			{
				// specific message not found - use default
				$message = Kohana::message('a2', 'default');
			}

			throw new A2_Exception($message);
		}
	}

	/**
	 * Alias of the logged_in method
	 *
	 * @return boolean
	 */
	public function logged_in()
	{
		return $this->a1->logged_in();
	}

	/**
	 * Alias of the get_user method
	 *
	 * @return object
	 */
	public function get_user()
	{
		return $this->a1->get_user();
	}

	/**
	 * Get common resources
	 *
	 * @return array
	 */
	public function get_common_resource()
	{
		return $this->_common_resource;
	}

	/**
	 * Get system resources that are setted in config
	 *
	 * @return array
	 */
	public function get_system_resources()
	{
		$config = Kohana::$config->load($this->_config['name']);
		return $config['resources'];
	}

	/**
	 * Get system roles that are setted in config
	 *
	 * @return array
	 */
	public function get_system_roles()
	{
		$config = Kohana::$config->load($this->_config['name']);
		return $config['roles'];
	}

	/**
	 * Get system rules that are setted in config
	 *
	 * @return array
	 */
	public function get_system_rules()
	{
		$config = Kohana::$config->load($this->_config['name']);
		return $config['rules'];
	}

	/**
	 * Initiates data from database ACL and sets it in config
	 *
	 * @return void
	 */
	protected function _init_data()
	{
		// load roles
		$roles = $this->_load_roles();

		// first module run
		if ( ! count($roles))
		{
			// scan&rec roles
			$roles_data = array();
			foreach($this->_config['roles'] as $role => $parent)
			{
				$roles_data[$role] = $this->_set_role($role);
			}

			// set roles parents
			foreach($this->_config['roles'] as $role => $parent)
			{
				if ( ! empty($roles_data[$parent]))
				{
					$this->_set_role_parent($roles_data[$role], $roles_data[$parent]);
				}
			}


			// scan&rec resoursec
			$resources_data = array();
			foreach($this->_config['resources'] as $resource => $parent)
			{
				$resources_data[$resource] = $this->_set_resource($resource);
			}

			// set resources parents
			foreach($this->_config['resources'] as $resource => $parent)
			{
				if ( ! empty($this->_config['common_resource']) AND $this->_config['common_resource'] == $resource)
					continue;

				$id_parent = NULL;

				if ($parent == NULL)
				{
					if ($this->_config['common_resource'] AND
						! empty($resources_data[$this->_config['common_resource']]))
					{
						$id_parent = $resources_data[$this->_config['common_resource']];
					}
				}
				else
				{
					if ( ! empty($resources_data[$parent]))
					{
						$id_parent = $resources_data[$parent];
					}
				}

				if ($id_parent != NULL)
				{
					$this->_set_resource_parent($resources_data[$resource], $id_parent);
				}
			}

			// get all possible privileges
			$privileges_data = array();
			foreach($this->_config['rules'] as $type)
			{
				foreach($type as $rule)
				{
					if ( ! is_array($rule['privilege']))
					{
						$rule['privilege'] = (array) $rule['privilege'];
					}

					foreach($rule['privilege'] as $privilege)
					{
						if ( ! isset($privileges_data[$privilege]))
						{
							$privileges_data[$privilege] = $this->_set_privilege($privilege);
						}
					}
				}
			}

			// save rules
			$rules_data = array();
			foreach($this->_config['rules'] as $type => $rules)
			{
				foreach($rules as $rule => $data)
				{
					if (count($data) AND ! empty($resources_data[$data['resource']]))
					{
						$rules_object = $this->_init_rule($type, $rule, $resources_data[$data['resource']]);

						if ( ! is_array($data['privilege']))
						{
							$data['privilege'] = (array) $data['privilege'];
						}

						$privileges_array = array();
						foreach($data['privilege'] as $privilege)
						{
							if ( ! empty($privileges_data[$privilege]))
							{
								$privileges_array[] = $privileges_data[$privilege];
							}
						}

						$rules_object->privileges = $privileges_array;

						$rules_data[$rule] = $this->_set_rule($rules_object);

						if ( ! empty($roles_data[$data['role']]))
						{
							$this->_set_role_rule($roles_data[$data['role']], $rules_data[$rule]);
						}

						if ( ! empty($data['assertion']))
						{
							$this->_set_assertion(
								$rules_data[$rule],
								$resources_data[$data['resource']],
								$data['assertion'][1]);
						}
					}
				}
			}
			$roles = $this->_load_roles();
		}

		$all_roles = array();
		foreach($roles as $role)
		{
			$all_roles[$role->id] = $role->name;
		}

		// clear config data
		$this->_config['roles'] = array();

		// set roles
		foreach($roles as $role)
		{
			$this->_config['roles'][$role->name] = (isset($all_roles[$role->parent_id])) ?
											$all_roles[$role->parent_id] :
											NULL;
		}

		// cache resources for fast get parents
		$resources = $this->_load_resources();

		$all_resources = array();
		foreach($resources as $resource)
		{
			$all_resources[$resource->id] = $resource->name;
		}

		// clear config data
		$this->_config['resources'] = array();

		// set resources
		foreach($resources as $resource)
		{
			$this->_config['resources'][$resource->name] = (isset($all_resources[$resource->parent->id])) ?
													 $all_resources[$resource->parent->id] :
													 NULL;
		}

		// clear config data
		$this->_config['rules'] = array();

		// set rules
		$rules = $this->_load_rules();

		foreach($rules as $rule)
		{
			$this->_config['rules'][$rule->type][$rule->name]['resource'] = $this->_load_resource_name($rule);

			$roles = array();
			foreach($rule->roles as $role)
			{
				$roles[] = $role->name;
			}
			$this->_config['rules'][$rule->type][$rule->name]['role'] = $roles;

			$privileges = array();
			foreach($rule->privileges as $privilege)
			{
				$privileges[] = $privilege->name;
			}
			$this->_config['rules'][$rule->type][$rule->name]['privilege'] = $privileges;

			$assertion = $this->_load_assertion($rule);
			if ( ! empty($assertion->id))
			{
				$this->_config['rules'][$rule->type][$rule->name]['assertion'] = array(
					'Acl_Assert_Argument',
					array($assertion->user_field => $assertion->resource_field)
				);
			}
		}
	}
} // End A2_Core