ALTER TABLE `wigle_aps`
	ADD COLUMN `priority` TINYINT NOT NULL DEFAULT '1' AFTER `downloaded_date`;


ALTER TABLE `google_request`
	ADD COLUMN `priority` TINYINT NOT NULL DEFAULT '1' AFTER `id_download_request`;


ALTER TABLE `wigle_aps`
ALTER `id_wigle_download_queue` DROP DEFAULT;
ALTER TABLE `wigle_aps`
CHANGE COLUMN `id_wigle_download_queue` `id_wigle_download_queue` INT(10) UNSIGNED NULL AFTER `id`;
