<?php defined('SYSPATH') or die('No direct script access.');

return array(
	// default message
	'default'       => 'You are not authorized to perform this action',

	// add your own in the 'resource.privilege' format
	// if the resource is not specified (->deny($user,NULL,'delete'))
	// use 'resource'

	/*
	'blog' => array(
		'default'  => 'you are not authorized to work with blogs',
		'delete'   => 'you are not authorized to delete this blog',
		'create'   => 'you are not authorized to create a blog'
	),
	
	'resource' => array(
		'delete' => 'you are not authorized to delete things'
	)
	*/
);