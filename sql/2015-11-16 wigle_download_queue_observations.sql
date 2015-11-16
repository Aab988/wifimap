ALTER TABLE `wigle_download_queue`
	ADD COLUMN `count_downloaded_observations` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `to`;

ALTER TABLE `wigle_aps`
ADD COLUMN `id_wigle_download_queue` INT(10) UNSIGNED NOT NULL AFTER `id`;
