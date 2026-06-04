-- ============================================================
--  WVM Funeral Supplies — Portal Customisation Migration
--  Run after database.sql and database-shop.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Users: add postcode and branch_number ─────────────────────────────────────

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `postcode` VARCHAR(20) DEFAULT NULL AFTER `phone`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'postcode');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `branch_number` VARCHAR(50) DEFAULT NULL AFTER `postcode`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'branch_number');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Pricing tiers: add hide_invoices flag ─────────────────────────────────────

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `pricing_tiers` ADD COLUMN `hide_invoices` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pricing_tiers' AND COLUMN_NAME = 'hide_invoices');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Rename tiers to WVM account types ────────────────────────────────────────

INSERT INTO `pricing_tiers` (`id`, `name`, `description`, `hide_invoices`, `sort_order`) VALUES
  (1, 'Customer',   'Standard retail customer',        0, 1),
  (2, 'Wholesaler', 'Wholesale trade account',         0, 2),
  (3, 'Trade',      'Trade account',                   0, 3),
  (4, 'Dignity',    'Dignity account (invoices hidden)',1, 4),
  (5, 'Dignity 2',  'Dignity secondary (invoices hidden)',1, 5),
  (6, 'Custom',     'Additional custom tier',          0, 6)
ON DUPLICATE KEY UPDATE
  `name`          = VALUES(`name`),
  `description`   = VALUES(`description`),
  `hide_invoices` = VALUES(`hide_invoices`),
  `sort_order`    = VALUES(`sort_order`);

-- ── Products: add type column ─────────────────────────────────────────────────

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `products` ADD COLUMN `type` ENUM(''simple'',''variable'') NOT NULL DEFAULT ''simple'' AFTER `id`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'type');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `products` ADD COLUMN `short_description` TEXT DEFAULT NULL AFTER `description`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'short_description');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `products` ADD COLUMN `weight` DECIMAL(8,3) DEFAULT NULL AFTER `short_description`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'weight');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `products` ADD COLUMN `woo_id` INT UNSIGNED DEFAULT NULL AFTER `weight`',
  'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'woo_id');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Product attributes ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_attributes` (
  `id`         INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(255)     NOT NULL,
  `slug`       VARCHAR(255)     NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_attribute_terms` (
  `id`           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `attribute_id` INT UNSIGNED     NOT NULL,
  `name`         VARCHAR(255)     NOT NULL,
  `slug`         VARCHAR(255)     NOT NULL,
  `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_attr_term` (`attribute_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which attributes are used by each product (and whether they drive variations)
CREATE TABLE IF NOT EXISTS `product_attribute_maps` (
  `id`           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `product_id`   INT UNSIGNED     NOT NULL,
  `attribute_id` INT UNSIGNED     NOT NULL,
  `term_ids`     TEXT             DEFAULT NULL,  -- comma-separated term IDs used by this product
  `is_variation` TINYINT(1)       NOT NULL DEFAULT 0,
  `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_product_attr` (`product_id`, `attribute_id`),
  FOREIGN KEY (`product_id`)   REFERENCES `products`(`id`)           ON DELETE CASCADE,
  FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Product variations ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_variations` (
  `id`          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `product_id`  INT UNSIGNED  NOT NULL,
  `sku`         VARCHAR(100)  NOT NULL,
  `stock`       INT           NOT NULL DEFAULT 0,
  `image`       VARCHAR(255)  DEFAULT NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`  INT UNSIGNED  NOT NULL DEFAULT 0,
  `woo_id`      INT UNSIGNED  DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_sku` (`sku`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attribute values that define each variation
CREATE TABLE IF NOT EXISTS `product_variation_attributes` (
  `variation_id` INT UNSIGNED NOT NULL,
  `attribute_id` INT UNSIGNED NOT NULL,
  `term_id`      INT UNSIGNED NOT NULL,
  PRIMARY KEY (`variation_id`, `attribute_id`),
  FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`term_id`)      REFERENCES `product_attribute_terms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tier pricing per variation
CREATE TABLE IF NOT EXISTS `variation_prices` (
  `variation_id`    INT UNSIGNED  NOT NULL,
  `pricing_tier_id` INT UNSIGNED  NOT NULL,
  `price`           DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`variation_id`, `pricing_tier_id`),
  FOREIGN KEY (`variation_id`)    REFERENCES `product_variations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pricing_tier_id`) REFERENCES `pricing_tiers`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Done ──────────────────────────────────────────────────────────────────────
-- Run order: database.sql → database-shop.sql → database-wvm.sql

-- ── Customer address fields ────────────────────────────────────────────────────

SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `billing_address_1` VARCHAR(255) DEFAULT NULL AFTER `branch_number`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'billing_address_1'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `billing_address_2` VARCHAR(255) DEFAULT NULL AFTER `billing_address_1`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'billing_address_2'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `billing_city` VARCHAR(100) DEFAULT NULL AFTER `billing_address_2`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'billing_city'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `billing_county` VARCHAR(100) DEFAULT NULL AFTER `billing_city`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'billing_county'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `billing_postcode` VARCHAR(20) DEFAULT NULL AFTER `billing_county`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'billing_postcode'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `billing_country` VARCHAR(100) NOT NULL DEFAULT ''United Kingdom'' AFTER `billing_postcode`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'billing_country'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_same_as_billing` TINYINT(1) NOT NULL DEFAULT 1 AFTER `billing_country`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_same_as_billing'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_address_1` VARCHAR(255) DEFAULT NULL AFTER `delivery_same_as_billing`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_address_1'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_address_2` VARCHAR(255) DEFAULT NULL AFTER `delivery_address_1`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_address_2'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_city` VARCHAR(100) DEFAULT NULL AFTER `delivery_address_2`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_city'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_county` VARCHAR(100) DEFAULT NULL AFTER `delivery_city`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_county'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_postcode` VARCHAR(20) DEFAULT NULL AFTER `delivery_county`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_postcode'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `delivery_country` VARCHAR(100) NOT NULL DEFAULT ''United Kingdom'' AFTER `delivery_postcode`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'delivery_country'); PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill billing_postcode from existing postcode column
UPDATE `users` SET `billing_postcode` = `postcode` WHERE `billing_postcode` IS NULL AND `postcode` IS NOT NULL;
