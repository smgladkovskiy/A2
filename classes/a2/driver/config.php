<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A2 Config Driver to load data from config.php
 *
 * @author smgladkovskiy <smgladkovskiy@gmail.com>
 */
class A2_Driver_Config extends A2 {

	/**
	 * Build default A2 from config and database
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
	
} // End A2_Driver_Config