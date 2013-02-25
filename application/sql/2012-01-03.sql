INSERT INTO `ats_type` ( `name`, `id`, `website`) VALUES ('JobTarget', '6', 'http://www.jobtarget.com/');

# add address and zipcode to job listing
ALTER TABLE `ats_jobs` ADD COLUMN `city` varchar(60) DEFAULT NULL AFTER `location`;
ALTER TABLE `ats_jobs` ADD COLUMN `state` varchar(20) DEFAULT NULL AFTER `city`;
ALTER TABLE `ats_jobs` ADD COLUMN `address` varchar(160) DEFAULT NULL AFTER `state`;
ALTER TABLE `ats_jobs` ADD COLUMN `zipcode` varchar(11) DEFAULT NULL AFTER `address`;
ALTER TABLE `ats_jobs` ADD INDEX `city_idx` USING BTREE (`city`);
ALTER TABLE `ats_jobs` ADD INDEX `state_idx` USING BTREE (`state`);
ALTER TABLE `ats_jobs` ADD INDEX `city_state_idx` USING BTREE (`city`, `state`);

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

# create the api limit table
CREATE TABLE IF NOT EXISTS `api_limit` (
    `key_id` CHAR(40) NOT NULL PRIMARY KEY,
    `max_requests` INT(11) UNSIGNED DEFAULT 0,
    `num_requests` INT(11) UNSIGNED DEFAULT 0,
    `last_request` INT(11) UNSIGNED DEFAULT NULL,
    `reset_ts` INT(11) UNSIGNED DEFAULT NULL
) engine=InnoDB charset=utf8;
