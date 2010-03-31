<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A2 Sprig Driver
 *
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 *
 * @todo implements ACL data caching
 */
class A2_Driver_Sprig extends A2 {

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
			// load roles
			$roles = Sprig::factory('role')->load(NULL, NULL);

			// first module run
			if ( ! count($roles))
			{
				// scan&rec roles
				$roles_data = array();
				foreach($config['roles'] as $role => $parent)
				{
					$role_object = Sprig::factory('role');
					$role_object->name = $role;
					$role_object->create();

					$roles_data[$role] = $role_object->id;
				}

				// set roles parents
				foreach($config['roles'] as $role => $parent)
				{
					if ( ! empty($roles_data[$parent]))
					{
						$role_object = Sprig::factory('role', array(
							'id' => $roles_data[$role]
						));
						$role_object->load();

						$role_object->id_parent = $roles_data[$parent];
						$role_object->update();
					}
				}


				// scan&rec resoursec
				$resources_data = array();
				foreach($config['resources'] as $resource => $parent)
				{
					$resource_object = Sprig::factory('resource');
					$resource_object->name = $resource;
					$resource_object->create();

					$resources_data[$resource] = $resource_object->id;
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
						$resource_object = Sprig::factory('resource', array(
							'id' => $resources_data[$resource]
						));
						$resource_object->load();

						$resource_object->parent_id = $id_parent;
						$resource_object->update();
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
								$privilege_object = Sprig::factory('privilege');
								$privilege_object->name = $privilege;
								$privilege_object->create();

								$privileges_data[$privilege] = $privilege_object->id;
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
							$rules_object = Sprig::factory('rule');
							$rules_object->type = $type;
							$rules_object->name = $rule;
							$rules_object->resource = $resources_data[$data['resource']];

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
							$rules_object->create();

							$rules_data[$rule] = $rules_object->id;

							if ( ! empty($roles_data[$data['role']]))
							{
								$roles_object = Sprig::factory('role', array(
									'id' => $roles_data[$data['role']]
								));
								$roles_object->load();

								$roles_object->rules = array($rules_data[$rule]);
								$roles_object->update();
							}

							if ( ! empty($data['assertion']))
							{
								$assertion_object = Sprig::factory('assertion');
								$assertion_object->rule = $rules_data[$rule];
								$assertion_object->resource = $resources_data[$data['resource']];
								foreach($data['assertion'][1] as $user_field => $resource_field)
								{
									$assertion_object->user_field = $user_field;
									$assertion_object->resource_field = $resource_field;
									break;
								}
								$assertion_object->create();
							}
						}
					}
				}
				$roles = Sprig::factory('role')->load(NULL, FALSE);
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
			$resources = Sprig::factory('resource')->load(NULL, FALSE);

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
			$rules = Sprig::factory('rule')->load(NULL, FALSE);
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
	
} // End A2_Driver_Sprig