-- --------------------------------------------------------
-- Host:                         123.31.17.59
-- Server version:               5.7.29 - MySQL Community Server (GPL)
-- Server OS:                    Linux
-- HeidiSQL Version:             9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table sbc.acl
CREATE TABLE IF NOT EXISTS `acl` (
  `i_acl` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_auth` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `ip_proxy` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `block_regex_caller` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `block_regex_callee` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `allow_regex_caller` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `allow_regex_callee` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`i_acl`)
) ENGINE=InnoDB AUTO_INCREMENT=878 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.callee_destination
CREATE TABLE IF NOT EXISTS `callee_destination` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callee_regex` varchar(5000) COLLATE utf8_unicode_ci NOT NULL,
  `destination` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `i_sip_profile` int(11) NOT NULL,
  `i_routing` int(11) NOT NULL,
  `i_private_routing` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.caller_group
CREATE TABLE IF NOT EXISTS `caller_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caller` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `enterprise` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'So dai dien cho tat ca caller',
  `algorithm` int(11) NOT NULL COMMENT 'Thuat toan chon caller, 1-ramdon, 2-round robin..., 3-lowest cost',
  `callee_regex` varchar(5000) COLLATE utf8_unicode_ci NOT NULL COMMENT 'algorithm = 3 thi check them callee de dinh tuyen lowest cost',
  `status` int(11) NOT NULL COMMENT '0: active, 1-deactive',
  `cus_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `caller` (`caller`),
  KEY `id` (`id`),
  KEY `cus_id` (`cus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.caller_group_bk202103
CREATE TABLE IF NOT EXISTS `caller_group_bk202103` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caller` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `enterprise` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'So dai dien cho tat ca caller',
  `algorithm` int(11) NOT NULL COMMENT 'Thuat toan chon caller, 1-ramdon, 2-round robin..., 3-lowest cost',
  `callee_regex` varchar(5000) COLLATE utf8_unicode_ci NOT NULL COMMENT 'algorithm = 3 thi check them callee de dinh tuyen lowest cost',
  `status` int(11) NOT NULL COMMENT '0: active, 1-deactive',
  `cus_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `caller` (`caller`),
  KEY `id` (`id`),
  KEY `cus_id` (`cus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors
CREATE TABLE IF NOT EXISTS `cdr_vendors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL,
  `connect_time` datetime DEFAULT NULL,
  `disconnect_time` datetime NOT NULL,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quality_mos` float DEFAULT NULL,
  `quality_largest_jb` float DEFAULT NULL,
  `quality_jitter_burst_rate` float DEFAULT NULL,
  `duration` int(10) NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `charge_status` int(11) DEFAULT '0' COMMENT '0-new, 1-processing, 2-done,-1:error; -2: sent but error',
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_id` (`call_id`),
  KEY `CLI` (`CLI`),
  KEY `CLD` (`CLD`),
  KEY `i_vendor` (`i_vendor`),
  KEY `i_customer` (`i_customer`),
  KEY `charge_status` (`charge_status`,`connect_time`,`i_vendor`),
  KEY `from_network_ip` (`from_network_ip`),
  KEY `setup_time` (`setup_time`)
) ENGINE=InnoDB AUTO_INCREMENT=908994199 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_extention
CREATE TABLE IF NOT EXISTS `cdr_vendors_extention` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Join với cdr_vendors.call_id',
  `call_brandname` int(11) DEFAULT '0' COMMENT '0: Bình thường 1 BrandName',
  `ext_field1` varchar(50) DEFAULT NULL,
  `ext_field2` varchar(50) DEFAULT NULL,
  `ext_field3` varchar(50) DEFAULT NULL,
  `ext_field4` varchar(50) DEFAULT NULL,
  `ext_field5` varchar(50) DEFAULT NULL,
  `ext_field6` int(11) DEFAULT '0',
  `ext_field7` int(11) DEFAULT '0',
  `ext_field8` int(11) DEFAULT '0',
  `ext_field9` int(11) DEFAULT '0',
  `ext_field10` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cdr_vendors_id` (`call_id`),
  KEY `call_brandname` (`call_brandname`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_failed
CREATE TABLE IF NOT EXISTS `cdr_vendors_failed` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disconnect_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_id` (`call_id`),
  KEY `CLI` (`CLI`),
  KEY `CLD` (`CLD`),
  KEY `setup_time` (`setup_time`),
  KEY `i_vendor` (`i_vendor`)
) ENGINE=InnoDB AUTO_INCREMENT=264 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_failed_extention
CREATE TABLE IF NOT EXISTS `cdr_vendors_failed_extention` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Join với cdr_vendors_failds.call_id',
  `call_brandname` int(11) DEFAULT '0' COMMENT '0: Bình thường 1 BrandName',
  `reject_cause` int(11) DEFAULT '0' COMMENT 'ma loi tu choi dinh tuyen cuoc goi 4xx, 5xx',
  `ext_field1` varchar(50) DEFAULT NULL,
  `ext_field2` varchar(50) DEFAULT NULL,
  `ext_field3` varchar(50) DEFAULT NULL,
  `ext_field4` varchar(50) DEFAULT NULL,
  `ext_field5` varchar(50) DEFAULT NULL,
  `ext_field6` int(11) DEFAULT '0',
  `ext_field7` int(11) DEFAULT '0',
  `ext_field8` int(11) DEFAULT '0',
  `ext_field9` int(11) DEFAULT '0',
  `ext_field10` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cdr_vendors_failed_id` (`call_id`),
  KEY `call_brandname` (`call_brandname`),
  KEY `reject_cause` (`reject_cause`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_failed_original
CREATE TABLE IF NOT EXISTS `cdr_vendors_failed_original` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disconnect_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `call_id` (`call_id`) USING BTREE,
  KEY `CLI` (`CLI`) USING BTREE,
  KEY `CLD` (`CLD`) USING BTREE,
  KEY `setup_time` (`setup_time`) USING BTREE,
  KEY `i_vendor` (`i_vendor`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1771647651 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_original
CREATE TABLE IF NOT EXISTS `cdr_vendors_original` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL,
  `connect_time` datetime DEFAULT NULL,
  `disconnect_time` datetime NOT NULL,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quality_mos` float DEFAULT NULL,
  `quality_largest_jb` float DEFAULT NULL,
  `quality_jitter_burst_rate` float DEFAULT NULL,
  `duration` int(10) NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `charge_status` int(11) DEFAULT '0' COMMENT '0-new, 1-processing, 2-done,-1:error; -2: sent but error',
  `call_brandname` int(11) DEFAULT '0' COMMENT '0: Bình thường 1 BrandName',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `call_id` (`call_id`) USING BTREE,
  KEY `CLI` (`CLI`) USING BTREE,
  KEY `CLD` (`CLD`) USING BTREE,
  KEY `i_vendor` (`i_vendor`) USING BTREE,
  KEY `i_customer` (`i_customer`) USING BTREE,
  KEY `charge_status` (`charge_status`,`connect_time`,`i_vendor`) USING BTREE,
  KEY `from_network_ip` (`from_network_ip`) USING BTREE,
  KEY `setup_time` (`setup_time`) USING BTREE,
  KEY `call_brandname` (`call_brandname`)
) ENGINE=InnoDB AUTO_INCREMENT=913111973 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_original1
CREATE TABLE IF NOT EXISTS `cdr_vendors_original1` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL,
  `connect_time` datetime DEFAULT NULL,
  `disconnect_time` datetime NOT NULL,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quality_mos` float DEFAULT NULL,
  `quality_largest_jb` float DEFAULT NULL,
  `quality_jitter_burst_rate` float DEFAULT NULL,
  `duration` int(10) NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `charge_status` int(11) DEFAULT '0' COMMENT '0-new, 1-processing, 2-done,-1:error; -2: sent but error',
  `call_brandname` int(11) DEFAULT '0' COMMENT '0: Bình thường 1 BrandName',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `call_id` (`call_id`) USING BTREE,
  KEY `CLI` (`CLI`) USING BTREE,
  KEY `CLD` (`CLD`) USING BTREE,
  KEY `i_vendor` (`i_vendor`) USING BTREE,
  KEY `i_customer` (`i_customer`) USING BTREE,
  KEY `charge_status` (`charge_status`,`connect_time`,`i_vendor`) USING BTREE,
  KEY `from_network_ip` (`from_network_ip`) USING BTREE,
  KEY `setup_time` (`setup_time`) USING BTREE,
  KEY `call_brandname` (`call_brandname`)
) ENGINE=InnoDB AUTO_INCREMENT=910705008 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_original2
CREATE TABLE IF NOT EXISTS `cdr_vendors_original2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL,
  `connect_time` datetime DEFAULT NULL,
  `disconnect_time` datetime NOT NULL,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quality_mos` float DEFAULT NULL,
  `quality_largest_jb` float DEFAULT NULL,
  `quality_jitter_burst_rate` float DEFAULT NULL,
  `duration` int(10) NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `charge_status` int(11) DEFAULT '0' COMMENT '0-new, 1-processing, 2-done,-1:error; -2: sent but error',
  `call_brandname` int(11) DEFAULT '0' COMMENT '0: Bình thường 1 BrandName',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `call_id` (`call_id`) USING BTREE,
  KEY `CLI` (`CLI`) USING BTREE,
  KEY `CLD` (`CLD`) USING BTREE,
  KEY `i_vendor` (`i_vendor`) USING BTREE,
  KEY `i_customer` (`i_customer`) USING BTREE,
  KEY `charge_status` (`charge_status`,`connect_time`,`i_vendor`) USING BTREE,
  KEY `from_network_ip` (`from_network_ip`) USING BTREE,
  KEY `setup_time` (`setup_time`) USING BTREE,
  KEY `call_brandname` (`call_brandname`)
) ENGINE=InnoDB AUTO_INCREMENT=913100370 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.cdr_vendors_vt
CREATE TABLE IF NOT EXISTS `cdr_vendors_vt` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CLI` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CLD` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setup_time` datetime NOT NULL,
  `connect_time` datetime DEFAULT NULL,
  `disconnect_time` datetime NOT NULL,
  `disconnect_cause` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `from_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `des_network_ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quality_mos` float DEFAULT NULL,
  `quality_largest_jb` float DEFAULT NULL,
  `quality_jitter_burst_rate` float DEFAULT NULL,
  `duration` int(10) NOT NULL DEFAULT '0',
  `i_vendor` int(10) unsigned NOT NULL DEFAULT '0',
  `i_customer` int(10) unsigned NOT NULL,
  `call_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `charge_status` int(11) DEFAULT '0' COMMENT '0-new, 1-processing, 2-done,-1:error; -2: sent but error',
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_id` (`call_id`),
  KEY `CLI` (`CLI`),
  KEY `CLD` (`CLD`),
  KEY `i_vendor` (`i_vendor`),
  KEY `i_customer` (`i_customer`),
  KEY `charge_status` (`charge_status`,`connect_time`,`i_vendor`),
  KEY `from_network_ip` (`from_network_ip`),
  KEY `setup_time` (`setup_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2202824 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table sbc.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `i_customer` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `companyname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `firstname` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `midinit` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `lastname` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `addr` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `city` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `state` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `zip` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `country` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `phone1` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `phone2` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `email` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `bcc` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `send_statistics` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `blocked` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `capacity` int(10) unsigned DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`i_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.local_ip
CREATE TABLE IF NOT EXISTS `local_ip` (
  `i_local_ip` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `local_ip` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '127.0.0.1',
  `local_port` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '5060',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`i_local_ip`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.nd91_config
CREATE TABLE IF NOT EXISTS `nd91_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) DEFAULT '0' COMMENT 'Bật/ Tắt  1: Mở, 0 Tắt',
  `config_key` varchar(50) COLLATE utf16_unicode_ci DEFAULT 'DNC' COMMENT 'Mã cấu hình tương ứng: "197", hoặc "DNC"',
  `config_name` varchar(50) COLLATE utf16_unicode_ci DEFAULT NULL,
  `apply_rule` tinyint(4) DEFAULT '0' COMMENT 'Áp dụng cho (1, Viettel, 0 Tất cả các mạng) ',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf16 COLLATE=utf16_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.nd91_quota_config
CREATE TABLE IF NOT EXISTS `nd91_quota_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf16_unicode_ci NOT NULL DEFAULT '0' COMMENT 'Tên hạng thuê bao',
  `subscription_key` varchar(50) COLLATE utf16_unicode_ci NOT NULL DEFAULT '0' COMMENT 'Mã hạng thuê bao',
  `max_call_per_day` int(11) NOT NULL DEFAULT '0' COMMENT 'Số cuộc gọi tối đa trong ngày',
  `max_call_per_month` int(11) NOT NULL DEFAULT '0' COMMENT 'Số cuộc gọi tối đa trong tháng ',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_key` (`subscription_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf16 COLLATE=utf16_unicode_ci COMMENT='Cấu hình sản lượng cuộc gọi  được gọi đi của các hotline VBN';

-- Data exporting was unselected.
-- Dumping structure for table sbc.nd91_time_range_config
CREATE TABLE IF NOT EXISTS `nd91_time_range_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf16_unicode_ci DEFAULT NULL COMMENT 'Tên cấu hình',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(50) COLLATE utf16_unicode_ci DEFAULT NULL COMMENT 'Ghi chú',
  `time_allow` varchar(50) COLLATE utf16_unicode_ci NOT NULL DEFAULT '0' COMMENT '0800-1700,1800-2200',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf16 COLLATE=utf16_unicode_ci COMMENT='Cấu hình thời gian được thực hiện cuộc gọi';

-- Data exporting was unselected.
-- Dumping structure for table sbc.routing
CREATE TABLE IF NOT EXISTS `routing` (
  `i_routing` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `direction` int(10) NOT NULL COMMENT '1:pbx2telco, 2:telco2pbx, 3:on-net, 4-telco2telco',
  `caller` varchar(64) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'SIP:From',
  `callee` varchar(64) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'SIP:To',
  `i_acl` int(10) NOT NULL COMMENT 'trunk chinh',
  `i_acl_backup` int(10) NOT NULL COMMENT 'trunk backup',
  `destination` varchar(128) COLLATE utf8_unicode_ci NOT NULL COMMENT 'trunk ip:port',
  `priority` int(10) NOT NULL DEFAULT '10',
  `i_customer` int(10) NOT NULL,
  `i_vendor` int(10) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  `network` int(10) NOT NULL DEFAULT '1' COMMENT '1 - private, 2 - public',
  `i_sip_profile` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '1 - mpbn, 2 - internet, 3-leadline',
  `status` int(11) DEFAULT '0' COMMENT '0: Active, 1: Temporary disable',
  `auto_detect_blocking` int(11) DEFAULT '1' COMMENT '0: Auto,1:disable',
  PRIMARY KEY (`i_routing`),
  KEY `caller` (`caller`),
  KEY `callee` (`callee`),
  KEY `i_customer` (`i_customer`)
) ENGINE=InnoDB AUTO_INCREMENT=1601 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.sip_profile
CREATE TABLE IF NOT EXISTS `sip_profile` (
  `i_sip_profile` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(1024) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`i_sip_profile`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.tbl_students
CREATE TABLE IF NOT EXISTS `tbl_students` (
  `StudID` int(11) DEFAULT NULL,
  `StudName` varchar(50) COLLATE utf16_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.vendors
CREATE TABLE IF NOT EXISTS `vendors` (
  `i_vendor` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `send_statistics` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `blocked` enum('Y','N') COLLATE utf8_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `i_acl` int(11) NOT NULL COMMENT 'acl tuong ung voi vendor',
  `i_acl_backup` int(11) NOT NULL COMMENT 'acl backup tuong ung voi vendor',
  `i_sip_profile` int(11) DEFAULT NULL,
  `hotline_prefix` varchar(5000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `destination` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`i_vendor`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table sbc.vendors_bk202103
CREATE TABLE IF NOT EXISTS `vendors_bk202103` (
  `i_vendor` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `send_statistics` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `blocked` enum('Y','N') COLLATE utf8_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`i_vendor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.activity
CREATE TABLE IF NOT EXISTS `activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `data_id` int(11) DEFAULT NULL COMMENT 'Current ID',
  `root_id` int(11) DEFAULT '0' COMMENT 'Parent ID (relation ID)',
  `data_table` varchar(500) DEFAULT NULL COMMENT 'Table name',
  `action` varchar(500) DEFAULT NULL COMMENT 'Behavious ( Delete, Update, Lock, Unlock) ',
  `raw_log` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(1000) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `enterprise_number` varchar(50) DEFAULT NULL,
  `hotline_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `hotline_number` (`hotline_number`)
) ENGINE=InnoDB AUTO_INCREMENT=450 DEFAULT CHARSET=utf8 COMMENT='Log activity';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.api_servers
CREATE TABLE IF NOT EXISTS `api_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_url` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn truy xuất',
  `port` int(11) DEFAULT '80' COMMENT 'Cổng truy xuất (mặc định 80)',
  `active` tinyint(4) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cấu hình lựa chọn server ';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.call_fee_config
CREATE TABLE IF NOT EXISTS `call_fee_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) DEFAULT NULL COMMENT 'id bảng services_config',
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '1:noi mang, 2:ngoai mang, 3: quoc te ...',
  `from_min` bigint(20) DEFAULT NULL COMMENT 'từ phút thứ',
  `to_min` bigint(20) DEFAULT NULL COMMENT 'đến phút thứ (-1 là vô hạn)',
  `call_fees` double DEFAULT NULL COMMENT 'cước (VND)',
  `status` int(2) DEFAULT '0' COMMENT '0: Bình thường, 1: Tạm khóa, 2:Hủy ',
  `call_type` int(1) DEFAULT NULL COMMENT '0 : outbound, 1: internal mobi, 2: divert mobi',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `priority` int(2) DEFAULT NULL COMMENT 'độ ưu tiên, tránh trường hợp nhiều khoảng trùng nhau',
  PRIMARY KEY (`id`),
  KEY `services_id` (`service_config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=497 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='cấu hình cước cuộc gọi';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.call_fee_cycle_status
CREATE TABLE IF NOT EXISTS `call_fee_cycle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int(11) NOT NULL COMMENT '1:noi mang, 2:ngoai mang, 3: quoc te ...',
  `call_type` int(11) NOT NULL COMMENT '0 : outbound, 1: internal mobi, 2: divert mobi',
  `cycle_from` datetime NOT NULL,
  `cycle_to` datetime NOT NULL,
  `total_duration` bigint(20) NOT NULL COMMENT 'phut',
  `total_amount` double NOT NULL COMMENT 'tong tien vnd',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `cycle_from` (`cycle_from`),
  KEY `cycle_to` (`cycle_to`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.cdr_activity
CREATE TABLE IF NOT EXISTS `cdr_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_number` varchar(50) NOT NULL DEFAULT '0',
  `cdr` varchar(1000) NOT NULL,
  `action` varchar(250) NOT NULL,
  `user_id` int(11) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=1425 DEFAULT CHARSET=latin1 COMMENT='cdr_activity';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.charge_fee_limit
CREATE TABLE IF NOT EXISTS `charge_fee_limit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `limit_amount` int(11) DEFAULT '0' COMMENT '0 hoặc không tồn tại là mặc định,  >0 sẽ thực hiện limit ',
  `actual_limit_amount` int(11) DEFAULT '0',
  `over_quota_status` int(11) DEFAULT '1' COMMENT '0:over ,1:nomal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thiết lập hạn mức cước cho từng thuê bao';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.charge_log
CREATE TABLE IF NOT EXISTS `charge_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1: sub, 2:call, 3:sms',
  `event_source` int(11) DEFAULT NULL,
  `event_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge_session_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số hiển thị',
  `called_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đích',
  `hotline_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số Hotline',
  `enterprise_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đại diẹn',
  `event_occur_time` datetime DEFAULT NULL COMMENT 'Thời điểm phát sinh cước',
  `charge_time` datetime DEFAULT NULL,
  `amount` int(11) DEFAULT NULL COMMENT 'so tien charge',
  `count` bigint(20) DEFAULT NULL COMMENT 'so giay goi',
  `total_count` bigint(20) DEFAULT NULL COMMENT 'tong so giay',
  `total_amount` int(11) DEFAULT NULL COMMENT 'tong so tien',
  `charge_status` int(11) DEFAULT NULL COMMENT '1 thanh cong, 0: khong thanh cong',
  `charge_result` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'result code tra ve tu gw',
  `charge_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `retry_times` int(11) DEFAULT '0' COMMENT 'so lan charge',
  `insert_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `retry_after` datetime DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `display_num` (`display_num`),
  KEY `called_num` (`called_num`),
  KEY `hotline_num` (`hotline_num`),
  KEY `enterprise_num` (`enterprise_num`),
  KEY `event_occur_time` (`event_occur_time`),
  KEY `cus_id` (`cus_id`),
  KEY `charge_time` (`charge_time`),
  KEY `charge_result` (`charge_result`),
  KEY `insert_time` (`insert_time`)
) ENGINE=InnoDB AUTO_INCREMENT=126052941 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.charge_log_409
CREATE TABLE IF NOT EXISTS `charge_log_409` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1: sub, 2:call, 3:sms',
  `event_source` int(11) DEFAULT NULL,
  `event_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge_session_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số hiển thị',
  `called_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đích',
  `hotline_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số Hotline',
  `enterprise_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đại diẹn',
  `event_occur_time` datetime DEFAULT NULL COMMENT 'Thời điểm phát sinh cước',
  `charge_time` datetime DEFAULT NULL,
  `amount` int(11) DEFAULT NULL COMMENT 'so tien charge',
  `count` bigint(20) DEFAULT NULL COMMENT 'so giay goi',
  `total_count` bigint(20) DEFAULT NULL COMMENT 'tong so giay',
  `total_amount` int(11) DEFAULT NULL COMMENT 'tong so tien',
  `charge_status` int(11) DEFAULT NULL COMMENT '1 thanh cong, 0: khong thanh cong',
  `charge_result` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'result code tra ve tu gw',
  `charge_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `retry_times` int(11) DEFAULT '0' COMMENT 'so lan charge',
  `insert_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `retry_after` datetime DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `display_num` (`display_num`),
  KEY `called_num` (`called_num`),
  KEY `hotline_num` (`hotline_num`),
  KEY `enterprise_num` (`enterprise_num`),
  KEY `event_occur_time` (`event_occur_time`),
  KEY `cus_id` (`cus_id`),
  KEY `charge_time` (`charge_time`),
  KEY `charge_result` (`charge_result`)
) ENGINE=InnoDB AUTO_INCREMENT=125952074 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.charge_log_month
CREATE TABLE IF NOT EXISTS `charge_log_month` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1: sub, 2:call, 3:sms',
  `event_source` int(11) DEFAULT NULL,
  `event_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge_session_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số hiển thị',
  `called_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đích',
  `hotline_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số Hotline',
  `enterprise_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đại diẹn',
  `event_occur_time` datetime DEFAULT NULL COMMENT 'Thời điểm phát sinh cước',
  `charge_time` datetime DEFAULT NULL,
  `amount` int(11) DEFAULT NULL COMMENT 'so tien charge',
  `count` bigint(20) DEFAULT NULL COMMENT 'so giay goi',
  `total_count` bigint(20) DEFAULT NULL COMMENT 'tong so giay',
  `total_amount` int(11) DEFAULT NULL COMMENT 'tong so tien',
  `charge_status` int(11) DEFAULT NULL COMMENT '1 thanh cong, 0: khong thanh cong',
  `charge_result` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'result code tra ve tu gw',
  `charge_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `retry_times` int(11) DEFAULT '0' COMMENT 'so lan charge',
  `insert_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `retry_after` datetime DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `display_num` (`display_num`),
  KEY `called_num` (`called_num`),
  KEY `hotline_num` (`hotline_num`),
  KEY `enterprise_num` (`enterprise_num`),
  KEY `event_occur_time` (`event_occur_time`),
  KEY `cus_id` (`cus_id`),
  KEY `charge_time` (`charge_time`),
  KEY `charge_result` (`charge_result`),
  KEY `insert_time` (`insert_time`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.charge_log_vt
CREATE TABLE IF NOT EXISTS `charge_log_vt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1: sub, 2:call, 3:sms',
  `event_source` int(11) DEFAULT NULL,
  `event_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge_session_id` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số hiển thị',
  `called_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đích',
  `hotline_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số Hotline',
  `enterprise_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số đại diẹn',
  `event_occur_time` datetime DEFAULT NULL COMMENT 'Thời điểm phát sinh cước',
  `charge_time` datetime DEFAULT NULL,
  `amount` int(11) DEFAULT NULL COMMENT 'so tien charge',
  `count` bigint(20) DEFAULT NULL COMMENT 'so giay goi',
  `total_count` bigint(20) DEFAULT NULL COMMENT 'tong so giay',
  `total_amount` int(11) DEFAULT NULL COMMENT 'tong so tien',
  `charge_status` int(11) DEFAULT NULL COMMENT '1 thanh cong, 0: khong thanh cong',
  `charge_result` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'result code tra ve tu gw',
  `charge_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `retry_times` int(11) DEFAULT '0' COMMENT 'so lan charge',
  `insert_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `retry_after` datetime DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `display_num` (`display_num`),
  KEY `called_num` (`called_num`),
  KEY `hotline_num` (`hotline_num`),
  KEY `enterprise_num` (`enterprise_num`),
  KEY `event_occur_time` (`event_occur_time`),
  KEY `cus_id` (`cus_id`),
  KEY `charge_time` (`charge_time`),
  KEY `charge_result` (`charge_result`),
  KEY `insert_time` (`insert_time`)
) ENGINE=InnoDB AUTO_INCREMENT=125485719 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Customer ID ',
  `companyname` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cus_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enterprise_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstname` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midinit` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taxcode` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `licenseno` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateofissue` datetime DEFAULT NULL,
  `issuedby` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone1` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone2` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bcc` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_statistics` int(11) DEFAULT NULL,
  `amid` int(11) DEFAULT NULL,
  `amname` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agencyid` int(11) DEFAULT NULL COMMENT 'Mã đơn vị',
  `regionid` int(11) DEFAULT NULL COMMENT 'Mã khu vực',
  `blocked` int(11) DEFAULT '0' COMMENT '0: Mở, 1:Chặn, 2: Hủy dịch vụ',
  `capacity` int(11) DEFAULT NULL,
  `description` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_auth` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_proxy` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `ip_auth_backup` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_proxy_backup` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pause_state` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '00' COMMENT '10  admin chặn 2 chiều, 11 admin chặn gọi ra, admin 12. Chặn gọi vào. 13:BCCS, 14 VCONNECT PAYMENT',
  `server_profile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '10' COMMENT 'ID của server khách đang chạy, ID này được constant ở code, và check nếu bằng constant thì đang hoạt động ở server đó',
  PRIMARY KEY (`id`),
  UNIQUE KEY `enterprise_number` (`enterprise_number`),
  KEY `id` (`id`),
  KEY `service_id` (`service_id`),
  KEY `blocked` (`blocked`),
  KEY `account_id` (`account_id`),
  KEY `server_profile` (`server_profile`)
) ENGINE=InnoDB AUTO_INCREMENT=410 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khách hàng (theo docs của Tùng)  cus_name\r\n    companyname      firstname     midinit     lastname     addr     city     state     zip    country     phone1     phone2     email     bcc     send_statistics     blocked     capacity     description\r\nBổ sung: service_id';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.customers_log_states
CREATE TABLE IF NOT EXISTS `customers_log_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `enterprise_number` varchar(50) DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL COMMENT 'ID khahcs hang',
  `status` int(11) DEFAULT NULL COMMENT '0: Mở, 1:Chặn, 2: Hủy dịch vụ',
  `reason` varchar(250) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Người xử lý',
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `cus_id` (`cus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8 COMMENT='Bảng ghi nhận lịch sử thay đổi trạng thái của khách hàng ';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.entity
CREATE TABLE IF NOT EXISTS `entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `entity_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `entity_group` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity_key` (`entity_key`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cấu hình quyền ';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.hot_line_config
CREATE TABLE IF NOT EXISTS `hot_line_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cus_id` int(11) NOT NULL COMMENT 'Customer ID',
  `hotline_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(11) DEFAULT '0' COMMENT '0: Đang hoạt động, 1: Tạm ngưng, 2:Hủy',
  `init_charge` int(11) NOT NULL DEFAULT '0' COMMENT '0: la chua thu tien khoi tao',
  `sip_config` datetime DEFAULT NULL COMMENT 'Null, chưa thiết lập, Date: Đã thiết lập ',
  `hotline_type_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `pause_state` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '10',
  `last_charge_date` datetime DEFAULT NULL,
  `use_brand_name` int(11) DEFAULT '0' COMMENT '0 hoặc NULL, chưa thiết lập, 1: Đang dùng cho Brandname',
  `max_ccu` int(11) DEFAULT '0' COMMENT 'Số cuộc gọi tối đa, 0: mặc định',
  `brand_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Brandname nếu use_brand_name =1',
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `hotline_number` (`hotline_number`),
  KEY `sip_config` (`sip_config`),
  KEY `status` (`status`),
  KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB AUTO_INCREMENT=568 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.number_routing
CREATE TABLE IF NOT EXISTS `number_routing` (
  `NUMBER_ROUTING_ID` int(11) NOT NULL,
  `ISDN` bigint(20) NOT NULL,
  `CURR_OPERATOR` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DESCRIPTION` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `LAST_UPDATE_TIME` date DEFAULT NULL,
  `IS_HOME` bit(1) DEFAULT NULL,
  `STATUS` bit(1) DEFAULT NULL,
  PRIMARY KEY (`NUMBER_ROUTING_ID`),
  KEY `ISDN` (`ISDN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.prefix_type
CREATE TABLE IF NOT EXISTS `prefix_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prefix_called` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int(11) NOT NULL COMMENT '1:noi mang, 2:ngoai mang, 3: quoc te ...',
  `priority` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='bảng mapping giữa đầu số đích và loại cuộc gọi';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.prefix_type_group
CREATE TABLE IF NOT EXISTS `prefix_type_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='Nhóm các loại cước cùng mạng';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.prefix_type_name
CREATE TABLE IF NOT EXISTS `prefix_type_name` (
  `prefix_type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`prefix_type_id`),
  KEY `prefix_group` (`prefix_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.quantity_config
CREATE TABLE IF NOT EXISTS `quantity_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) DEFAULT NULL COMMENT 'id bảng services_config',
  `min` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `description` varchar(5000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(11) DEFAULT '0' COMMENT '0: Bình thường, 1, Tạm khóa, 2 Hủy ',
  `type` int(11) DEFAULT '0' COMMENT '0: noi mang, 1: trong nuoc',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.quantity_subcriber
CREATE TABLE IF NOT EXISTS `quantity_subcriber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_subcriber_id` int(11) DEFAULT NULL COMMENT 'mapping serrvice_subcriber',
  `quantity_config_id` int(11) DEFAULT NULL COMMENT 'mapping quantity_config',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(11) DEFAULT NULL COMMENT '0: Kích hoạt, 1: Tạm ngưng, 2 Hủy',
  `resub` int(11) DEFAULT '0' COMMENT '0: khong resub, 1:co',
  `begin_use_date` timestamp NULL DEFAULT NULL COMMENT 'thoi gian bat dau su dung',
  `init_charge` int(11) DEFAULT '0' COMMENT '0: chua charge lan dau, 1: da charge lan dau',
  `last_charge_date` datetime DEFAULT NULL COMMENT 'lan dau tien luu NULL',
  `last_charge_sub_status` int(11) DEFAULT NULL COMMENT 'lan dau tien luu NULL',
  `last_try_charge_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.quantity_subcriber_cycle_status
CREATE TABLE IF NOT EXISTS `quantity_subcriber_cycle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cycle_from` datetime NOT NULL,
  `cycle_to` datetime NOT NULL,
  `reserve_duration` bigint(20) NOT NULL,
  `total_reserve` bigint(20) NOT NULL,
  `activated` int(11) NOT NULL COMMENT '0: chua dung, 1: da dung',
  `type` int(11) DEFAULT NULL COMMENT '0: noi mang, 1: trong nuoc',
  PRIMARY KEY (`id`),
  KEY `cycle_from` (`cycle_from`),
  KEY `cycle_to` (`cycle_to`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.quantity_subcriber_local_cycle_status
CREATE TABLE IF NOT EXISTS `quantity_subcriber_local_cycle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cycle_from` datetime NOT NULL,
  `cycle_to` datetime NOT NULL,
  `total_reserve` bigint(20) NOT NULL,
  `total_amount` bigint(20) NOT NULL,
  `type` int(11) DEFAULT NULL COMMENT '0: noi mang, 1: trong nuoc',
  `is_charge` int(11) DEFAULT NULL COMMENT '0: chua tru tien, 1: da tru tien',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `enterprise_number` (`enterprise_number`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.quantity_subcriber_log
CREATE TABLE IF NOT EXISTS `quantity_subcriber_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_subcriber_id` int(11) DEFAULT NULL,
  `quantity_config_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int(11) DEFAULT NULL COMMENT '0: deactive, 1: active',
  `insert_date` timestamp NULL DEFAULT NULL,
  `begin_use_date` timestamp NULL DEFAULT NULL COMMENT 'thoi gian bat dau su dung',
  `actor_user_id` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT '0' COMMENT '0: noi mang, 1: trong nuoc',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.report_days
CREATE TABLE IF NOT EXISTS `report_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_time` date DEFAULT NULL,
  `report_year` int(11) DEFAULT NULL,
  `report_month` int(11) DEFAULT NULL,
  `month_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `report_day` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `full_time` (`full_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2431 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.report_hour_of_day
CREATE TABLE IF NOT EXISTS `report_hour_of_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hour` varchar(5) COLLATE utf8_unicode_ci DEFAULT '0',
  `minute` varchar(5) COLLATE utf8_unicode_ci DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.report_month
CREATE TABLE IF NOT EXISTS `report_month` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mname` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.report_week
CREATE TABLE IF NOT EXISTS `report_week` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `w` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.report_week_bk202103
CREATE TABLE IF NOT EXISTS `report_week_bk202103` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `w` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.report_year
CREATE TABLE IF NOT EXISTS `report_year` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `y` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `role_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.role_entity
CREATE TABLE IF NOT EXISTS `role_entity` (
  `entity_id` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity_id_role_id` (`entity_id`,`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cấu hình quyền ';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.send_mail
CREATE TABLE IF NOT EXISTS `send_mail` (
  `id` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  `customer` int(11) DEFAULT NULL,
  `enterprise_num` int(11) DEFAULT NULL,
  `email` int(11) DEFAULT NULL,
  `money` int(11) DEFAULT NULL,
  `c_limits` int(11) DEFAULT NULL,
  `percent` int(11) DEFAULT NULL,
  `phone1` int(11) DEFAULT NULL,
  `c_status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `c_status` (`c_status`),
  KEY `enterprise_num` (`enterprise_num`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.services_apps_linked
CREATE TABLE IF NOT EXISTS `services_apps_linked` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cus_id` int(11) NOT NULL,
  `service_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'BCCS Key',
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(4) DEFAULT '1' COMMENT '1: Kích hoat, 0: Tạm ngưng. hủy',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cus_id_app_key` (`cus_id`,`service_key`),
  KEY `cus_id` (`cus_id`),
  KEY `app_key` (`service_key`),
  KEY `enterprise_number` (`enterprise_number`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình dịch vụ bổ sung';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_config
CREATE TABLE IF NOT EXISTS `service_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'tên dịch vụ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type` int(11) DEFAULT '0' COMMENT '0: tron goi, 1: tinh theo so user',
  `status` int(11) DEFAULT '0',
  `product_code` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_code` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cấu hình các gói dịch vụ';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_config_hotline_price
CREATE TABLE IF NOT EXISTS `service_config_hotline_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) DEFAULT NULL,
  `from_hotline_num` int(11) DEFAULT NULL,
  `to_hotline_num` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL COMMENT 'gia thue bao',
  `init_price` double DEFAULT NULL COMMENT 'gia khoi tao',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int(2) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_config_price
CREATE TABLE IF NOT EXISTS `service_config_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) DEFAULT NULL,
  `from_user` int(11) DEFAULT NULL,
  `to_user` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_option_price
CREATE TABLE IF NOT EXISTS `service_option_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) NOT NULL,
  `type` int(11) NOT NULL COMMENT '1: extension, 2: call_record, 3: data_storage, 4: api, 5: softphone_3c',
  `from` int(11) NOT NULL,
  `to` int(11) NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` double NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0: Bình thường, 1 tạm ngưng, 2 Xóa',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_option_subcriber
CREATE TABLE IF NOT EXISTS `service_option_subcriber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_subcriber_id` int(11) NOT NULL,
  `status` int(11) DEFAULT '0' COMMENT '0: Bình thường, 1: Tạm ngưng, 2 xóa',
  `begin_charge_date` datetime DEFAULT NULL COMMENT 'thoi gian bat dau tinh tien cuoc sub',
  `last_charge_date` datetime DEFAULT NULL,
  `last_try_charge_date` datetime DEFAULT NULL,
  `last_charge_sub_status` int(11) DEFAULT NULL,
  `extension_count` int(11) NOT NULL DEFAULT '0',
  `call_record_storage_count` int(11) NOT NULL DEFAULT '0',
  `data_storage_count` int(11) NOT NULL DEFAULT '0',
  `api_count` int(11) NOT NULL DEFAULT '0',
  `api_rpm_count` int(11) NOT NULL DEFAULT '0',
  `softphone_3c_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_option_subcriber_log
CREATE TABLE IF NOT EXISTS `service_option_subcriber_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_subcriber_id` int(11) NOT NULL,
  `status` int(11) DEFAULT '1' COMMENT '1 - active, 0 - deactive',
  `begin_charge_date` datetime DEFAULT NULL COMMENT 'thoi gian bat dau tinh tien cuoc sub',
  `last_charge_date` datetime DEFAULT NULL,
  `last_try_charge_date` datetime DEFAULT NULL,
  `last_charge_sub_status` int(11) DEFAULT NULL,
  `extension_count` int(11) NOT NULL DEFAULT '0',
  `call_record_storage_count` int(11) NOT NULL DEFAULT '0',
  `data_storage_count` int(11) NOT NULL DEFAULT '0',
  `api_count` int(11) NOT NULL DEFAULT '0',
  `api_rpm_count` int(11) NOT NULL DEFAULT '0',
  `softphone_3c_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_prefix_type
CREATE TABLE IF NOT EXISTS `service_prefix_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) NOT NULL COMMENT 'mapping voi bang service_config',
  `prefix_type_id` int(11) NOT NULL COMMENT 'mapping voi bang prefix_type_name',
  `description` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `prefix_caller` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Prefix cho đầu số hotline Caller Prefix',
  `prefix_called` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Prefix đầu số bị gọi Called Prefix',
  `prefix_caller_match_switch` int(11) NOT NULL COMMENT 'kiểu match prefix_caller 0: bình thường/1: phủ định',
  `prefix_called_match_switch` int(11) NOT NULL COMMENT 'kiểu match prefix_called 0: bình thường/1: phủ định',
  `prefix_match_constraint` int(11) NOT NULL COMMENT '0: khong co, 1: prefix caller giong called, 2 prefix caller khac prefix called',
  `priority` int(11) NOT NULL,
  `charge_block_type` int(11) NOT NULL COMMENT '0: 1s+1; 1:6s+1; 2: 1 phut + 1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_config_id_priority` (`service_config_id`,`priority`),
  KEY `service_config_id` (`service_config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_subcriber
CREATE TABLE IF NOT EXISTS `service_subcriber` (
  `id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `service_config_id` int(11) DEFAULT NULL,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_agent` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int(11) DEFAULT '0' COMMENT '0: kich hoat',
  `begin_charge_date` datetime DEFAULT NULL COMMENT 'thoi gian bat dau tinh tien cuoc sub',
  `expired_contract_date` datetime DEFAULT NULL COMMENT 'Ngày hết hạn hợp đồng',
  `last_charge_date` datetime DEFAULT NULL,
  `last_charge_sub_status` int(11) DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL COMMENT 'ID của khách hàng',
  `last_try_charge` datetime DEFAULT NULL,
  `cus_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên Khách Hàng',
  `cus_manager` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đại diện khách hàng',
  `position` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chức vụ',
  `license_no` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Giấy phép kinh doanh',
  `date_of_issue` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ngày cấp',
  `issued_by` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'đơn vị cấp',
  `tax_code` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã số thuế',
  `cus_tel_num` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số hotline tổng đài khách hàng',
  `cus_email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'email',
  `admin_tel` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'điện thoại phụ trách',
  `admin_email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email phụ trách',
  `warning_range` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mức cảnh báo cước',
  `am_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã AM',
  `am_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên AM',
  `agency_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã đơn vị kinh doanh',
  `agency_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên đơn vị kinh doanh',
  `region_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã khu vực',
  `region_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên khu vực',
  `package_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên gói cước',
  `user_number` int(11) DEFAULT NULL COMMENT 'số lượng user tối đa',
  `cust_address` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'địa chỉ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `enterprise_number` (`enterprise_number`),
  KEY `status` (`status`),
  KEY `service_config_id` (`service_config_id`),
  KEY `begin_charge_date` (`begin_charge_date`),
  KEY `last_charge_date` (`last_charge_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='bang mapping giua services_config va account';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.service_subcriber_log
CREATE TABLE IF NOT EXISTS `service_subcriber_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `service_config_id` int(11) DEFAULT NULL,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_agent` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int(11) DEFAULT '1' COMMENT '1 - active, 0 - deactive',
  `begin_charge_date` datetime DEFAULT NULL COMMENT 'thoi gian bat dau tinh tien cuoc sub',
  `last_charge_date` datetime DEFAULT NULL,
  `last_charge_sub_status` int(11) DEFAULT NULL,
  `cus_id` int(11) DEFAULT NULL COMMENT 'ID của khách hàng',
  `cus_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên Khách Hàng',
  `cus_manager` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đại diện khách hàng',
  `position` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chức vụ',
  `license_no` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Giấy phép kinh doanh',
  `date_of_issue` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ngày cấp',
  `issued_by` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đơn vị cấp',
  `tax_code` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cus_tel_num` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số hotline tổng đài khách hàng',
  `cus_email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'email',
  `admin_tel` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'điện thoại phụ trách',
  `admin_email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'email phụ trách',
  `warning_range` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mức cảnh báo cước',
  `am_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã AM',
  `am_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên AM',
  `agency_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã đơn vị kinh doanh',
  `agency_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên đơn vị kinh doanh',
  `region_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mã khu vực',
  `region_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên khu vực',
  `package_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tên gói cước',
  `user_number` int(11) DEFAULT NULL COMMENT 'số lượng user tối đa',
  `action_type` int(2) DEFAULT NULL COMMENT '1: đăng ký mới, 2: hủy hotline, 3: sửa license',
  `result` int(2) DEFAULT NULL COMMENT '1: thanh cong, 2: that bai',
  `cust_address` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'địa chỉ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='bang mapping giua services_config va account';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.sms_fee_config
CREATE TABLE IF NOT EXISTS `sms_fee_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_config_id` int(11) DEFAULT NULL COMMENT 'id bảng services_config',
  `type` int(11) NOT NULL COMMENT '1:noi mang, 2:ngoai mang, 3: quoc te ...',
  `from_sms` bigint(20) DEFAULT NULL COMMENT 'từ sms từ',
  `to_sms` bigint(20) DEFAULT NULL COMMENT 'đến sms (-1 là vô hạn)',
  `sms_fees` double DEFAULT NULL COMMENT 'cước (VND)',
  `sms_type` int(1) DEFAULT NULL COMMENT '0 : MO, 1: MT',
  `priority` int(2) DEFAULT NULL COMMENT 'độ ưu tiên, tránh trường hợp nhiều khoảng trùng nhau',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` int(11) DEFAULT '0' COMMENT '0: Bình thường, 1: Tạm khóa, 2: Hủy ',
  PRIMARY KEY (`id`),
  KEY `services_id` (`service_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='cấu hình cước cuộc gọi';

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.sms_fee_cycle_status
CREATE TABLE IF NOT EXISTS `sms_fee_cycle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int(11) NOT NULL COMMENT '1:noi mang, 2:ngoai mang, 3: quoc te ...',
  `cycle_from` datetime NOT NULL,
  `cycle_to` datetime NOT NULL,
  `total_count` bigint(20) NOT NULL,
  `total_amount` bigint(20) NOT NULL COMMENT 'tong tien vnd',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `cycle_from` (`cycle_from`),
  KEY `cycle_to` (`cycle_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.subcharge_fee_cycle_status
CREATE TABLE IF NOT EXISTS `subcharge_fee_cycle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enterprise_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cycle_from` datetime NOT NULL,
  `cycle_to` datetime NOT NULL,
  `total_amount` bigint(20) NOT NULL COMMENT 'tong tien vnd',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_number` (`enterprise_number`),
  KEY `cycle_from` (`cycle_from`),
  KEY `cycle_to` (`cycle_to`)
) ENGINE=InnoDB AUTO_INCREMENT=890 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.
-- Dumping structure for table vt_vnmcms.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `role` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `api_token` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=443 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
