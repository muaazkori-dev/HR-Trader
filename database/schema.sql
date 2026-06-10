-- HR Traders Hybrid Retail System Database Schema
-- Optimized for Grocery E-commerce & In-store POS Billing

CREATE DATABASE IF NOT EXISTS `hr_traders` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hr_traders`;

-- 1. USERS TABLE (Roles: owner, manager, customer)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'manager', 'customer') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PRODUCTS TABLE (Inventory)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `barcode` VARCHAR(50) UNIQUE NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,            -- Selling price
  `purchase_price` DECIMAL(10,2) NOT NULL,   -- Purchase cost (for net profit reports)
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `weight` VARCHAR(20) DEFAULT NULL,         -- e.g., '1 kg', '500 g'
  `unit` VARCHAR(20) DEFAULT 'pcs',          -- e.g., 'kg', 'pack', 'pcs'
  `category` VARCHAR(50) NOT NULL,           -- pulses_rice, snacks_chips, beverages, frozen_icecream
  `image` VARCHAR(255) DEFAULT NULL,         -- Optional item image path
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_barcode` (`barcode`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ORDERS TABLE (Online customer orders storefront)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,                -- NULL for guest checkouts
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_address` TEXT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'COD',
  `status` ENUM('pending', 'packaging', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ORDER ITEMS TABLE (Line items for online orders)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,            -- Sold price
  `quantity` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. SALES TABLE (Consolidated sales register for Owner daily/monthly profit reports)
CREATE TABLE IF NOT EXISTS `sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_type` ENUM('POS', 'Online') NOT NULL DEFAULT 'POS',
  `order_id` INT DEFAULT NULL,               -- References online order if type is 'Online'
  `total_amount` DECIMAL(10,2) NOT NULL,
  `total_profit` DECIMAL(10,2) NOT NULL,     -- Pre-calculated net profit for fast dashboard query
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash', -- Cash, Card, EasyPaisa, JazzCash
  `cashier_id` INT DEFAULT NULL,             -- User ID of owner/manager operating POS
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. SALE ITEMS TABLE (Details for financial reports)
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,            -- Selling price at the time of purchase
  `purchase_price` DECIMAL(10,2) NOT NULL,   -- Purchase cost at the time of purchase (selling_price - purchase_price = net profit)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT DEFAULT USERS (Passwords default to 'admin123', customer to 'customer123')
-- Password hashes generated via password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password`, `role`, `name`, `phone`, `address`) VALUES
('owner', '$2y$10$wWwMv/yG/T.i2aP1r9qK2OebkexyNmsJsp4v1P6d8s5bQv3iQ7eGq', 'owner', 'Owner Admin', '03033943814', 'Toor Colony, Front of Hira Public School, Tando Adam'),
('manager', '$2y$10$wWwMv/yG/T.i2aP1r9qK2OebkexyNmsJsp4v1P6d8s5bQv3iQ7eGq', 'manager', 'Store Manager', '03217654321', 'Lahore'),
('customer', '$2y$10$DzkQyqM/jI/Wq/y1W3cZeuuE9p9hL9kSg1D9o33r5Nszk/HjA5XyK', 'customer', 'Test Customer', '03129876543', 'Johar Town, Lahore');

-- INSERT DUMMY PRODUCTS FOR RETAIL SETUP
INSERT INTO `products` (`barcode`, `name`, `description`, `price`, `purchase_price`, `stock_quantity`, `weight`, `unit`, `category`) VALUES
('11111111', 'Premium Basmati Rice', 'High quality extra long grain aromatic rice.', 320.00, 260.00, 150, '1 kg', 'kg', 'anaj'),
('22222222', 'Daal Chana (Gram Lentils)', 'Clean and washed split gram pulses.', 280.00, 220.00, 200, '1 kg', 'kg', 'anaj'),
('33333333', 'Daal Mash (Urad Lentils)', 'Spiceless premium white lentils.', 450.00, 380.00, 80, '1 kg', 'kg', 'anaj'),
('44444444', 'Sunsilk Shampoo', 'Softening and shining hair shampoo bottle.', 350.00, 290.00, 120, '180 ml', 'pcs', 'shampoo'),
('55555555', 'Lifebuoy Soap Red', 'Germ protection hand washing soap bar.', 90.00, 75.00, 250, '115 g', 'pcs', 'soap'),
('66666666', 'Coca Cola Regular Can', 'Carbonated cold soft drink.', 150.00, 120.00, 500, '250 ml', 'pcs', 'cold_drinks'),
('77777777', 'Nestle Pure Life Water', 'Pure drinking mineral water bottle.', 90.00, 70.00, 600, '1.5 Litre', 'pcs', 'water'),
('88888888', 'Gourmet Chocolate Ice Cream', 'Creamy rich chocolate family pack ice cream.', 650.00, 520.00, 45, '1 Litre', 'pcs', 'ice_cream'),
('99999999', 'Nestle Milkpak', 'UHT processed premium milk pack.', 290.00, 240.00, 180, '1 Litre', 'pcs', 'milk');