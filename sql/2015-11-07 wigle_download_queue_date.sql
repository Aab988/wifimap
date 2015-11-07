ALTER TABLE `wigle_download_queue`
	ADD COLUMN `created` DATETIME NULL DEFAULT NULL AFTER `id_download_request`,
	ADD COLUMN `processed` DATETIME NULL DEFAULT NULL AFTER `created`;
