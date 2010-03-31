<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Authorization - provides Authentication (using A1) and ACL through a simple to use
 * interface to the developer.
 * 
 * @package A2
 * @author Wouter
 * @author devolonter <devolonter@enerdesign.ru>
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 *
 * @todo create ORM drivers to make it possible to work with different ORM libraries. Sprig is now
 * the default one.
 */
abstract class A2_Core extends Acl {

	public    $a1;               // the Authentication library (used to retrieve user)
	protected $_guest_role;      // name of the guest role (used when no user is logged in)
	protected $_common_resource; // common resources array

	/**
	 * Return an instance of A2 class
	 *
	 * @staticvar array $_instances
	 * @param string $_name
	 * @param boolean $load_from_db
	 * @return object
	 */
	public static function instance($_name = 'a2', $load_from_db = TRUE)
	{
		static $_instances;

		if ( ! isset($_instances[$_name]) AND ($load_from_db === TRUE))
		{
			$config = Kohana::config('a2');
			$class_name = 'A2_Driver_'.$config['orm_driver'];
			$_instances[$_name] = new $class_name($_name, TRUE);
		}
		else
		{
			$_instances[$_name] = new A2($_name, FALSE);
		}

		return $_instances[$_name];
	}
	
	/**
	 * Build A2 from config and database with the help of Sprig orm library
	 *
	 * @param string $_name
	 * @param boolean $load_from_db
	 * @return void
	 */
	public function __construct($_name = 'a2', $load_from_db = TRUE)
	{
		// Read config
		$config = Kohana::config($_name);

		$this->_common_resource = ! empty($config['common_resource']) ? $config['common_resource'] : NULL;

		if ($load_from_db === TRUE)
		{
			$this->_init_data();
		}

		// Create instance of Authenticate lib (a1, auth, authlite)
		$instance = new ReflectionMethod($config->lib['class'],'instance');

		$params = !empty($config->lib['params'])
			? $config->lib['params']
			: array();

		$this->a1 = $instance->invokeArgs(NULL, $params);

		// Throw exceptions?
		$this->_exception = $config->exception;

		// Guest role
		$this->_guest_role = $config['guest_role'];

		// Add Guest Role as role
		if ( ! array_key_exists($this->_guest_role,$config['roles']))
		{
			$this->add_role($this->_guest_role);
		}

		// Load ACL data
		$this->load($config);
	}

	/**
	 * Load ACL data (roles/resources/rules)
	 *
	 * This allows you to add context specific rules
	 * roles and resources.
	 *
	 * @param  array|Kohana_Config  configiration data
	 */
	public function load($config)
	{
		// Roles
		if ( isset($config['roles']))
		{
			foreach ( $config['roles'] as $role => $parent)
			{
				$this->add_role($role,$parent);
			}
		}

		// Resources
		if ( isset($config['resources']))
		{
			foreach($config['resources'] as $resource => $parent)
			{
				$this->add_resource($resource,$parent);
			}
		}

		// Rules
		if(isset($config['rules']))
		{
			foreach(array('allow','deny') as $method)
			{
				if ( isset($config['rules'][$method]))
				{
					foreach ( $config['rules'][$method] as $rule)
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
	 * @param   mixed     Resource
	 * @param   string    Privilege
	 * @param   boolean   Override exception handling set by config
	 * @return  boolean   Is user allowed
	 * @throws  A2_Exception   In exception modus, when user is not allowed
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
	 * Get resources
	 *
	 * @return array
	 */
	public function get_resources()
	{
		return $this->_resources;
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
			foreach($config['roles'] as $role => $parent)
			{
				$roles_data[$role] = $this->_set_role($role);
			}

			// set roles parents
			foreach($config['roles'] as $role => $parent)
			{
				if ( ! empty($roles_data[$parent]))
				{
					$this->_set_role_parent($roles_data[$role], $roles_data[$parent]);
				}
			}


			// scan&rec resoursec
			$resources_data = array();
			foreach($config['resources'] as $resource => $parent)
			{
				$resources_data[$resource] = $this->_set_resource($resource);
			}

			// set resources parents
			foreach($config['resources'] as $resource => $parent)
			{
				if ( ! empty($config['common_resource']) AND $config['common_resource'] == $resource)
					continue;

				$id_parent = NULL;

				if ($parent == NULL)
				{
					if ($config['common_resource'] AND
						! empty($resources_data[$config['common_resource']]))
					{
						$id_parent = $resources_data[$config['common_resource']];
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
			foreach($config['rules'] as $type)
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
			foreach($config['rules'] as $type => $rules)
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
		$config['roles'] = array();

		// set roles
		foreach($roles as $role)
		{
			$config['roles'][$role->name] = (isset($all_roles[$role->parent_id])) ?
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
		$config['resources'] = array();

		// set resources
		foreach($resources as $resource)
		{
			$config['resources'][$resource->name] = (isset($all_resources[$resource->parent_id])) ?
													 $all_resources[$resource->parent_id] :
													 NULL;
		}

		// clear config data
		$config['rules'] = array();

		// set rules
		$rules = $this->_load_rules();

		foreach($rules as $rule)
		{
			$config['rules'][$rule->type][$rule->name]['resource'] = $rule->resource->load()->name;

			$roles = array();
			foreach($rule->roles as $role)
			{
				$roles[] = $role->name;
			}
			$config['rules'][$rule->type][$rule->name]['role'] = $roles;

			$privileges = array();
			foreach($rule->privileges as $privilege)
			{
				$privileges[] = $privilege->name;
			}
			$config['rules'][$rule->type][$rule->name]['privilege'] = $privileges;

			$assertion = $rule->assertion->load();
			if ( ! empty($assertion->id))
			{
				$config['rules'][$rule->type][$rule->name]['assertion'] = array(
					'Acl_Assert_Argument',
					array($assertion->user_field => $assertion->resource_field)
				);
			}
		}
	}
} // End A2_Core