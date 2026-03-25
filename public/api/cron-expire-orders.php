<?php
/**
 * cron-expire-orders.php
 * Cancels pending orders older than 24 hours and restores their stock.
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

    // Find all pending orders older than 24 hours
    $stmt = $pdo->query('SELECT * FROM pending_orders WHERE status = "pending" AND created_at < NOW() - INTERVAL 24 HOUR');
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
        $pdo->prepare('UPDATE pending_orders SET status = "cancelled", cancelled_at = NOW(), note = "Auto-cancelled after 24 hours — stock restored" WHERE id = ?')
            ->execute([$order['id']]);

        $count++;
        echo '[' . date('Y-m-d H:i:s') . '] Cancelled ' . $order['order_ref'] . ' — stock restored for ' . count($items) . " item(s)\n";
    }

    if ($count === 0) echo '[' . date('Y-m-d H:i:s') . "] No expired orders found.\n";
    else echo '[' . date('Y-m-d H:i:s') . "] Done — $count order(s) cancelled.\n";

} catch (Exception $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
