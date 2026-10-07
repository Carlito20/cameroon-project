<?php
// Display names set from the admin dashboard. The original product name stays the
// key for stock, prices, discounts, barcodes and orders; only what people see changes.
// Colour variants ("Name (Colour)") follow a rename of their base product.

require_once __DIR__ . '/db.php';

function productNamesPdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_names (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL UNIQUE,
        display_name VARCHAR(500) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    return $pdo;
}

// [original name => display name]
function loadProductNames() {
    static $names = null;
    if ($names !== null) return $names;
    $names = [];
    try {
        foreach (productNamesPdo()->query('SELECT product_name, display_name FROM product_names')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $names[$r['product_name']] = $r['display_name'];
        }
    } catch (Exception $e) { /* no renames if DB unavailable */ }
    return $names;
}

function productDisplayName($name, $names = null) {
    $names = $names ?? loadProductNames();
    if (isset($names[$name])) return $names[$name];
    if (preg_match('/^(.*) \(([^()]*)\)$/', $name, $m) && isset($names[$m[1]])) {
        return $names[$m[1]] . ' (' . $m[2] . ')';
    }
    return $name;
}
