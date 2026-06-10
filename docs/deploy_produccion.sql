-- ====================================================================
-- DESPLIEGUE A PRODUCCION - Celfix POS (feature/pos-improved)
-- Generado: 2026-05-28
-- Aplica en phpMyAdmin sobre la base de PRODUCCION. Hacer BACKUP antes.
-- 100% IDEMPOTENTE: se puede correr varias veces sin error.
-- ====================================================================

SET FOREIGN_KEY_CHECKS=0;

-- ============ TABLAS NUEVAS ============

CREATE TABLE IF NOT EXISTS `card_terminals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_terminals_business_id_index` (`business_id`),
  CONSTRAINT `card_terminals_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_cuts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `location_id` int unsigned NOT NULL,
  `cut_date` date NOT NULL,
  `total_sales` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_cash` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_card` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_transfer` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_cheque` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_other` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_expenses` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `total_transactions` int unsigned NOT NULL DEFAULT '0',
  `summary` json DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily_cut` (`business_id`,`location_id`,`cut_date`),
  KEY `daily_cuts_location_id_foreign` (`location_id`),
  KEY `daily_cuts_cut_date_index` (`cut_date`),
  CONSTRAINT `daily_cuts_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
  CONSTRAINT `daily_cuts_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `business_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `discount_locations` (
  `discount_id` int unsigned NOT NULL,
  `location_id` int unsigned NOT NULL,
  PRIMARY KEY (`discount_id`,`location_id`),
  KEY `discount_locations_location_id_foreign` (`location_id`),
  CONSTRAINT `discount_locations_discount_id_foreign` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_locations_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `business_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `repair_product_commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `commission_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_repair_commission` (`business_id`,`product_id`),
  KEY `repair_product_commissions_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_goals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `location_id` int unsigned NOT NULL DEFAULT '0',
  `metric` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'equipos_weekly',
  `target_qty` int unsigned NOT NULL DEFAULT '0',
  `target_amount` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_goal` (`business_id`,`location_id`,`metric`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_corrections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `location_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `variation_id` int unsigned NOT NULL,
  `type` enum('add','deduct') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(22,4) NOT NULL,
  `reason` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_before` decimal(22,4) DEFAULT NULL,
  `qty_after` decimal(22,4) DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_corrections_business_id_location_id_index` (`business_id`,`location_id`),
  KEY `stock_corrections_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `technicians` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `commission_per_repair` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `technicians_business_id_is_active_index` (`business_id`,`is_active`),
  CONSTRAINT `technicians_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `technician_locations` (
  `technician_id` bigint unsigned NOT NULL,
  `location_id` int unsigned NOT NULL,
  PRIMARY KEY (`technician_id`,`location_id`),
  KEY `technician_locations_location_id_foreign` (`location_id`),
  CONSTRAINT `technician_locations_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `business_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `technician_locations_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vendor_commission_targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `brand_id` int unsigned NOT NULL,
  `meta_units` int unsigned NOT NULL DEFAULT '0',
  `commission_per_unit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_target_per_user_brand` (`user_id`,`brand_id`),
  KEY `vendor_commission_targets_business_id_foreign` (`business_id`),
  KEY `vendor_commission_targets_brand_id_foreign` (`brand_id`),
  CONSTRAINT `vendor_commission_targets_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_commission_targets_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_commission_targets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ COLUMNAS NUEVAS EN TABLAS EXISTENTES ============

-- columna business.cash_exchange_rate (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'business' AND column_name = 'cash_exchange_rate');
SET @s := IF(@e = 0, 'ALTER TABLE `business` ADD COLUMN `cash_exchange_rate` decimal(10,4) NOT NULL DEFAULT ''18.0000''', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna products.reward_points (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'reward_points');
SET @s := IF(@e = 0, 'ALTER TABLE `products` ADD COLUMN `reward_points` int NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna transaction_payments.card_terminal_id (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transaction_payments' AND column_name = 'card_terminal_id');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_payments` ADD COLUMN `card_terminal_id` bigint unsigned NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- indice transaction_payments.card_terminal_id (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'transaction_payments' AND index_name = 'transaction_payments_card_terminal_id_index');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_payments` ADD INDEX `transaction_payments_card_terminal_id_index` (`card_terminal_id`)', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna transaction_payments.denomination_breakdown (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transaction_payments' AND column_name = 'denomination_breakdown');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_payments` ADD COLUMN `denomination_breakdown` json NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna transaction_sell_lines.technician_id (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transaction_sell_lines' AND column_name = 'technician_id');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_sell_lines` ADD COLUMN `technician_id` bigint unsigned NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- indice transaction_sell_lines.technician_id (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'transaction_sell_lines' AND index_name = 'transaction_sell_lines_technician_id_index');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_sell_lines` ADD INDEX `transaction_sell_lines_technician_id_index` (`technician_id`)', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- FK transaction_sell_lines.technician_id → technicians.id (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = 'transaction_sell_lines' AND constraint_name = 'transaction_sell_lines_technician_id_foreign');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_sell_lines` ADD CONSTRAINT `transaction_sell_lines_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE SET NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna transaction_sell_lines.repair_entry_date (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transaction_sell_lines' AND column_name = 'repair_entry_date');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_sell_lines` ADD COLUMN `repair_entry_date` date NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna transaction_sell_lines.repair_anticipo (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transaction_sell_lines' AND column_name = 'repair_anticipo');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_sell_lines` ADD COLUMN `repair_anticipo` decimal(22,4) NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columna transaction_sell_lines.technician_commission_override (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transaction_sell_lines' AND column_name = 'technician_commission_override');
SET @s := IF(@e = 0, 'ALTER TABLE `transaction_sell_lines` ADD COLUMN `technician_commission_override` decimal(22,4) NULL', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- columnas daily_cuts.closed_at + closed_by + indice + backfill (solo si no existen)
-- closed_at marca que el corte de esa (sucursal, fecha) quedo "cerrado definitivamente".
-- Una vez cerrado:
--   - el heartbeat de las 18:00 ignora esa sucursal,
--   - ensureCutsForRange NO regenera al abrir reportes,
--   - solo "Reabrir" (admin) lo vuelve a hacer mutable.
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'daily_cuts' AND column_name = 'closed_at');
SET @s := IF(@e = 0, 'ALTER TABLE `daily_cuts` ADD COLUMN `closed_at` DATETIME NULL AFTER `generated_at`', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'daily_cuts' AND column_name = 'closed_by');
SET @s := IF(@e = 0, 'ALTER TABLE `daily_cuts` ADD COLUMN `closed_by` INT UNSIGNED NULL AFTER `closed_at`', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @e := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'daily_cuts' AND index_name = 'daily_cuts_closed_at_index');
SET @s := IF(@e = 0, 'ALTER TABLE `daily_cuts` ADD INDEX `daily_cuts_closed_at_index` (`closed_at`)', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Backfill: cortes generados >= cut_date 18:00 quedan marcados como cerrados
-- (hereda la regla anterior de "frozen after 18:00").
UPDATE `daily_cuts`
   SET `closed_at` = `generated_at`
 WHERE `closed_at` IS NULL
   AND `generated_at` >= TIMESTAMP(CONCAT(`cut_date`, ' 18:00:00'));

-- columna layaways.completed_at + indice + backfill (solo si no existe)
-- Marca la fecha en que un apartado pasó a status=completed con balance=0.
-- El cut diario la usa para consolidar los pagos del apartado en el dia de entrega
-- (los apartados activos quedan FUERA del cut hasta que se paguen completos).
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'layaways' AND column_name = 'completed_at');
SET @s := IF(@e = 0, 'ALTER TABLE `layaways` ADD COLUMN `completed_at` DATETIME NULL AFTER `status`', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @e := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'layaways' AND index_name = 'layaways_completed_at_index');
SET @s := IF(@e = 0, 'ALTER TABLE `layaways` ADD INDEX `layaways_completed_at_index` (`completed_at`)', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Backfill: apartados ya cerrados al 100% (completed + balance=0) usan updated_at como fecha de completacion.
UPDATE `layaways`
   SET `completed_at` = `updated_at`
 WHERE `status` = 'completed'
   AND `balance_due` = 0
   AND `completed_at` IS NULL;

-- columna transactions.repair_status (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transactions' AND column_name = 'repair_status');
SET @s := IF(@e = 0, 'ALTER TABLE `transactions` ADD COLUMN `repair_status` varchar(20) NULL AFTER `status`', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- indice transactions.repair_status (solo si no existe)
SET @e := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'transactions' AND index_name = 'transactions_repair_status_index');
SET @s := IF(@e = 0, 'ALTER TABLE `transactions` ADD INDEX `transactions_repair_status_index` (`repair_status`)', 'DO 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ============ LIMPIEZA DE DATOS (marcas con formula de Excel) ============
DELETE FROM `brands`
 WHERE `name` LIKE '=%'
   AND `id` NOT IN (SELECT brand_id FROM (SELECT DISTINCT brand_id FROM products WHERE brand_id IS NOT NULL) x);

SET FOREIGN_KEY_CHECKS=1;
