-- One-off cleanup, 2026-10-16: single-colour products were listed twice
-- ("Name" and "Name (Black)") since 2026-05-01, with stock copied to both.
-- The "(Colour)" twin is gone from products-list.json; this removes its data.
-- Run once in phpMyAdmin on StockDB2 (SQL tab), after deploying the new site.
--
-- Keeps: the plain entry's stock, barcode and price (the real item).
-- Removes: the twin's stock row, its barcode (GT4 PRO (Black) = AS70065441, never printed)
-- and its price override, after copying that price to the plain name if it had none.
-- Sales history (stock_transactions) is left as is.

START TRANSACTION;

-- Dark Grey USB Rechargeable Fan... (Black)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'Dark Grey USB Rechargeable Fan', price FROM product_prices WHERE product_name = 'Dark Grey USB Rechargeable Fan (Black)';
DELETE FROM product_prices WHERE product_name = 'Dark Grey USB Rechargeable Fan (Black)';
DELETE FROM product_stock WHERE product_name = 'Dark Grey USB Rechargeable Fan (Black)';
DELETE FROM barcode_map WHERE product_name = 'Dark Grey USB Rechargeable Fan (Black)';

-- Doviico 1.83-inch Touch Screen, Supports Call Function, Inco... (Pink)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones', price FROM product_prices WHERE product_name = 'Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones (Pink)';
DELETE FROM product_prices WHERE product_name = 'Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones (Pink)';
DELETE FROM product_stock WHERE product_name = 'Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones (Pink)';
DELETE FROM barcode_map WHERE product_name = 'Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones (Pink)';

-- GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Ca... (Black)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications', price FROM product_prices WHERE product_name = 'GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications (Black)';
DELETE FROM product_prices WHERE product_name = 'GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications (Black)';
DELETE FROM product_stock WHERE product_name = 'GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications (Black)';
DELETE FROM barcode_map WHERE product_name = 'GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications (Black)';

-- JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Ca... (Black)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C', price FROM product_prices WHERE product_name = 'JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C (Black)';
DELETE FROM product_prices WHERE product_name = 'JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C (Black)';
DELETE FROM product_stock WHERE product_name = 'JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C (Black)';
DELETE FROM barcode_map WHERE product_name = 'JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C (Black)';

-- LAXASFIT Unisex Smartwatch Compatible with Both iPhone and A... (Black)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android', price FROM product_prices WHERE product_name = 'LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android (Black)';
DELETE FROM product_prices WHERE product_name = 'LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android (Black)';
DELETE FROM product_stock WHERE product_name = 'LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android (Black)';
DELETE FROM barcode_map WHERE product_name = 'LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android (Black)';

-- PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flas... (Black)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone', price FROM product_prices WHERE product_name = 'PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone (Black)';
DELETE FROM product_prices WHERE product_name = 'PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone (Black)';
DELETE FROM product_stock WHERE product_name = 'PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone (Black)';
DELETE FROM barcode_map WHERE product_name = 'PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone (Black)';

-- Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inc... (Black)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen', price FROM product_prices WHERE product_name = 'Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen (Black)';
DELETE FROM product_prices WHERE product_name = 'Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen (Black)';
DELETE FROM product_stock WHERE product_name = 'Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen (Black)';
DELETE FROM barcode_map WHERE product_name = 'Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen (Black)';

-- Wireless Earbuds with Charging Case, USB Type-C Charging, TW... (White)
INSERT IGNORE INTO product_prices (product_name, price) SELECT 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology', price FROM product_prices WHERE product_name = 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology (White)';
DELETE FROM product_prices WHERE product_name = 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology (White)';
DELETE FROM product_stock WHERE product_name = 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology (White)';
DELETE FROM barcode_map WHERE product_name = 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology (White)';

COMMIT;
