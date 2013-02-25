ALTER TABLE `applicants` ADD COLUMN `previous_job_title` varchar(255) AFTER `email`;

ALTER TABLE `ats_jobs` ADD COLUMN `apply_phone` varchar(255) AFTER `apply_url`, ADD COLUMN `tracking_code` varchar(255) AFTER `apply_phone`;
ALTER TABLE `ats_jobs` ADD COLUMN `modal_style` varchar(20) NOT NULL AFTER `tracking_code`;
ALTER TABLE `ats_jobs` ADD COLUMN `outbound_link_url` varchar(255) AFTER `apply_url`;
ALTER TABLE `ats_jobs` ADD COLUMN `dynamic_phone` tinyint(1) NOT NULL DEFAULT '1' AFTER `apply_phone`;
