CREATE TABLE `download_import` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`mac` CHAR(17) NOT NULL,
	`state` TINYINT UNSIGNED NOT NULL,
	`id_wigle_aps` INT UNSIGNED NULL,
	`id_google_request` INT UNSIGNED NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_czech_ci'
ENGINE=InnoDB
;

ALTER TABLE `download_import`
ADD CONSTRAINT `FK_download_import_wigle_aps` FOREIGN KEY (`id_wigle_aps`) REFERENCES `wigle_aps` (`id`) ON UPDATE SET NULL ON DELETE SET NULL,
ADD CONSTRAINT `FK_download_import_google_request` FOREIGN KEY (`id_google_request`) REFERENCES `google_request` (`id`) ON UPDATE SET NULL ON DELETE SET NULL;


