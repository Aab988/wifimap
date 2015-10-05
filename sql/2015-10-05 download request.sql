ALTER TABLE `wigle_request`
	ADD COLUMN `id_source` INT(10) UNSIGNED NOT NULL AFTER `id`;

update wigle_request set id_source=2 where 1;

ALTER TABLE `wigle_request`
	ADD CONSTRAINT `FK_wigle_request_source` FOREIGN KEY (`id_source`) REFERENCES `source` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

RENAME TABLE `wigle_request` TO `download_request`;

