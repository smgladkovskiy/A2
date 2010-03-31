This A2 module can work with the data in the database, not only in config file.

## Database schema

	CREATE TABLE IF NOT EXISTS `assertions` (
	  `id` smallint(5) unsigned NOT NULL auto_increment,
	  `rule_id` smallint(5) unsigned NOT NULL,
	  `resource_id` smallint(5) unsigned NOT NULL,
	  `user_field` varchar(36) NOT NULL,
	  `resource_field` varchar(36) NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `fk_assertion_rule` (`rule_id`),
	  KEY `fk_assertion_resource` (`resource_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `privileges` (
	  `id` smallint(5) unsigned NOT NULL auto_increment,
	  `name` varchar(64) NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `resources` (
	  `id` smallint(5) unsigned NOT NULL auto_increment,
	  `parent_id` smallint(5) unsigned default NULL,
	  `name` varchar(64) NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `fk_resource_parent` (`parent_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `roles` (
	  `id` smallint(5) unsigned NOT NULL auto_increment,
	  `parent_id` smallint(5) unsigned default NULL,
	  `name` varchar(64) NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `fk_role_parent` (`parent_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `roles_rules` (
	  `role_id` smallint(5) unsigned NOT NULL,
	  `rule_id` smallint(5) unsigned NOT NULL,
	  PRIMARY KEY  (`role_id`,`rule_id`),
	  KEY `fk_role_rule` (`rule_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `roles_users` (
	  `role_id` smallint(5) unsigned NOT NULL,
	  `user_id` smallint(5) unsigned NOT NULL,
	  PRIMARY KEY  (`role_id`,`user_id`),
	  KEY `fk_role_user` (`user_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `rules` (
	  `id` smallint(5) unsigned NOT NULL auto_increment,
	  `type` enum('allow','deny') NOT NULL COMMENT 'rule type (allow/deny)',
	  `name` varchar(64) NOT NULL,
	  `resource_id` smallint(5) unsigned default NULL,
	  PRIMARY KEY  (`id`),
	  KEY `fk_rule_resource` (`resource_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `rules_privileges` (
	  `rule_id` smallint(5) unsigned NOT NULL,
	  `privilege_id` smallint(5) unsigned NOT NULL,
	  PRIMARY KEY  (`rule_id`,`privilege_id`),
	  KEY `fk_rule_privelege` (`privilege_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	ALTER TABLE `assertions`
	  ADD CONSTRAINT `fk_assertion_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE,
	  ADD CONSTRAINT `fk_assertion_rule` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON UPDATE CASCADE;

	ALTER TABLE `resources`
	  ADD CONSTRAINT `fk_resource_parent` FOREIGN KEY (`parent_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

	ALTER TABLE `roles`
	  ADD CONSTRAINT `fk_role_parent` FOREIGN KEY (`parent_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

	ALTER TABLE `roles_rules`
	  ADD CONSTRAINT `fk_rule_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  ADD CONSTRAINT `fk_role_rule` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

	ALTER TABLE `roles_users`
	  ADD CONSTRAINT `fk_role_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

	ALTER TABLE `rules`
	  ADD CONSTRAINT `fk_rule_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE;

	ALTER TABLE `rules_privileges`
	  ADD CONSTRAINT `fk_privilege_rule` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  ADD CONSTRAINT `fk_rule_privelege` FOREIGN KEY (`privilege_id`) REFERENCES `privileges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;