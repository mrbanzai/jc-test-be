DROP TABLE IF EXISTS `session`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `password_recovery`;
DROP TABLE IF EXISTS `ats_feed`;
DROP TABLE IF EXISTS `ats_feed_listing`;
DROP TABLE IF EXISTS `ats_job`;
DROP TABLE IF EXISTS `ats_feed_type`;
DROP TABLE IF EXISTS `ats_type`;
DROP TABLE IF EXISTS `theme`;
DROP TABLE IF EXISTS `crawl_index`;
DROP TABLE IF EXISTS `history_crawl_index`;
DROP TABLE IF EXISTS `sitemaps`;

CREATE TABLE `session` (
  `id` char(32) NOT NULL DEFAULT '',
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL DEFAULT 2,
  `subdomain` varchar(20) DEFAULT NULL,
  `cname` varchar(120) DEFAULT NULL,
  `firstname` varchar(60) DEFAULT NULL,
  `lastname` varchar(60) DEFAULT NULL,
  `company` varchar(60) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `passphrase` char(60) NOT NULL,
  `salt` char(22) NOT NULL,
  `dynamic_phone_tracking` text DEFAULT NULL,
  `default_phone` varchar(100) DEFAULT NULL,
  `disabled` tinyint(1) unsigned DEFAULT 0,
  `deleted` tinyint(1) unsigned DEFAULT 0,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_idx` (`email`),
  UNIQUE KEY `subdomain_uniq_idx` (`subdomain`),
  KEY `user_role_idx` (`id`, `role_id`),
  KEY `subdomain_id_idx` (`subdomain`, `id`),
  KEY `cname_idx` (`cname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `created_ts` int(11) unsigned NOT NULL,
  `modified_ts` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `password_recovery` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `unique_id` char(32) NOT NULL,
  `expires` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_uniq_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# all ATS feeds (path to feed data)
CREATE TABLE IF NOT EXISTS `ats_feed` (
    `id` int(11) unsigned not null auto_increment primary key,
    `feed_type_id` int(11) unsigned not null,
    `user_id` int(11) unsigned not null,
    `name` varchar(120) not null,
    `url` varchar(255) not null,
    `last_ran` int(11) unsigned default null,
    `created_ts` int(11) unsigned not null,
    `modified_ts` int(11) unsigned default null,
    KEY `user_id_idx` (`user_id`),
    KEY `last_ran_idx` (`last_ran`)
) engine=InnoDB default charset=utf8;

# all listing entries by feed
CREATE TABLE IF NOT EXISTS `ats_feed_listing` (
    `id` int(11) unsigned not null auto_increment primary key,
    `feed_id` int(11) unsigned not null,
    `job_url` varchar(255) not null,
    `last_ran` int(11) unsigned default null,
    `created_ts` int(11) unsigned not null,
    `modified_ts` int(11) unsigned default null,
    KEY `feed_id_idx` (`feed_id`),
    KEY `last_ran_idx` (`last_ran`),
    UNIQUE KEY `job_url_idx` (`job_url`)
) engine=InnoDB default charset=utf8;

# all jobs received
CREATE TABLE IF NOT EXISTS `ats_jobs` (
    `id` int(11) unsigned not null auto_increment primary key,
    `created_by` int(11) unsigned not null,
    `feed_id` int(11) unsigned not null,
    `job_id` varchar(120) default null,
    `uristub` varchar(155) not null,
    `company` varchar(60) default null,
    `name` varchar(120) not null,
    `location` varchar(120) default null,
    `city` varchar(60) DEFAULT NULL,
    `state` varchar(20) DEFAULT NULL,
    `address` varchar(160) DEFAULT NULL,
    `zipcode` varchar(11) DEFAULT NULL,
    `category` varchar(60) default null,
    `department` varchar(60) default null,
    `schedule` varchar(60) default null,
    `shift` varchar(20) default null,
    `description` text default null,
    `qualifications` text default null,
    `num_openings` int(11) unsigned default null,
    `years_exp` int(11) unsigned default null,
    `job_url` varchar(255) not null,
    `apply_url` varchar(255) default null,
    `apply_phone` varchar(255) default null,
    `dynamic_phone` tinyint(1) not null default '1',
    `tracking_code` varchar(255) default null,
    `outbound_link_url` varchar(255) default null,
    `modal_style` varchar(20) default null,
    `hide_apply` tinyint(1) unsigned default 0,
    `editable` tinyint(1) unsigned default 0,
    `failed_attempts` tinyint(3) unsigned default 0,
    `closed` tinyint(1) unsigned default 0,
    `deleted` tinyint(1) unsigned default 0,
    `date_posted` int(11) unsigned default null,
    `created_ts` int(11) unsigned not null,
    `modified_ts` int(11) unsigned default null,
    KEY `feed_id_idx` (`feed_id`),
    KEY `location_idx` (`location`),
    KEY `category_idx` (`category`),
    KEY `job_url_idx` (`job_url`),
    UNIQUE KEY `job_id_uniq_idx` (`job_id`),
    UNIQUE KEY `uristub_uniq_idx` (`uristub`, `created_by`),
    INDEX `created_category_schedule_idx` (created_by, category, schedule),
    INDEX `city_idx` USING BTREE (`city`),
    INDEX `state_idx` USING BTREE (`state`),
    INDEX `city_state_idx` USING BTREE (`city`, `state`)
) engine=InnoDB default charset=utf8;

# all allowable feed types (lookup table)
CREATE TABLE IF NOT EXISTS `ats_feed_type` (
    `id` int(11) unsigned not null auto_increment primary key,
    `short_name` varchar(10) default null,
    `name` varchar(120) not null,
    `created_ts` int(11) unsigned not null,
    `modified_ts` int(11) unsigned default null
) engine=InnoDB default charset=utf8;

# all allowable ats types (taleo, etc, etc)
CREATE TABLE IF NOT EXISTS `ats_type` (
    `id` int(11) unsigned not null auto_increment primary key,
    `name` varchar(120) not null,
    `website` varchar(255) default null,
    `created_ts` int(11) unsigned not null,
    `modified_ts` int(11) unsigned default null
) engine=InnoDB default charset=utf8;

# site theming
CREATE TABLE IF NOT EXISTS `theme` (
    `id` int(11) unsigned not null auto_increment primary key,
    `created_by` int(11) unsigned not null,
    `company` varchar(20) not null default '',
    `website` varchar(255) default null,
    `logo` varchar(255) default null,
    `bgcolor` char(6) default 'FFFFFF',
    `fgcolor` char(6) default '000000',
    `link` char(6) default '5171ac',
    `link_hover` char(6) default '41619c',
    `bgbutton` char(6) default '000000',
    `bgbutton_hover` char(6) default '111111',
    `fgbutton` char(6) default 'FFFFFF',
    `heading` char(6) default '000000',
    `created_ts` int(11) unsigned not null,
    `modified_ts` int(11) unsigned default null,
    UNIQUE KEY `created_by_uniq_idx` (`created_by`)
) engine=InnoDB default charset=utf8;

# job applicants
CREATE TABLE IF NOT EXISTS `applicants` (
    `id` int(11) unsigned not null auto_increment primary key,
    `client_id` int(11) unsigned not null,
    `job_id` int(11) unsigned not null,
    `name` varchar(60) not null,
    `email` varchar(120) not null,
    `previous_job_title` varchar(255)
    `cover_letter` text default null,
    `resume` varchar(255) default null,
    `deleted` tinyint(1) unsigned default 0,
    `responded` tinyint(1) unsigned default 0,
    `created_ts` int(11) unsigned default null,
    KEY `client_id_idx` (`client_id`)
) engine=InnoDB default charset=utf8;

CREATE TABLE `crawl_index` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `client_id` int(11) unsigned NOT NULL,
  `link` varchar(255) NOT NULL,
  `count` int(11) unsigned default '1',
  `contenthash` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  INDEX `user_idx` (`client_id`),
  INDEX `link_idx` (`link`),
  UNIQUE KEY `user_link_uniq_idx` (`client_id`, `link`)
) ENGINE=MyISAM default CHARSET=utf8;

# a backup of the previous ran crawl
CREATE TABLE `history_crawl_index` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `client_id` int(11) unsigned NOT NULL,
  `link` varchar(255) NOT NULL,
  `count` int(11) unsigned default '1',
  `contenthash` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  INDEX `user_idx` (`client_id`),
  INDEX `link_idx` (`link`),
  UNIQUE KEY `user_link_uniq_idx` (`client_id`, `link`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

# custom sitemap for each subdomain
CREATE TABLE IF NOT EXISTS `sitemaps` (
    `id` int(11) unsigned not null auto_increment primary key,
    `created_by` int(11) unsigned not null,
    `sitemap` LONGTEXT NOT NULL,
    UNIQUE KEY `created_by_uniq_idx` (`created_by`)
) engine=InnoDB default charset=utf8;

# create the api table
CREATE TABLE IF NOT EXISTS `api_keys` (
    `user_id` INT(11) UNSIGNED NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `public` CHAR(40) NOT NULL PRIMARY KEY,
    `private` CHAR(40) NOT NULL,
    `created_ts` INT(11) UNSIGNED DEFAULT NULL,
    `modified_ts` INT(11) UNSIGNED DEFAULT NULL,
    UNIQUE INDEX `uniq_domain` USING BTREE (`domain`),
    UNIQUE INDEX `uniq_user_idx` USING BTREE (`user_id`)
) engine=InnoDB charset=utf8;

# create the api limit table (not used)
CREATE TABLE IF NOT EXISTS `api_limit` (
    `key_id` CHAR(40) NOT NULL PRIMARY KEY,
    `max_requests` INT(11) UNSIGNED DEFAULT 0,
    `num_requests` INT(11) UNSIGNED DEFAULT 0,
    `last_request` INT(11) UNSIGNED DEFAULT NULL,
    `reset_ts` INT(11) UNSIGNED DEFAULT NULL
) engine=InnoDB charset=utf8;

# add the ability to manage features (NOT YET USED)
CREATE TABLE IF NOT EXISTS `features` (
    `id` int(11) unsigned not null auto_increment primary key,
    `admin_only` TINYINT(1) unsigned DEFAULT 1,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `disabled` TINYINT(1) UNSIGNED DEFAULT 0,
    `created_ts` INT(11) UNSIGNED DEFAULT NULL,
    `modified_ts` INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY  (`id`)
) engine=InnoDB charset=utf8;

# create a relation between clients and site features for enabling (NOT YET USED)
CREATE TABLE IF NOT EXISTS `users_rel_features` (
    `user_id` int(11) unsigned not null,
    `feature_id` int(11) unsigned not null,
    `disabled` TINYINT(1) UNSIGNED DEFAULT 0,
    `created_ts` INT(11) UNSIGNED DEFAULT NULL,
    `modified_ts` INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY  (`user_id`, `feature_id`),
    INDEX `feature_user_idx` (`feature_id`, `user_id`)
) engine=InnoDB charset=utf8;

# insert base users
INSERT INTO `users` VALUES
(1,4,NULL,'corey','ballou','company 1','corey@skookum.com','$2a$10$9LOqLiBtRpoaQMlgIofZt.dnR0AaftFydAmBJNOMbEviDjp1RJdeW','9LOqLiBtRpoaQMlgIofZt',0,0,1287151258,1286899296),
(2,4,'default','user','user','company 2','developers@skookum.com','$2a$10$9LOqLiBtRpoaQMlgIofZt.dnR0AaftFydAmBJNOMbEviDjp1RJdeW','9LOqLiBtRpoaQMlgIofZt',0,0,1286899296,1286899296);

# insert base roles
INSERT INTO `roles` VALUES
(1, 'Guest', '', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(2, 'User', '', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(3, 'Administrator', '', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(4, 'Super', '', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);
UPDATE `roles` SET id = 0 WHERE id = 4;

# allowable atses
INSERT INTO `ats_type` VALUES
(1, 'Taleo', 'http://www.taleo.com', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(2, 'ICIMS', 'http://www.icims.com', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(3, 'Internal', 'http://www.internalfeed.com', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(4, 'DataFrenzy', 'http://www.datafrenzy.com/', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(5, 'HRSmart', 'http://www.hrsmart.com/', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(6, 'JobTarget', 'http://www.jobtarget.com/', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# allowable feed types
INSERT INTO `ats_feed_type` VALUES
(1, 'xml', 'XML', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(2, 'json', 'JSON', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(3, 'html', 'HTML', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# pre-populate the internal feed
INSERT INTO `ats_feed` VALUES
(1, 3, 1, 'Internal Feed', 'http://www.internalfeed.com', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# pre-populate a bunch of Taleo XML feeds as a test
#INSERT INTO `ats_feed` VALUES
#(2, 1, 1, 'Nurse.com', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=10140', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(3, 1, 1, 'Advance.com', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=10141', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(4, 1, 1, 'Jobing.com', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11010', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(5, 1, 1, 'MoreCareerChoices', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=10142', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(6, 1, 1, 'AbsolutelyHealthcare', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11009', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(7, 1, 1, 'Careerbuilder', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11011', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(8, 1, 1, 'HealtheCareers', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11012', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(9, 1, 1, 'Monster', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11014', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(10, 1, 1, 'SimplyHired', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11016', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(11, 1, 1, 'Indeed', 'https://hca.taleo.net/careersection/sitemap.jss?portalCode=11017', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# pre-populate ICIMS feeds
#INSERT INTO `ats_feed` VALUES
#(12, 2, 1, 'Inhouse', 'https://jobs-inhouse.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(13, 2, 1, 'Soc USA', 'https://jobs-soc-usa.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(14, 2, 1, 'Gilt City', 'https://careers-giltcity.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(15, 2, 1, 'TMG Office Services', 'https://careers-tmgofficeservices.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(16, 2, 1, 'Fisher Nuts', 'https://careers-fishernuts.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(17, 2, 1, 'NYPL', 'https://jobs-nypl.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(18, 2, 1, 'Calista', 'https://jobs-calista.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
#(19, 2, 1, 'LF USA', 'https://careers-lfusa.icims.com/jobs/intro', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# pre-populate JobTarget feed for testing
#INSERT INTO `ats_feed` VALUES
#(20, 6, 1, 'JobTarget - Parallon', 'http://www.jobtarget.com/distrib/clients/jobcastle/parallonAAS.xml', NULL, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# base set of toggleable site features
INSERT INTO `features` VALUES
(1, 1, 'Enable Marchex Dynamic Phone Tracking', 'This feature gives admins the ability to add dynamic phone number tracking for job applicants by phone.', 0, UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0),
(2, 1, 'Allow Overriding a jobs Apply Now URL', 'This feature gives clients the ability to override a jobs outbound apply url.', UTC_TIMESTAMP() + 0, UTC_TIMESTAMP() + 0);

# possible features
#####################
# ats_jobs.apply_phone
# ats_jobs.apply_url
# ats_jobs.outbound_link_url
# ats_jobs.tracking_code
# ats_jobs.modal_style
# ats_jobs.hide_apply
# enable API
#
