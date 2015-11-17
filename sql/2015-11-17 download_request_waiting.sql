CREATE TABLE `download_request_waiting` (
	`id_download_request` INT(10) UNSIGNED NOT NULL,
	`id_download_request_waitfor` INT(10) UNSIGNED NOT NULL,
	`completed` ENUM('Y','N') NOT NULL DEFAULT 'N' COLLATE 'utf8_czech_ci',
	`completed_date` DATETIME NULL DEFAULT NULL,
	INDEX `FK__download_request` (`id_download_request`),
	INDEX `FK__download_request_2` (`id_download_request_waitfor`),
	CONSTRAINT `FK__download_request` FOREIGN KEY (`id_download_request`) REFERENCES `download_request` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT `FK__download_request_2` FOREIGN KEY (`id_download_request_waitfor`) REFERENCES `download_request` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
COLLATE='utf8_czech_ci'
ENGINE=InnoDB
;
