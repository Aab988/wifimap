-- --------------------------------------------------------
-- Hostitel:                     127.0.0.1
-- Verze serveru:                5.6.24 - MySQL Community Server (GPL)
-- OS serveru:                   Win32
-- HeidiSQL Verze:               9.2.0.4947
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Exportování struktury databáze pro
CREATE DATABASE IF NOT EXISTS `wifimap` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci */;
USE `wifimap`;


-- Exportování struktury pro tabulka wifimap.wifi
CREATE TABLE IF NOT EXISTS `wifi` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_zdroj` int(11) unsigned NOT NULL DEFAULT '1',
  `mac` char(17) NOT NULL,
  `ssid` varchar(255) NOT NULL,
  `sec` tinyint(4) NOT NULL,
  `latitude` double NOT NULL,
  `longtitude` double NOT NULL,
  `altitude` double NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `freenet` char(1) DEFAULT NULL,
  `paynet` char(1) DEFAULT NULL,
  `firsttime` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  `flags` varchar(255) DEFAULT NULL,
  `wep` char(1) DEFAULT NULL,
  `lastupdt` varchar(50) DEFAULT NULL,
  `channel` int(11) DEFAULT NULL,
  `bcninterval` varchar(50) DEFAULT NULL,
  `qos` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_zdroj_mac_ssid_latitude_longtitude` (`id_zdroj`,`mac`,`ssid`,`latitude`,`longtitude`),
  KEY `FK_wifi_zdroj` (`id_zdroj`),
  KEY `latitude` (`latitude`),
  KEY `longtitude` (`longtitude`),
  CONSTRAINT `FK_wifi_zdroj` FOREIGN KEY (`id_zdroj`) REFERENCES `zdroj` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Export dat nebyl vybrán.


-- Exportování struktury pro tabulka wifimap.zdroj
CREATE TABLE IF NOT EXISTS `zdroj` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(50) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Export dat nebyl vybrán.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
