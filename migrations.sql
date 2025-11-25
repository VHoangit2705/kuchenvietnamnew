-- Thực hiện migration (up)
ALTER TABLE `users`
ADD COLUMN `password_changed_at` TIMESTAMP NULL DEFAULT NULL AFTER `password`;

-- Rollback migration (down)
ALTER TABLE `users`
DROP COLUMN `password_changed_at`;
-- Tạo bảng user_device_tokens
CREATE TABLE `user_device_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `device_fingerprint` VARCHAR(255) NOT NULL,
    `device_token` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `browser_info` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `status` VARCHAR(20) NOT NULL DEFAULT 'approved',
    `approval_requested_at` TIMESTAMP NULL DEFAULT NULL,
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- unique giống trong migration
    UNIQUE KEY `user_device_tokens_device_fingerprint_unique` (`device_fingerprint`),
    UNIQUE KEY `user_device_tokens_device_token_unique` (`device_token`),

    -- index giống trong migration
    KEY `user_device_tokens_user_id_index` (`user_id`),
    KEY `user_device_tokens_device_token_index` (`device_token`),
    KEY `user_device_tokens_device_fingerprint_index` (`device_fingerprint`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- Rollback tương đương hàm down()
DROP TABLE IF EXISTS `user_device_tokens`;

-- Tạo bảng warranty_anomaly_alerts
CREATE TABLE `warranty_anomaly_alerts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `branch` VARCHAR(255) NOT NULL,
    `staff_name` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `staff_count` INT NOT NULL,              -- Số ca nhân viên này nhận
    `total_count` INT NOT NULL,              -- Tổng số ca của kho
    `staff_count_in_branch` INT NOT NULL,    -- Số nhân viên trong kho
    `average_count` DECIMAL(10,2) NOT NULL,  -- Số ca trung bình
    `threshold` DECIMAL(10,2) NOT NULL,      -- Ngưỡng cảnh báo (average * 2.5)
    `alert_level` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
    `resolved_by` BIGINT UNSIGNED NULL,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,

    PRIMARY KEY (`id`),

    KEY `warranty_anomaly_alerts_branch_index` (`branch`),
    KEY `warranty_anomaly_alerts_date_index` (`date`),
    KEY `warranty_anomaly_alerts_staff_name_index` (`staff_name`),
    KEY `warranty_anomaly_alerts_is_resolved_index` (`is_resolved`)
)
ENGINE=InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- Rollback tương đương hàm down()
DROP TABLE IF EXISTS `warranty_anomaly_alerts`;

-- Tạo bảng warranty_anomaly_blocks
CREATE TABLE `warranty_anomaly_blocks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_name` VARCHAR(255) NOT NULL,
    `branch` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `blocked_until` TIMESTAMP NOT NULL,          -- Chặn đến khi nào (1 giờ sau)
    `count_when_blocked` INT NOT NULL,          -- Số ca khi bị chặn
    `threshold` DECIMAL(10,2) NOT NULL,         -- Ngưỡng đã vượt
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,  -- Còn hiệu lực không
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,

    PRIMARY KEY (`id`),

    KEY `warranty_anomaly_blocks_staff_name_index` (`staff_name`),
    KEY `warranty_anomaly_blocks_branch_index` (`branch`),
    KEY `warranty_anomaly_blocks_date_index` (`date`),
    KEY `warranty_anomaly_blocks_blocked_until_index` (`blocked_until`),
    KEY `warranty_anomaly_blocks_is_active_index` (`is_active`)
)
ENGINE=InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- Rollback tương đương hàm down()
DROP TABLE IF EXISTS `warranty_anomaly_blocks`;

-- Tạo bảng warranty_overdue_rate_history
CREATE TABLE `warranty_overdue_rate_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_date` DATE NOT NULL,
    `report_type` ENUM('weekly','monthly') NOT NULL,
    `from_date` DATE NOT NULL,
    `to_date` DATE NOT NULL,
    `branch` VARCHAR(255) NULL,
    `staff_received` VARCHAR(255) NULL,
    `tong_tiep_nhan` INT NOT NULL DEFAULT 0,
    `so_ca_qua_han` INT NOT NULL DEFAULT 0,
    `ti_le_qua_han` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `dang_sua_chua` INT NOT NULL DEFAULT 0,
    `cho_khach_hang_phan_hoi` INT NOT NULL DEFAULT 0,
    `da_hoan_tat` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `warranty_overdue_rate_history_report_date_type_index` (`report_date`, `report_type`),
    KEY `warranty_overdue_rate_history_branch_date_index` (`branch`, `report_date`),
    KEY `warranty_overdue_rate_history_staff_date_index` (`staff_received`, `report_date`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- DOWN
DROP TABLE IF EXISTS `warranty_overdue_rate_history`;
