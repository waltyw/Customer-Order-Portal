-- ============================================================
--  E-Commerce Shop Module — Database Schema
--  Safe to run on fresh or existing Beebizzi Portal databases.
--  Run AFTER database.sql has been applied.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Pricing Tiers ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `pricing_tiers` (
  `id`          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)     NOT NULL,
  `description` VARCHAR(500)     DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Product Categories ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_categories` (
  `id`          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `parent_id`   INT UNSIGNED     DEFAULT NULL,
  `name`        VARCHAR(255)     NOT NULL,
  `slug`        VARCHAR(255)     NOT NULL,
  `description` TEXT             DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `product_categories`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `uq_slug` (`slug`),
  INDEX `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Products ──────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `products` (
  `id`                  INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `category_id`         INT UNSIGNED  DEFAULT NULL,
  `sku`                 VARCHAR(100)  NOT NULL,
  `name`                VARCHAR(500)  NOT NULL,
  `slug`                VARCHAR(500)  NOT NULL,
  `description`         TEXT          DEFAULT NULL,
  `stock`               INT           NOT NULL DEFAULT 0,
  `low_stock_threshold` INT           NOT NULL DEFAULT 5,
  `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`          INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `product_categories`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `uq_sku`  (`sku`),
  UNIQUE KEY `uq_slug` (`slug`),
  INDEX `idx_category` (`category_id`),
  FULLTEXT INDEX `ft_search` (`name`, `sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Product Images ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED     NOT NULL,
  `filename`   VARCHAR(255)     NOT NULL,
  `alt_text`   VARCHAR(255)     DEFAULT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1)       NOT NULL DEFAULT 0,
  `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tier-Specific Pricing ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_prices` (
  `id`              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `product_id`      INT UNSIGNED  NOT NULL,
  `pricing_tier_id` INT UNSIGNED  NOT NULL,
  `price`           DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_product_tier` (`product_id`, `pricing_tier_id`),
  FOREIGN KEY (`product_id`)      REFERENCES `products`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`pricing_tier_id`) REFERENCES `pricing_tiers`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Customer Addresses ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED NOT NULL,
  `type`                ENUM('billing','shipping','both') NOT NULL DEFAULT 'both',
  `label`               VARCHAR(100) DEFAULT NULL,
  `company`             VARCHAR(255) DEFAULT NULL,
  `line1`               VARCHAR(255) NOT NULL,
  `line2`               VARCHAR(255) DEFAULT NULL,
  `city`                VARCHAR(100) NOT NULL,
  `county`              VARCHAR(100) DEFAULT NULL,
  `postcode`            VARCHAR(20)  NOT NULL,
  `country`             VARCHAR(100) NOT NULL DEFAULT 'United Kingdom',
  `is_default_billing`  TINYINT(1)   NOT NULL DEFAULT 0,
  `is_default_shipping` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Baskets ───────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `baskets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `basket_items` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `basket_id`  INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity`   INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_basket_product` (`basket_id`, `product_id`),
  FOREIGN KEY (`basket_id`)  REFERENCES `baskets`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Orders ────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `shop_orders` (
  `id`                    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `reference`             VARCHAR(20)   NOT NULL,
  `user_id`               INT UNSIGNED  NOT NULL,
  `pricing_tier_id`       INT UNSIGNED  DEFAULT NULL,
  `status`                ENUM('pending','confirmed','processing','dispatched','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `checkout_method`       ENUM('stripe','po') NOT NULL DEFAULT 'stripe',
  `po_number`             VARCHAR(100)  DEFAULT NULL,
  `po_notes`              TEXT          DEFAULT NULL,
  `stripe_session_id`     VARCHAR(255)  DEFAULT NULL,
  `stripe_payment_intent` VARCHAR(255)  DEFAULT NULL,
  `subtotal`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_charge`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `vat_amount`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`                 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `billing_name`          VARCHAR(255)  DEFAULT NULL,
  `billing_company`       VARCHAR(255)  DEFAULT NULL,
  `billing_line1`         VARCHAR(255)  DEFAULT NULL,
  `billing_line2`         VARCHAR(255)  DEFAULT NULL,
  `billing_city`          VARCHAR(100)  DEFAULT NULL,
  `billing_county`        VARCHAR(100)  DEFAULT NULL,
  `billing_postcode`      VARCHAR(20)   DEFAULT NULL,
  `billing_country`       VARCHAR(100)  DEFAULT NULL,
  `shipping_name`         VARCHAR(255)  DEFAULT NULL,
  `shipping_company`      VARCHAR(255)  DEFAULT NULL,
  `shipping_line1`        VARCHAR(255)  DEFAULT NULL,
  `shipping_line2`        VARCHAR(255)  DEFAULT NULL,
  `shipping_city`         VARCHAR(100)  DEFAULT NULL,
  `shipping_county`       VARCHAR(100)  DEFAULT NULL,
  `shipping_postcode`     VARCHAR(20)   DEFAULT NULL,
  `shipping_country`      VARCHAR(100)  DEFAULT NULL,
  `notes`                 TEXT          DEFAULT NULL,
  `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_ref` (`reference`),
  FOREIGN KEY (`user_id`)         REFERENCES `users`(`id`)         ON DELETE CASCADE,
  FOREIGN KEY (`pricing_tier_id`) REFERENCES `pricing_tiers`(`id`) ON DELETE SET NULL,
  INDEX `idx_user`   (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shop_order_items` (
  `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `order_id`   INT UNSIGNED  NOT NULL,
  `product_id` INT UNSIGNED  DEFAULT NULL,
  `sku`        VARCHAR(100)  NOT NULL,
  `name`       VARCHAR(500)  NOT NULL,
  `quantity`   INT UNSIGNED  NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,4) NOT NULL,
  `total`      DECIMAL(10,2) NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`)   REFERENCES `shop_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)    ON DELETE SET NULL,
  INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Delivery Rules ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `delivery_rules` (
  `id`              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(255)  NOT NULL,
  `type`            ENUM('flat','tier','threshold') NOT NULL DEFAULT 'flat',
  `pricing_tier_id` INT UNSIGNED  DEFAULT NULL,
  `flat_rate`       DECIMAL(10,2) DEFAULT NULL,
  `free_threshold`  DECIMAL(10,2) DEFAULT NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pricing_tier_id`) REFERENCES `pricing_tiers`(`id`) ON DELETE CASCADE,
  INDEX `idx_tier` (`pricing_tier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Safely add shop columns to users table ────────────────────────────────────

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `pricing_tier_id` INT UNSIGNED DEFAULT NULL AFTER `role`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'pricing_tier_id');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `checkout_method` ENUM(''stripe'',''po'') NOT NULL DEFAULT ''stripe'' AFTER `pricing_tier_id`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'checkout_method');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Add shop settings ─────────────────────────────────────────────────────────

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
  ('shop_enabled',          '1'),
  ('shop_vat_rate',         '20'),
  ('shop_vat_enabled',      '1'),
  ('shop_default_currency', 'GBP'),
  ('shop_order_prefix',     'ORD'),
  ('shop_low_stock_notify', '1'),
  ('quickbooks_enabled',    '0'),
  ('sage_enabled',          '0');

-- ── Default pricing tiers ─────────────────────────────────────────────────────

INSERT IGNORE INTO `pricing_tiers` (`id`, `name`, `description`, `sort_order`) VALUES
  (1, 'Bronze',     'Entry-level pricing',           1),
  (2, 'Silver',     'Standard trade pricing',        2),
  (3, 'Gold',       'Preferred partner pricing',     3),
  (4, 'Platinum',   'Premium account pricing',       4),
  (5, 'Enterprise', 'Bespoke enterprise pricing',    5);

-- ── Default delivery rule ─────────────────────────────────────────────────────

INSERT IGNORE INTO `delivery_rules` (`name`, `type`, `flat_rate`, `free_threshold`, `is_active`, `sort_order`) VALUES
  ('Standard Delivery', 'flat',      6.95,  NULL,   1, 1),
  ('Free over £150',    'threshold', 0.00,  150.00, 1, 2);

SET FOREIGN_KEY_CHECKS = 1;

-- ── Done ──────────────────────────────────────────────────────────────────────
-- Run this after database.sql on any fresh or existing install.
