ALTER TABLE `wigle_aps`
	ADD COLUMN `priority` TINYINT NOT NULL DEFAULT '1' AFTER `downloaded_date`;


ALTER TABLE `google_request`
	ADD COLUMN `priority` TINYINT NOT NULL DEFAULT '1' AFTER `id_download_request`;
