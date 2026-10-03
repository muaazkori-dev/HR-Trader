-- HR Traders Mobile Apps Database Migration
-- Adds Rider role, live GPS tracking, delivery coordinates, and notification support

-- 1. Update users table role enum to include 'rider' and add push notification token
ALTER TABLE `users` 
  MODIFY COLUMN `role` ENUM('owner', 'manager', 'customer', 'rider') NOT NULL DEFAULT 'customer';

-- Add FCM push notification token if not exists
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `fcm_token` VARCHAR(255) DEFAULT NULL AFTER `address`;

-- 2. Add Rider, GPS Coordinates & Delivery Timestamps to orders table
ALTER TABLE `orders` 
  ADD COLUMN IF NOT EXISTS `rider_id` INT DEFAULT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10, 8) DEFAULT NULL AFTER `customer_address`,
  ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(11, 8) DEFAULT NULL AFTER `latitude`,
  ADD COLUMN IF NOT EXISTS `delivery_notes` TEXT DEFAULT NULL AFTER `longitude`,
  ADD COLUMN IF NOT EXISTS `assigned_at` DATETIME DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `picked_at` DATETIME DEFAULT NULL AFTER `assigned_at`,
  ADD COLUMN IF NOT EXISTS `delivered_at` DATETIME DEFAULT NULL AFTER `picked_at`,
  ADD INDEX IF NOT EXISTS `idx_rider_id` (`rider_id`),
  ADD INDEX IF NOT EXISTS `idx_order_created` (`created_at`);

-- 3. Create rider_locations table for live GPS ping tracking
CREATE TABLE IF NOT EXISTS `rider_locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rider_id` INT NOT NULL UNIQUE,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `heading` DECIMAL(5, 2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`rider_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insert Default Demo Rider (Password: rider123)
-- Hash generated via password_hash('rider123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password`, `role`, `name`, `phone`, `address`)
SELECT 'rider1', '$2y$10$wWwMv/yG/T.i2aP1r9qK2OebkexyNmsJsp4v1P6d8s5bQv3iQ7eGq', 'rider', 'Express Rider 1', '03001234567', 'Tando Adam'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'rider1');
