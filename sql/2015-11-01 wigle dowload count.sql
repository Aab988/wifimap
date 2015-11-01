ALTER TABLE `download_request`
	ADD COLUMN `total_count` INT NULL AFTER `processed_date`,
	ADD COLUMN `downloaded_count` INT NULL AFTER `total_count`;

ALTER TABLE `google_request`
ADD COLUMN `id_download_request` INT UNSIGNED NULL AFTER `downloaded`,
ADD CONSTRAINT `FK_google_request_download_request` FOREIGN KEY (`id_download_request`) REFERENCES `download_request` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `wigle_download_queue`
ADD COLUMN `id_download_request` INT UNSIGNED NULL AFTER `id`,
ADD CONSTRAINT `FK_wigle_download_queue_download_request` FOREIGN KEY (`id_download_request`) REFERENCES `download_request` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `download_request`
CHANGE COLUMN `total_count` `total_count` INT(11) NOT NULL DEFAULT '0' AFTER `processed_date`,
CHANGE COLUMN `downloaded_count` `downloaded_count` INT(11) NOT NULL DEFAULT '0' AFTER `total_count`;
