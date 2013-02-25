CREATE TABLE `zipcodes` (
  `zipcode` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
  `latitude` DECIMAL(10,8),
  `longitude` DECIMAL(10,8),
  `state` VARCHAR(2),
  `city` VARCHAR(128),
  `county` VARCHAR(128),
  
);
