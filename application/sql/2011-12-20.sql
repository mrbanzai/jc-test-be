# fix modal style so it's not required (danger, will robinson)
ALTER TABLE `ats_jobs` CHANGE COLUMN `modal_style` `modal_style` varchar(20) DEFAULT NULL;
