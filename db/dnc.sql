CREATE TABLE `dnc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `msisdn` varchar(50) DEFAULT NULL COMMENT 'Thuê bao KH',
  `telco` varchar(45) DEFAULT NULL COMMENT 'Số định tuyến nhà mạng: - 01: MobiFone\n- 02: VinaPhone\n- 04: Viettel\n- 05: VietnamMobile\n- 07: Gtel Mobile\n- 08: Đông Dương Telecom',
  `shortcode` varchar(45) DEFAULT NULL COMMENT 'Đầu số',
  `info` varchar(500) DEFAULT NULL COMMENT 'Nội dung KH nhắn lên hệ thống',
  `mo_time` datetime DEFAULT NULL COMMENT 'Thời gian KH nhắn lên hệ thống (dd/mm/yyyy hh24:mi:ss)',
  `cmd_code` varchar(45) DEFAULT NULL COMMENT 'Giá trị cho biết khách hàng này đăng ký hay rút khỏi DNC\n- DK: khách hàng đăng ký mới vào DNC.\n- HUY: khách hàng hủy đăng ký khỏi DNC',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `msisdn` (`msisdn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
