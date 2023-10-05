CREATE TABLE `dnc` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`msisdn` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Thuê bao KH',
	`telco` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Số định tuyến nhà mạng: - 01: MobiFone\\n- 02: VinaPhone\\n- 04: Viettel\\n- 05: VietnamMobile\\n- 07: Gtel Mobile\\n- 08: Đông Dương Telecom',
	`shortcode` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Đầu số',
	`info` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Nội dung KH nhắn lên hệ thống',
	`mo_time` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Thời gian KH nhắn lên hệ thống (dd/mm/yyyy hh24:mi:ss)',
	`cmd_code` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Giá trị cho biết khách hàng này đăng ký hay rút khỏi DNC\\n- DK: khách hàng đăng ký mới vào DNC.\\n- HUY: khách hàng hủy đăng ký khỏi DNC',
	`updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `msisdn` (`msisdn`),
	INDEX `cmd_code` (`cmd_code`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=4
;
