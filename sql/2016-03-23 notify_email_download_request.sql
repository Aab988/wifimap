
CREATE TABLE `notify_email` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`added` DATETIME NULL DEFAULT NULL,
	`email` VARCHAR(255) NOT NULL COLLATE 'utf8_czech_ci',
	`sent` ENUM('Y','N') NOT NULL DEFAULT 'N' COLLATE 'utf8_czech_ci',
	`sent_date` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
)
	COLLATE='utf8_czech_ci'
	ENGINE=InnoDB
;

CREATE TABLE `notify_email_download_import` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_download_import` INT(10) UNSIGNED NOT NULL,
	`id_notify_email` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `FK__download_import` (`id_download_import`),
	INDEX `FK__notify_email` (`id_notify_email`),
	CONSTRAINT `FK__download_import` FOREIGN KEY (`id_download_import`) REFERENCES `download_import` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT `FK__notify_email` FOREIGN KEY (`id_notify_email`) REFERENCES `notify_email` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
	COLLATE='utf8_czech_ci'
	ENGINE=InnoDB
;


CREATE TABLE `notify_email_wigle_aps` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_wigle_aps` INT(10) UNSIGNED NOT NULL,
	`id_notify_email` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `FK_notify_email_wigle_aps_wigle_aps` (`id_wigle_aps`),
	INDEX `FK_notify_email_wigle_aps_notify_email` (`id_notify_email`),
	CONSTRAINT `FK_notify_email_wigle_aps_notify_email` FOREIGN KEY (`id_notify_email`) REFERENCES `notify_email` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT `FK_notify_email_wigle_aps_wigle_aps` FOREIGN KEY (`id_wigle_aps`) REFERENCES `wigle_aps` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
	COLLATE='utf8_czech_ci'
	ENGINE=InnoDB
;

