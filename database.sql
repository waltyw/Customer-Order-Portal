-- ============================================================
--  Beebizzi Customer Portal — Master Database Schema
--  Version: 2.0
--  Safe to run on both fresh and existing databases.
--  Uses IF NOT EXISTS / INSERT IGNORE throughout.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Core tables ───────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `users` (
  `id`              INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `email`           VARCHAR(255)     NOT NULL UNIQUE,
  `password_hash`   VARCHAR(255)     NOT NULL,
  `name`            VARCHAR(255)     NOT NULL,
  `company`         VARCHAR(255)     DEFAULT NULL,
  `phone`           VARCHAR(50)      DEFAULT NULL,
  `website_url`     VARCHAR(500)     DEFAULT NULL,
  `role`            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `xero_contact_id` VARCHAR(100)     DEFAULT NULL,
  `is_active`       TINYINT(1)       NOT NULL DEFAULT 1,
  `show_invoices`   TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(100) NOT NULL UNIQUE,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`        VARCHAR(255) NOT NULL,
  `ip_address`   VARCHAR(45)  NOT NULL,
  `attempted_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email_time` (`email`, `attempted_at`),
  INDEX `idx_ip_time`    (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tickets` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference`   VARCHAR(20)  NOT NULL UNIQUE,
  `user_id`     INT UNSIGNED NOT NULL,
  `subject`     VARCHAR(500) NOT NULL,
  `priority`    ENUM('low','medium','high','urgent')                          NOT NULL DEFAULT 'medium',
  `status`      ENUM('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  `category`    ENUM('billing','technical','general','account')               NOT NULL DEFAULT 'general',
  `website_url` VARCHAR(500) DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id`   INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `message`     TEXT         NOT NULL,
  `is_internal` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_attachments` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_message_id`   INT UNSIGNED NOT NULL,
  `filename`            VARCHAR(255) NOT NULL,
  `original_filename`   VARCHAR(255) NOT NULL,
  `mime_type`           VARCHAR(100) NOT NULL,
  `file_size`           INT UNSIGNED NOT NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_message_id`) REFERENCES `ticket_messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id`              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED    NOT NULL,
  `xero_invoice_id` VARCHAR(100)    DEFAULT NULL,
  `invoice_number`  VARCHAR(100)    NOT NULL,
  `status`          ENUM('draft','authorised','paid','voided','overdue') NOT NULL DEFAULT 'authorised',
  `subtotal`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `vat_amount`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `amount_paid`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `amount_due`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `currency`        VARCHAR(3)      NOT NULL DEFAULT 'GBP',
  `issue_date`      DATE            DEFAULT NULL,
  `due_date`        DATE            DEFAULT NULL,
  `line_items`      JSON            DEFAULT NULL,
  `notes`           TEXT            DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id`                        INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `user_id`                   INT UNSIGNED  NOT NULL,
  `invoice_id`                INT UNSIGNED  DEFAULT NULL,
  `stripe_session_id`         VARCHAR(255)  DEFAULT NULL,
  `stripe_payment_intent_id`  VARCHAR(255)  DEFAULT NULL,
  `amount`                    DECIMAL(10,2) NOT NULL,
  `currency`                  VARCHAR(3)    NOT NULL DEFAULT 'GBP',
  `method`                    ENUM('stripe','bank_transfer') NOT NULL DEFAULT 'stripe',
  `status`                    ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `reference`                 VARCHAR(100)  DEFAULT NULL,
  `notes`                     TEXT          DEFAULT NULL,
  `created_at`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50)  DEFAULT NULL,
  `entity_id`   INT UNSIGNED DEFAULT NULL,
  `details`     JSON         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user`    (`user_id`),
  INDEX `idx_entity`  (`entity_type`, `entity_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_websites` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `url`        VARCHAR(500) NOT NULL,
  `label`      VARCHAR(100) DEFAULT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT         DEFAULT NULL,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_status` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_name` VARCHAR(100) NOT NULL,
  `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status`       ENUM('operational','degraded','outage','maintenance') NOT NULL DEFAULT 'operational',
  `message`      TEXT         DEFAULT NULL,
  `updated_by`   INT UNSIGNED DEFAULT NULL,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faqs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `question`   VARCHAR(500) NOT NULL,
  `answer`     TEXT         NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Safely add columns to existing tables (MySQL compatible) ──────────────────

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `website_url` VARCHAR(500) DEFAULT NULL AFTER `phone`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'website_url');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `show_invoices` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'show_invoices');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `tickets` ADD COLUMN `website_url` VARCHAR(500) DEFAULT NULL AFTER `category`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'website_url');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Remove duplicate service_status rows if any ───────────────────────────────

DELETE s1 FROM service_status s1
INNER JOIN service_status s2
WHERE s1.id > s2.id AND s1.service_name = s2.service_name;

-- ── Default data ──────────────────────────────────────────────────────────────

-- Default admin account — password is "password" — CHANGE IMMEDIATELY after login
INSERT IGNORE INTO `users` (`email`, `password_hash`, `name`, `role`) VALUES (
  'admin@beebizzi.co.uk',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Admin',
  'admin'
);

-- Portal settings
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
  ('app_name',         'Beebizzi Customer Portal'),
  ('primary_color',    '#2563eb'),
  ('primary_dark',     '#1d4ed8'),
  ('sidebar_bg',       '#0f172a'),
  ('sidebar_text',     '#94a3b8'),
  ('sidebar_active',   '#2563eb'),
  ('body_bg',          '#f8fafc'),
  ('text_color',       '#1e293b'),
  ('text_muted',       '#64748b'),
  ('card_bg',          '#ffffff'),
  ('support_email',    ''),
  ('logo_ext',         'png'),
  ('favicon_ext',      'png'),
  ('currency_symbol',  '£'),
  ('font_family',      'Inter'),
  ('invoices_enabled', '1'),
  ('xero_client_id',       ''),
  ('xero_client_secret',   ''),
  ('xero_redirect_uri',    ''),
  ('xero_access_token',    ''),
  ('xero_refresh_token',   ''),
  ('xero_token_expires_at','0'),
  ('xero_tenant_id',       ''),
  ('xero_tenant_name',     ''),
  ('xero_oauth_state',     ''),
  ('xero_last_sync',       '');

-- Default service status entries
INSERT IGNORE INTO `service_status` (`service_name`, `sort_order`, `status`) VALUES
  ('Web Hosting',     1, 'operational'),
  ('Email',           2, 'operational'),
  ('DNS',             3, 'operational'),
  ('Customer Portal', 4, 'operational'),
  ('Support',         5, 'operational');

SET FOREIGN_KEY_CHECKS = 1;

-- ── Done ──────────────────────────────────────────────────────────────────────
-- Default login: admin@beebizzi.co.uk / password
-- IMPORTANT: Change the admin password immediately after first login
