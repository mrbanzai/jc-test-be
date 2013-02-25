ALTER TABLE `users` ADD COLUMN `dynamic_phone_tracking` text NOT NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `default_phone` varchar(100) NOT NULL AFTER `dynamic_phone_tracking`;
