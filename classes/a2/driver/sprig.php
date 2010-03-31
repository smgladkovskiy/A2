<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A2 Sprig Driver
 *
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 *
 * @todo implements ACL data caching
 */
class A2_Driver_Sprig extends A2 implements A2_Driver_Interface {

	/**
	 * Loads all roles
	 *
	 * @return object
	 */
	public function _load_roles()
	{
		return Sprig::factory('role')->load(NULL, FALSE);
	}

	/**
	 * Loads all resources
	 *
	 * @return object
	 */
	public function _load_resources()
	{
		return Sprig::factory('resource')->load(NULL, FALSE);
	}

	/**
	 * Loads all rules
	 *
	 * @return object
	 */
	public function _load_rules()
	{
		return Sprig::factory('rule')->load(NULL, FALSE);
	}

	/**
	 * Sets new role to database
	 *
	 * @param string   $role_name
	 * @return integer $role->id
	 */
	public function _set_role($role_name)
	{
		$role = Sprig::factory('role');
		$role->name = $role_name;
		$role->create();

		return $role->id;
	}

	/**
	 * Sets role parent id
	 *
	 * @param integer $role_id
	 * @param integer $parent_id
	 */
	public function _set_role_parent($role_id, $parent_id)
	{
		$role = Sprig::factory('role', array(
			'id' => (int) $role_id
		));
		$role->load();

		$role->id_parent = (int) $parent_id;
		$role->update();
	}

	/**
	 * Sets new resource to database
	 *
	 * @param string   $resource_name
	 * @return integer $resource->id
	 */
	public function _set_resource($resource_name)
	{
		$resource = Sprig::factory('resource');
		$resource->name = $resource_name;
		$resource->create();

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
		$resource = Sprig::factory('resource', array(
			'id' => (int) $resource_id
		));
		$resource->load();

		$resource->parent_id = (int) $parent_id;
		$resource->update();
	}

	/**
	 * Sets new privilege to database
	 *
	 * @param string   $privilege_name
	 * @return integer $privilege->id
	 */
	public function _set_privilege($privilege_name)
	{
		$privilege = Sprig::factory('privilege');
		$privilege->name = $privilege_name;
		$privilege->create();

		return $privilege->id;
	}

	/**
	 * Initiates new rule object
	 *
	 * @param integer $type_id
	 * @param string  $name
	 * @param integer $resource_id
	 * @return object
	 */
	public function _init_rule($type_id, $name, $resource_id)
	{
		$rule = Sprig::factory('rule');
		$rule->type = $type_id;
		$rule->name = $name;
		$rule->resource = $resource_id;

		return $rule;
	}

	/**
	 * Sets new rule to database
	 *
	 * @param object   $rule
	 * @return integer $rule->id
	 */
	public function _set_rule($rule)
	{
		$rule->create();

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
		$role = Sprig::factory('role', array(
			'id' => (int) $role_id
		));
		$role->load();

		$role->rules = array($rule_id);
		$role->update();
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
		$assertion = Sprig::factory('assertion');
		$assertion->rule = $rule_id;
		$assertion->resource = $resource_id;
		foreach($assetrion as $user_field => $resource_field)
		{
			$assertion->user_field = $user_field;
			$assertion->resource_field = $resource_field;
			break;
		}
		$assertion->create();
	}
} // End A2_Driver_Sprig