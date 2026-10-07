<?php
// Shared discount helpers — used by discounts.php, checkout, barcode lookup, price tags.
// Discounts target either one product (scope=product) or a whole top-level category
// (scope=category). A product discount always beats a category discount.
// Dates are whole days in Cameroon time: a sale runs from the start of starts_on
// to the end of ends_on. Either date may be NULL (open-ended).

require_once __DIR__ . '/db.php';

const DISCOUNT_TZ = 'Africa/Douala';

function discountPdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_discounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scope ENUM('product','category') NOT NULL DEFAULT 'product',
        target VARCHAR(500) NOT NULL,
        type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        value INT NOT NULL,
        starts_on DATE NULL,
        ends_on DATE NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_scope_target (scope, target)
    )");
    return $pdo;
}

function discountToday() {
    return (new DateTime('now', new DateTimeZone(DISCOUNT_TZ)))->format('Y-m-d');
}

// 'active' | 'scheduled' | 'expired' | 'paused'
function discountStatus(array $row) {
    if (empty($row['active'])) return 'paused';
    $today = discountToday();
    if (!empty($row['starts_on']) && $today < $row['starts_on']) return 'scheduled';
    if (!empty($row['ends_on']) && $today > $row['ends_on']) return 'expired';
    return 'active';
}

function discountPublicShape(array $row) {
    return [
        'type'    => $row['type'],
        'value'   => (int)$row['value'],
        'ends_on' => $row['ends_on'] ?: null,
    ];
}

// All discount rows (admin view), each with a computed status
function loadAllDiscounts() {
    $rows = discountPdo()->query('SELECT * FROM product_discounts ORDER BY scope, target')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['value']  = (int)$r['value'];
        $r['active'] = (int)$r['active'];
        $r['status'] = discountStatus($r);
    }
    unset($r);
    return $rows;
}

// Only discounts in effect right now: ['products' => [name => d], 'categories' => [name => d]]
function loadActiveDiscounts() {
    $out = ['products' => [], 'categories' => []];
    try {
        foreach (loadAllDiscounts() as $r) {
            if ($r['status'] !== 'active') continue;
            $key = $r['scope'] === 'category' ? 'categories' : 'products';
            $out[$key][$r['target']] = discountPublicShape($r);
        }
    } catch (Exception $e) { /* no discounts if DB unavailable */ }
    return $out;
}

// name => top-level category, from products-list.json
function productCategoryMap() {
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    $jsonPath = __DIR__ . '/products-list.json';
    if (file_exists($jsonPath)) {
        foreach (json_decode(file_get_contents($jsonPath), true) ?? [] as $p) {
            if (!empty($p['category'])) $map[$p['name']] = $p['category'];
        }
    }
    return $map;
}

// Colour variants are listed as "Name (Colour)"; a discount on the base name covers them
function findDiscount(array $discounts, $name, $category = null) {
    if (isset($discounts['products'][$name])) return $discounts['products'][$name];
    $base = preg_replace('/\s\([^()]*\)$/', '', $name);
    if ($base !== $name && isset($discounts['products'][$base])) return $discounts['products'][$base];
    if ($category === null) {
        $cats = productCategoryMap();
        $category = $cats[$name] ?? $cats[$base] ?? null;
    }
    if ($category !== null && isset($discounts['categories'][$category])) return $discounts['categories'][$category];
    return null;
}

function applyDiscountPrice($price, $discount) {
    $price = (int)$price;
    if (!$discount || $price <= 0) return $price;
    $final = $discount['type'] === 'percent'
        ? (int)round($price * (100 - $discount['value']) / 100)
        : $price - (int)$discount['value'];
    return max(0, $final);
}

// [final_price, original_price|null, discount|null]
function discountedPrice(array $discounts, $name, $price, $category = null) {
    $d = findDiscount($discounts, $name, $category);
    $final = applyDiscountPrice($price, $d);
    if (!$d || $final === (int)$price) return [(int)$price, null, null];
    return [$final, (int)$price, $d];
}
