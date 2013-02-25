#
# Encoding: Unicode (UTF-8)
#
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `users_profile`;
DROP TABLE IF EXISTS `users_tokens`;
DROP TABLE IF EXISTS `session`;
DROP TABLE IF EXISTS `password_recovery`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fbuid` int(11) unsigned DEFAULT NULL,
  `role` int(11) unsigned NOT NULL,
  `firstname` varchar(80) NOT NULL,
  `lastname` varchar(80) NOT NULL,
  `about` text DEFAULT NULL,
  `avatarhash` varchar(40) DEFAULT NULL,
  `avatarext` varchar(4) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT '',
  `avatar_thumb` varchar(200) DEFAULT '',
  `email` varchar(120) DEFAULT NULL,
  `show_email` tinyint(1) unsigned DEFAULT '1',
  `password` char(32) NOT NULL,
  `salt` char(16) NOT NULL,
  `lastvisit` int(11) DEFAULT NULL,
  `disabled` tinyint(1) unsigned DEFAULT '0',
  `created_ts` int(11) unsigned DEFAULT NULL,
  `modified_ts` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `fbuid` (`fbuid`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

CREATE TABLE `users_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `about` text,
  `created_ts` int(11) unsigned DEFAULT NULL,
  `modified_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `roles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE `session` (
    `id` char(32),
    `modified` int,
    `lifetime` int,
    `data` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `password_recovery` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `unique_id` char(32) NOT NULL,
  `expires` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_unique_hash` (`user_id`,`unique_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

# invitation codes
CREATE TABLE `users_tokens` (
    `id` char(32) NOT NULL PRIMARY KEY,
    `user_id` int(11) unsigned NOT NULL,
    `usage` int(11) unsigned DEFAULT 0,
    `max_usage` int(11) unsigned DEFAULT 1,
    `expires` int(11) unsigned DEFAULT NULL,
    `created_ts` int(11) unsigned NOT NULL,
    UNIQUE KEY `uniq_user` USING BTREE (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

# invitation request emails
CREATE TABLE `invite_requests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `created_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# the ATS details for the new user
CREATE TABLE `users_ats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `ats_type_id` int(11) unsigned NOT NULL,
  `login_subdomain` varchar(16) NOT NULL,
  `ats_username` varchar(255) NOT NULL,
  `ats_password` varchar(40) NOT NULL,
  `ats_vector` varchar(20) NOT NULL,
  `ats_login_url` varchar(255) NOT NULL,
  `company` varchar(80) DEFAULT NULL,
  `subdomain` varchar(255) NOT NULL,
  `cname` varchar(120) NOT NULL,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_idx` USING BTREE (`user_id`),
  UNIQUE INDEX `login_subdomain_uniq_idx` (`login_subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# the ATS style settings for the user
CREATE TABLE `users_ats_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `users_ats_id` int(11) unsigned NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `font` varchar(120) DEFAULT 'Verdana, Arial, sans-serif',
  `bgcolor` varchar(6) DEFAULT 'FFF',
  `color` varchar(6) DEFAULT '000',
  `header_bgcolor` varchar(6) DEFAULT 'FFF',
  `header_color` varchar(6) DEFAULT '000',
  `header_link` varchar(6) DEFAULT 'FF0000',
  `header_link_bgcolor` varchar(6) DEFAULT '000',
  `nav_bgcolor` varchar(6) DEFAULT 'FFF',
  `nav_color` varchar(6) DEFAULT '000',
  `nav_link` varchar(6) DEFAULT 'FF0000',
  `nav_link_hover` varchar(6) DEFAULT '000',
  `sidebar_bgcolor` varchar(6) DEFAULT 'FFF',
  `sidebar_color` varchar(6) DEFAULT '000',
  `sidebar_link` varchar(6) DEFAULT 'FF0000',
  `sidebar_link_hover` varchar(6) DEFAULT '000',
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_idx` USING BTREE (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# ATS job listings
CREATE TABLE `ats_jobs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(30) DEFAULT NULL,
  `uristub` varchar(120) NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `url`
  `is_editable` tinyint(1) unsigned DEFAULT 0,
  `is_closed` tinyint(1) unsigned DEFAULT 0,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `job_id_idx` (`job_id`),
  UNIQUE KEY `uristub_uniq` (`uristub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# the supported ATS categories
CREATE TABLE `ats_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uristub` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uristub_uniq` (`uristub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# the supported ATS locations
CREATE TABLE `ats_locations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uristub` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `city` varchar(30) DEFAULT NULL,
  `state` char(2) DEFAULT NULL,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uristub_uniq` (`uristub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# the supported ATS systems
CREATE TABLE `ats_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# current ATSs
INSERT INTO `ats_jobs` ();

######################
# END TABLE CREATION #
######################
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `roles` (`id`, `role`) VALUES (1, 'admin'), (2, 'user'), (3, 'guest'), (4, 'super');
INSERT INTO `users` (`id`,`role`,`firstname`, `lastname`, `about`, `avatarhash`, `avatarext`, `avatar`, `avatar_thumb`, `email`, `password`, `salt`, `lastvisit`, `disabled`, `created_ts`, `modified_ts`) VALUES
(1, 2, 'Ballou', 'Corey', 'Haters gonna hate.', 'ff9d3b1d8423905acc6c13413e839eca7538e8c5', NULL, '/uploads/user/avatars/2.jpg', '/uploads/user/avatars/2_thumb.jpg', 'corey@skookum.com', 'ae9479449d7e7ea90f58671c75c57568', 'j&9Gt59jNG6l@40a', 1290113909, 0, 1286899296, NULL);

# fix the super admin role id
UPDATE `roles` SET id = 0 WHERE id = 4;

SET FOREIGN_KEY_CHECKS = 1;
