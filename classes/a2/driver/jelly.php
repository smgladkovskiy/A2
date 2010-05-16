<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A2 Jelly Driver
 *
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 */
class A2_Driver_Jelly extends A2 implements A2_Driver_Interface {

	/**
	 * Loads all roles
	 *
	 * @return object
	 */
	public function _load_roles()
	{
		return Jelly::select('role')->execute();
	}

	/**
	 * Loads all resources
	 *
	 * @return object
	 */
	public function _load_resources()
	{
		return Jelly::select('resource')->execute();
	}

	/**
	 * Loads all rules
	 *
	 * @return object
	 */
	public function _load_rules()
	{
		return Jelly::select('rule')->execute();
	}

	/**
	 * Sets new role to database
	 *
	 * @param  string  $role_name
	 * @return integer $role->id
	 */
	public function _set_role($role_name)
	{
		$role = Jelly::factory('role')
			->set(array(
				'name' => $role_name
			))
			->save();

		return $role->id;
	}

	/**
	 * Loads resource name from role object
	 * 
	 * @param  object $rule
	 * @return string
	 */
	public function _load_resource_name($rule)
	{
		return $rule->resource->name;
	}

	/**
	 * Loads assertion frome rule object
	 *
	 * @param  object $rule
	 * @return object
	 */
	public function _load_assertion($rule)
	{
		return $rule->assertion;
	}

	/**
	 * Sets role parent id
	 *
	 * @param integer $role_id
	 * @param integer $parent_id
	 */
	public function _set_role_parent($role_id, $parent_id)
	{
		$role = Jelly::factory('role');
		$role->parent = (int) $parent_id;
		$role->save($role_id);
	}

	/**
	 * Sets new resource to database
	 *
	 * @param  string  $resource_name
	 * @return integer $resource->id
	 */
	public function _set_resource($resource_name)
	{
		$resource = Jelly::factory('resource')
			->set(array(
				'name' => $resource_name
			))
			->save();

		return $resource->id;
	}

	/**
	 * Sets resource parent id
	 *
	 * @param integer $resource_id
	 * @param integer $parent_id
	 */
	public function _set_resource_parent($resource_id, $parent_id)
	{
		$resource = Jelly::factory('resource');
		$resource->parent = (int) $parent_id;
		$resource->save((int) $resource_id);
	}

	/**
	 * Sets new privilege to database
	 *
	 * @param  string  $privilege_name
	 * @return integer $privilege->id
	 */
	public function _set_privilege($privilege_name)
	{
		$privilege = Jelly::factory('privilege')
			->set(array(
				'name' => $privilege_name
			))
			->save();

		return $privilege->id;
	}

	/**
	 * Initiates new rule object
	 *
	 * @param  integer $type_id
	 * @param  string  $name
	 * @param  integer $resource_id
	 * @return object
	 */
	public function _init_rule($type_id, $name, $resource_id)
	{
		$rule = Jelly::factory('rule')
			->set(array(
				'type' => $type_id,
				'name' => $name,
				'resource' => $resource_id
			));

		return $rule;
	}

	/**
	 * Sets new rule to database
	 *
	 * @param  object  $rule
	 * @return integer $rule->id
	 */
	public function _set_rule($rule)
	{
		try
		{
			$rule->save();
		}
		catch( Validate_Exception $e)
		{
			exit(Kohana::debug($e->array->errors('a2')));
		}

		return $rule->id;
	}

	/**
	 * Sets new rule to role
	 * 
	 * @param integer $role_id
	 * @param integer $rule
	 */
	public function _set_role_rule($role_id, $rule_id)
	{
		$role = Jelly::factory('role');
		$role->rules = array($rule_id);
		$role->save((int) $role_id);
	}

	/**
	 * Sets new assertion to database
	 *
	 * @param integer $rule_id
	 * @param integer $resource_id
	 * @param array   $assetrion
	 */
	public function _set_assertion($rule_id, $resource_id, $assetrion)
	{
		$assertion = Jelly::factory('assertion')
			->set(array(
				'rule' => $rule_id,
				'resource' => $resource_id
			));
		foreach($assetrion as $user_field => $resource_field)
		{
			$assertion->set(array(
				'user_field' => $user_field,
				'resource_field' => $resource_field
				));
			break;
		}
		$assertion->save();
	}
} // End A2_Driver_Jelly