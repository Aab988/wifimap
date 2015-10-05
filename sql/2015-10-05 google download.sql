ALTER TABLE `source`
	ALTER `name` DROP DEFAULT;
ALTER TABLE `source`
	CHANGE COLUMN `name` `name` VARCHAR(50) NOT NULL AFTER `id`;

INSERT INTO `wifimap`.`source` (`name`) VALUES ('google');
