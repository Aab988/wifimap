TRUNCATE `log`;

DROP TABLE `log`;

CREATE TABLE `log` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`created` DATETIME NOT NULL,
	`ip` VARCHAR(255) NULL,
	`url` VARCHAR(255) NULL,
	`type` ENUM('ERROR','WARNING','INFO') NOT NULL,
	`operation` VARCHAR(255) NULL,
	`message` TEXT NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_czech_ci'
ENGINE=InnoDB
;
