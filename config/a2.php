<?php defined('SYSPATH') OR die('No direct access allowed.');

return array(

	/*
	 * The Authentication library to use
	 * Make sure that the library supports:
	 * 1) A get_user method that returns FALSE when no user is logged in
	 *	  and a user object that implements Acl_Role_Interface when a user is logged in
	 * 2) A static instance method to instantiate a Authentication object
	 *
	 * array(CLASS_NAME,array $arguments)
	 */
	'lib' => array(
		'class'  => 'A1', // (or AUTH)
		'params' => array('a1')
	),

	/**
	 * Throws an a2_exception when authentication fails
	 */
	'exception' => FALSE,

	/*
	 * The ACL Roles (String IDs are fine, use of ACL_Role_Interface objects also possible)
	 * Use: ROLE => PARENT(S) (make sure parent is defined as role itself before you use it as a parent)
	 */
	'roles' => array
	(
		// ADD YOUR OWN ROLES HERE
		'guest' => NULL,
		'root'  => NULL
	),

	/*
	 * The name of the guest role 
	 * Used when no user is logged in.
	 */
	'guest_role' => 'guest',

	/*
	 * The ACL Resources (String IDs are fine, use of ACL_Resource_Interface objects also possible)
	 * Use: ROLE => PARENT (make sure parent is defined as resource itself before you use it as a parent)
	 */
	'resources' => array
	(
		'common'    => NULL,
		'rule'      => NULL,
		'resource'  => NULL,
		'role'      => NULL,
		'user'      => NULL,
		'assertion' => NULL,
		// ADD YOUR OWN RESOURCES HERE
	),

	/**
	 * Common parent resource
	 */
	'common_resource' => 'common',

	/*
	 * The ACL Rules (Again, string IDs are fine, use of ACL_Role/Resource_Interface objects also possible)
	 * Split in allow rules and deny rules, one sub-array per rule:
	     array( ROLES, RESOURCES, PRIVILEGES, ASSERTION)
	 *
	 * Assertions are defined as follows :
			array(CLASS_NAME,$argument) // (only assertion objects that support (at most) 1 argument are supported
			                            //  if you need to give your assertion object several arguments, use an array)
	 */
	'rules' => array
	(
		'allow' => array
		(
			/*
			 * ADD YOUR OWN ALLOW RULES HERE 
			 *
			'ruleName1' => array(
				'role'      => 'guest',
				'resource'  => 'blog',
				'privilege' => 'read'
			),
			'ruleName2' => array(
				'role'      => 'admin'
			),
			'ruleName3' => array(
				'role'      => array('user','manager'),
				'resource'  => 'blog',
				'privilege' => array('delete','edit')
			)
			 */
			'system_root_rule' => array(
				'role' => 'root',
				'resource' => 'common',
				'privilege' => array('create', 'read', 'update', 'delete')
			),
			'system_guest_rule' => array(
				'role' => 'guest',
				'resource' => 'common',
				'privilege' => array('read')
			),
		),
		'deny' => array
		(
			// ADD YOUR OWN DENY RULES HERE
		)
	)
);