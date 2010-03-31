<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A2 Driver Interface
 *
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 */
interface A2_Driver_Interface {

	/**
	 * Loads all roles
	 *
	 * @return object
	 */
	public function _load_roles();

	/**
	 * Loads all resources
	 *
	 * @return object
	 */
	public function _load_resources();

	/**
	 * Loads all rules
	 *
	 * @return object
	 */
	public function _load_rules();

	/**
	 * Sets new role to database
	 *
	 * @param string   $role_name
	 * @return integer $role->id
	 */
	public function _set_role($role_name);

	/**
	 * Sets role parent id
	 *
	 * @param integer $role_id
	 * @param integer $parent_id
	 */
	public function _set_role_parent($role_id, $parent_id);

	/**
	 * Sets new resource to database
	 *
	 * @param string   $resource_name
	 * @return integer $resource->id
	 */
	public function _set_resource($resource_name);

	/**
	 * Sets resource parent id
	 *
	 * @param integer $resource_id
	 * @param integer $parent_id
	 */
	public function _set_resource_parent($resource_id, $parent_id);

	/**
	 * Sets new privilege to database
	 *
	 * @param string   $privilege_name
	 * @return integer $privilege->id
	 */
	public function _set_privilege($privilege_name);

	/**
	 * Initiates new rule object
	 *
	 * @param integer $type_id
	 * @param string  $name
	 * @param integer $resource_id
	 * @return object
	 */
	public function _init_rule($type_id, $name, $resource_id);

	/**
	 * Sets new rule to database
	 *
	 * @param object   $rule
	 * @return integer $rule->id
	 */
	public function _set_rule($rule);

	/**
	 * Sets new rule to role
	 *
	 * @param integer $role_id
	 * @param integer $rule
	 */
	public function _set_role_rule($role_id, $rule_id);

	/**
	 * Sets new assertion to database
	 *
	 * @param integer $rule_id
	 * @param integer $resource_id
	 * @param array   $assetrion
	 */
	public function _set_assertion($rule_id, $resource_id, $assetrion);
	
} // End A2_Driver_Interface
