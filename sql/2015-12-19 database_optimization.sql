ALTER TABLE `statistics_source`
	DROP FOREIGN KEY `FK_statistics_source_source`;

ALTER TABLE `wifi`
	DROP FOREIGN KEY `FK_wifi_zdroj`;

ALTER TABLE `download_request`
	DROP FOREIGN KEY `FK_wigle_request_source`;

ALTER TABLE `source`
	CHANGE COLUMN `id` `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST;

ALTER TABLE `source`
	ALTER `name` DROP DEFAULT;
ALTER TABLE `source`
	CHANGE COLUMN `name` `name` VARCHAR(20) NULL AFTER `id`;

ALTER TABLE `statistics_source`
ALTER `id_source` DROP DEFAULT;
ALTER TABLE `statistics_source`
CHANGE COLUMN `id_source` `id_source` TINYINT UNSIGNED NULL AFTER `id_statistics`;

ALTER TABLE `statistics_source`
ADD CONSTRAINT `FK_statistics_source_source` FOREIGN KEY (`id_source`) REFERENCES `source` (`id`);

ALTER TABLE `wifi`
CHANGE COLUMN `id_source` `id_source` TINYINT UNSIGNED NOT NULL DEFAULT '1' AFTER `id`;

ALTER TABLE `wifi`
ADD CONSTRAINT `FK_wifi_source` FOREIGN KEY (`id_source`) REFERENCES `source` (`id`);

ALTER TABLE `download_request`
ALTER `id_source` DROP DEFAULT;
ALTER TABLE `download_request`
CHANGE COLUMN `id_source` `id_source` TINYINT UNSIGNED NOT NULL AFTER `id`;

ALTER TABLE `download_request`
ADD CONSTRAINT `FK_download_request_source` FOREIGN KEY (`id_source`) REFERENCES `source` (`id`);

ALTER TABLE `wigle_aps`
ALTER `mac` DROP DEFAULT;
ALTER TABLE `wigle_aps`
CHANGE COLUMN `mac` `mac` CHAR(17) NOT NULL COLLATE 'utf8_czech_ci' AFTER `id_wigle_download_queue`;

