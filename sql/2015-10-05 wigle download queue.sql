	RENAME TABLE `download_queue` TO `wigle_download_queue`;

	ALTER TABLE `wigle_download_queue`
		DROP COLUMN `id_source`,
		DROP INDEX `FK_download_queue_source`,
		DROP FOREIGN KEY `FK_download_queue_source`;
