<?php
/**
 * cron-expire-orders.php
 * Cancels expired pending orders and restores their stock: unpaid 24 hours after the
 * admin confirmed them ("Receive Order"), or never confirmed 48 hours after ordering.
 * Must match autoExpireOrders() / ORDER_TIME_COLS in orders.php.
 *
 * Run via cPanel Cron Jobs every hour:
 *   /usr/bin/php /home/nu7wechphtdh/public_html/americanselect.net/api/cron-expire-orders.php
 */

require_once __DIR__ . '/db.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Same session time zone as orders.php, so NOW() lines up with the stored times
    $pdo->exec("SET time_zone = '+00:00'");

    // received_at is added by orders.php — make sure it exists before the cron queries it
    try { $pdo->exec("ALTER TABLE pending_orders ADD COLUMN received_at TIMESTAMP NULL"); } catch (Exception $e) {}

    // Find all expired pending orders
    $stmt = $pdo->query('SELECT * FROM pending_orders WHERE status = "pending" AND ((received_at IS NOT NULL AND received_at < NOW() - INTERVAL 24 HOUR) OR (received_at IS NULL AND created_at < NOW() - INTERVAL 48 HOUR))');
    $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($expired as $order) {
        $items = json_decode($order['items'], true) ?? [];

        // Restore stock for each item
        foreach ($items as $item) {
            $name = $item['name'];
            $qty  = max(1, (int)($item['quantity'] ?? 1));

            $stockStmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
            $stockStmt->execute([$name]);
            $row = $stockStmt->fetch(PDO::FETCH_ASSOC);
            $stockBefore = $row ? (int)$row['quantity'] : 0;
            $stockAfter  = $stockBefore + $qty;

            $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')
                ->execute([$name, $stockAfter]);

            $pdo->prepare('INSERT INTO stock_transactions (product_name, action, quantity, stock_before, stock_after, note) VALUES (?, "returned", ?, ?, ?, ?)')
                ->execute([$name, $qty, $stockBefore, $stockAfter, 'Restored — Auto-cancelled ' . $order['order_ref']]);
        }

        // Mark order as cancelled
        $pdo->prepare('UPDATE pending_orders SET status = "cancelled", cancelled_at = NOW(), note = ? WHERE id = ?')
            ->execute([$order['received_at']
                ? 'Auto-cancelled — not paid within 24 hours of confirmation — stock restored'
                : 'Auto-cancelled — not confirmed within 48 hours — stock restored', $order['id']]);

        $count++;
        echo '[' . date('Y-m-d H:i:s') . '] Cancelled ' . $order['order_ref'] . ' — stock restored for ' . count($items) . " item(s)\n";
    }

    if ($count === 0) echo '[' . date('Y-m-d H:i:s') . "] No expired orders found.\n";
    else echo '[' . date('Y-m-d H:i:s') . "] Done — $count order(s) cancelled.\n";

} catch (Exception $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
