-- ============================================================
-- CoutureAtelier — Patch SQL pour LWS (phpMyAdmin)
-- Branche backend : main  (commit 46fa33b)
-- A executer UNE SEULE FOIS sur la base de production existante.
-- ============================================================

-- 1) Table orders : liaison utilisateur + paiement/livraison
ALTER TABLE `orders`
  ADD COLUMN `user_id` BIGINT UNSIGNED NULL AFTER `id`,
  ADD COLUMN `payment_method` VARCHAR(255) NULL AFTER `promo_code`,
  ADD COLUMN `delivery_method` VARCHAR(255) NULL AFTER `payment_method`,
  ADD COLUMN `delivery_address` TEXT NULL AFTER `delivery_method`,
  ADD COLUMN `paid_at` TIMESTAMP NULL AFTER `status`;

-- Cle etrangere (desactiver si erreur "foreign key constraint fails")
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_user_id_foreign`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 2) Table products : stock, tailles, couleurs
ALTER TABLE `products`
  ADD COLUMN `quantity` INT NULL AFTER `price`,
  ADD COLUMN `sizes` TEXT NULL AFTER `quantity`,
  ADD COLUMN `colors` TEXT NULL AFTER `sizes`;

-- 3) (Optionnel) Verifier le resultat
-- SHOW COLUMNS FROM `orders`;
-- SHOW COLUMNS FROM `products`;
