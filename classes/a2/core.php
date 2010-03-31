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


		if ( ! isset($_instances[$_name]) AND $load_from_db === TRUE)
		{
			$config = Kohana::config('a2');
			$class_name = 'A2_Driver_'.$config['orm_driver'];
			$_instances[$_name] = new $class_name($_name, $load_from_db);
		}
		else
		{
			$_instances[$_name] = new A2_Driver_Config($_name, $load_from_db);
		}

		return $_instances[$_name];
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
	 */
	public function logged_in()
	{
		return $this->a1->logged_in();
	}

	/**
	 * Alias of the get_user method
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
} // End A2_Core