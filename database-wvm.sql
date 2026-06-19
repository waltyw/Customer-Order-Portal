-- ============================================================
--  WVM Funeral Supplies — Portal Customisation Migration
--  Run order: database.sql  →  database-shop.sql  →  database-wvm.sql
--
--  Safe to re-run on an existing database (all additions are
--  guarded with IF NOT EXISTS / ON DUPLICATE KEY UPDATE).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Helper procedure: add a column only if it doesn't already exist ───────────
--    Usage: CALL _add_col('table', 'column', 'COLUMN DEFINITION');
--    Dropped at the end of this script.

DROP PROCEDURE IF EXISTS _add_col;

DELIMITER $$
CREATE PROCEDURE _add_col(IN tbl VARCHAR(64), IN col VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE  TABLE_SCHEMA = DATABASE()
    AND    TABLE_NAME   = tbl
    AND    COLUMN_NAME  = col
  ) THEN
    SET @_sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
    PREPARE _stmt FROM @_sql;
    EXECUTE _stmt;
    DEALLOCATE PREPARE _stmt;
  END IF;
END$$
DELIMITER ;

-- ── Users table: legacy fields ────────────────────────────────────────────────

CALL _add_col('users', 'postcode',      'VARCHAR(20)  DEFAULT NULL AFTER `phone`');
CALL _add_col('users', 'branch_number', 'VARCHAR(50)  DEFAULT NULL AFTER `postcode`');

-- ── Users table: CC email addresses ──────────────────────────────────────────

CALL _add_col('users', 'cc_email_1', 'VARCHAR(255) DEFAULT NULL AFTER `email`');
CALL _add_col('users', 'cc_email_2', 'VARCHAR(255) DEFAULT NULL AFTER `cc_email_1`');

-- ── Users table: billing address ─────────────────────────────────────────────

CALL _add_col('users', 'billing_address_1', 'VARCHAR(255) DEFAULT NULL AFTER `branch_number`');
CALL _add_col('users', 'billing_address_2', 'VARCHAR(255) DEFAULT NULL AFTER `billing_address_1`');
CALL _add_col('users', 'billing_city',      'VARCHAR(100) DEFAULT NULL AFTER `billing_address_2`');
CALL _add_col('users', 'billing_county',    'VARCHAR(100) DEFAULT NULL AFTER `billing_city`');
CALL _add_col('users', 'billing_postcode',  'VARCHAR(20)  DEFAULT NULL AFTER `billing_county`');
CALL _add_col('users', 'billing_country',   'VARCHAR(100) NOT NULL DEFAULT "United Kingdom" AFTER `billing_postcode`');

-- ── Users table: delivery address ────────────────────────────────────────────

CALL _add_col('users', 'delivery_same_as_billing', 'TINYINT(1)   NOT NULL DEFAULT 1    AFTER `billing_country`');
CALL _add_col('users', 'delivery_address_1',        'VARCHAR(255) DEFAULT NULL          AFTER `delivery_same_as_billing`');
CALL _add_col('users', 'delivery_address_2',        'VARCHAR(255) DEFAULT NULL          AFTER `delivery_address_1`');
CALL _add_col('users', 'delivery_city',             'VARCHAR(100) DEFAULT NULL          AFTER `delivery_address_2`');
CALL _add_col('users', 'delivery_county',           'VARCHAR(100) DEFAULT NULL          AFTER `delivery_city`');
CALL _add_col('users', 'delivery_postcode',         'VARCHAR(20)  DEFAULT NULL          AFTER `delivery_county`');
CALL _add_col('users', 'delivery_country',          'VARCHAR(100) NOT NULL DEFAULT "United Kingdom" AFTER `delivery_postcode`');

-- ── Users table: shop fields ─────────────────────────────────────────────────

CALL _add_col('users', 'pricing_tier_id', 'INT UNSIGNED DEFAULT NULL AFTER `role`');
CALL _add_col('users', 'checkout_method', 'ENUM("stripe","po") NOT NULL DEFAULT "stripe" AFTER `pricing_tier_id`');

-- Backfill billing_postcode from legacy postcode column
UPDATE `users`
SET    `billing_postcode` = `postcode`
WHERE  `billing_postcode` IS NULL
AND    `postcode`         IS NOT NULL;

-- ── Pricing tiers ─────────────────────────────────────────────────────────────

CALL _add_col('pricing_tiers', 'hide_invoices', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`');

INSERT INTO `pricing_tiers` (`id`, `name`, `description`, `hide_invoices`, `sort_order`) VALUES
  (1, 'Customer',   'Standard retail customer',           0, 1),
  (2, 'Wholesaler', 'Wholesale trade account',            0, 2),
  (3, 'Trade',      'Trade account',                      0, 3),
  (4, 'Dignity',    'Dignity account (invoices hidden)',   1, 4),
  (5, 'Dignity 2',  'Dignity secondary (invoices hidden)', 1, 5),
  (6, 'Custom',     'Additional custom tier',             0, 6)
ON DUPLICATE KEY UPDATE
  `name`          = VALUES(`name`),
  `description`   = VALUES(`description`),
  `hide_invoices` = VALUES(`hide_invoices`),
  `sort_order`    = VALUES(`sort_order`);

-- ── Products table: extra columns ────────────────────────────────────────────

CALL _add_col('products', 'type',              'ENUM("simple","variable") NOT NULL DEFAULT "simple" AFTER `id`');
CALL _add_col('products', 'short_description', 'TEXT         DEFAULT NULL AFTER `description`');
CALL _add_col('products', 'weight',            'DECIMAL(8,3) DEFAULT NULL AFTER `short_description`');
CALL _add_col('products', 'woo_id',            'INT UNSIGNED DEFAULT NULL AFTER `weight`');

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
  UNIQUE KEY `uq_attr_term` (`attribute_id`, `slug`),
  FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_attribute_maps` (
  `id`           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `product_id`   INT UNSIGNED     NOT NULL,
  `attribute_id` INT UNSIGNED     NOT NULL,
  `term_ids`     TEXT             DEFAULT NULL,
  `is_variation` TINYINT(1)       NOT NULL DEFAULT 0,
  `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_product_attr` (`product_id`, `attribute_id`),
  FOREIGN KEY (`product_id`)   REFERENCES `products`(`id`)           ON DELETE CASCADE,
  FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Product variations ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `product_variations` (
  `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED  NOT NULL,
  `sku`        VARCHAR(100)  NOT NULL,
  `stock`      INT           NOT NULL DEFAULT 0,
  `image`      VARCHAR(255)  DEFAULT NULL,
  `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED  NOT NULL DEFAULT 0,
  `woo_id`     INT UNSIGNED  DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_sku` (`sku`),
  INDEX `idx_product` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_variation_attributes` (
  `variation_id` INT UNSIGNED NOT NULL,
  `attribute_id` INT UNSIGNED NOT NULL,
  `term_id`      INT UNSIGNED NOT NULL,
  PRIMARY KEY (`variation_id`, `attribute_id`),
  FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`term_id`)      REFERENCES `product_attribute_terms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `variation_prices` (
  `variation_id`    INT UNSIGNED  NOT NULL,
  `pricing_tier_id` INT UNSIGNED  NOT NULL,
  `price`           DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`variation_id`, `pricing_tier_id`),
  FOREIGN KEY (`variation_id`)    REFERENCES `product_variations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pricing_tier_id`) REFERENCES `pricing_tiers`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Shop settings ─────────────────────────────────────────────────────────────

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
  ('shop_enabled',          '1'),
  ('shop_vat_rate',         '20'),
  ('shop_vat_enabled',      '1'),
  ('shop_default_currency', 'GBP'),
  ('shop_order_prefix',     'ORD'),
  ('shop_low_stock_notify', '1'),
  ('quickbooks_enabled',    '0'),
  ('sage_enabled',          '0');

-- ── Default delivery rules ────────────────────────────────────────────────────

INSERT IGNORE INTO `delivery_rules` (`name`, `type`, `flat_rate`, `free_threshold`, `is_active`, `sort_order`) VALUES
  ('Standard Delivery', 'flat',      6.95, NULL,   1, 1),
  ('Free over £150',    'threshold', 0.00, 150.00, 1, 2);

-- ── Clean up helper procedure ─────────────────────────────────────────────────

DROP PROCEDURE IF EXISTS _add_col;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Done ──────────────────────────────────────────────────────────────────────
-- Default login: ian@beebizzi.co.uk / password  (change immediately)
