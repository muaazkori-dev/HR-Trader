-- HR Traders System Settings Schema
-- Stores dynamic configuration parameters for store hours, styling, thresholds, and alerts

CREATE TABLE IF NOT EXISTS `settings` (
    `key_name` VARCHAR(100) PRIMARY KEY,
    `val_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default configuration settings if they are not already set
INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('active_theme', 'emerald_green')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;

INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('shop_status', 'open')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;

INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('low_stock_threshold', '5')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;

INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('homepage_announcement', 'Welcome to HR Traders! Shop the freshest organic grains, cosmetics, and cold beverages online. Order now for home delivery!')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;

INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('whatsapp_dispatch_template', 'Hi {name}, your order #{ref} has been dispatched! Total Invoice: {total}. Delivery Address: {address}. Thank you for shopping with HR Traders!')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;

INSERT INTO `settings` (`key_name`, `val_value`) VALUES
('shop_timings', '{"Saturday":"6:00 AM - 12:00 PM","Sunday":"6:00 AM - 12:00 PM","Monday":"6:00 AM - 12:00 PM","Tuesday":"6:00 AM - 12:00 PM","Wednesday":"6:00 AM - 12:00 PM","Thursday":"6:00 AM - 12:00 PM","Friday":"6:00 AM - 12:00 PM & 4:00 PM - 12:00 AM"}')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;
