CREATE TABLE `google_request` (
	`id` INT UNSIGNED NOT NULL,
	`created` DATE NOT NULL,
	`id_wifi1` INT UNSIGNED NOT NULL,
	`id_wifi2` INT UNSIGNED NULL,
	`downloaded` ENUM('Y','N') NOT NULL DEFAULT 'N'
)
COLLATE='utf8_czech_ci'
ENGINE=InnoDB
;
ALTER TABLE `google_request`
CHANGE COLUMN `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
ADD PRIMARY KEY (`id`);

ALTER TABLE `google_request`
ADD CONSTRAINT `FK_google_request_wifi` FOREIGN KEY (`id_wifi1`) REFERENCES `wifi` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
ADD CONSTRAINT `FK_google_request_wifi_2` FOREIGN KEY (`id_wifi2`) REFERENCES `wifi` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;



