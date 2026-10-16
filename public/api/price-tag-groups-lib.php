<?php
// Group price tags — one label for several products that share a price
// (e.g. "Vaseline Body Lotion — 8 000 FCFA" for every scent). A group stores only
// its name and member product names; the price always comes from the members'
// live prices, so a group label can never disagree with the till.

require_once __DIR__ . '/db.php';

function priceTagGroupsPdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS price_tag_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        products TEXT NOT NULL,
        price_mode ENUM('max','from') NOT NULL DEFAULT 'max',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    return $pdo;
}

// [['id', 'name', 'products' => [original names], 'price_mode']]
function loadPriceTagGroups() {
    $groups = [];
    try {
        foreach (priceTagGroupsPdo()->query('SELECT id, name, products, price_mode FROM price_tag_groups ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $groups[] = [
                'id'         => (int)$r['id'],
                'name'       => $r['name'],
                'products'   => json_decode($r['products'], true) ?: [],
                'price_mode' => $r['price_mode'],
            ];
        }
    } catch (Exception $e) { /* no groups if DB unavailable */ }
    return $groups;
}

// Product types the suggester recognises, most specific first: [regex, label]
const PRICE_TAG_GROUP_TYPES = [
    ['/\blotion\b/i',                           'Lotion'],
    ['/\bbody wash\b/i',                        'Body Wash'],
    ['/\bbody mist\b/i',                        'Body Mist'],
    ['/\bshampoo (&|and) conditioner\b/i',      'Shampoo & Conditioner'],
    ['/\bhair colou?r\b/i',                     'Hair Color'],
    ['/\bdeodorant\b/i',                        'Deodorant'],
    ['/\bair freshener\b/i',                    'Air Freshener'],
    ['/\btoothpaste\b/i',                       'Toothpaste'],
    ['/\bmoisturi[sz]er\b|\bcream\b/i',         'Moisturizer'],
    ['/\bsmart ?watch\b/i',                     'Smartwatch'],
    ['/\bspeaker\b/i',                          'Speaker'],
    ['/\bearbuds\b/i',                          'Earbuds'],
    ['/\bhead(phones|set)\b/i',                 'Headphones'],
    ['/\bstorage bags\b/i',                     'Storage Bags'],
    ['/\b(perfume|parfum|cologne|edp|edt)\b/i', 'Perfume'],
];

// Gadget types group across brands — "Smart Watches — 15 000" beats one tag per model
// (GT4, Z68, PLEIVO…). Type => label printed on the tag.
const PRICE_TAG_GROUP_ANY_BRAND = [
    'Smartwatch' => 'Smart Watches',
    'Speaker'    => 'Speakers',
    'Earbuds'    => 'Earbuds',
    'Headphones' => 'Headphones',
];

// Brand = first word, or first two when the first is a short prefix ("Dr Teal's", "St. Ives", "So Fresh")
function priceTagGroupBrand($name) {
    $words = preg_split('/\s+/', trim($name));
    if (count($words) > 1 && (mb_strlen($words[0]) <= 2 || substr($words[0], -1) === '.')) {
        return $words[0] . ' ' . $words[1];
    }
    return $words[0];
}

// Variant names can be a whole sentence — keep the part before the first comma/dash/full stop, max 6 words
function priceTagGroupShortName($name) {
    $short = preg_split('/,|\s[-–]\s|\.\s/u', $name)[0];
    $words = preg_split('/\s+/', trim($short));
    return implode(' ', array_slice($words, 0, 6));
}

// Suggests groups among products not already grouped. Products are matched by brand
// + product type (gadgets by type alone; failing a known type, as colour variants of one product), then
// split by price — a label can only show one price. Prices that differ within a match
// are reported, since they're often a pricing mistake worth checking.
// $products: [['name', 'display', 'price', 'original_price'?]] with live prices applied.
function suggestPriceTagGroups($products, $groups) {
    $grouped = [];
    foreach ($groups as $g) foreach ($g['products'] as $n) $grouped[$n] = true;

    $buckets = [];
    foreach ($products as $p) {
        if (isset($grouped[$p['name']]) || empty($p['price'])) continue;
        $display = $p['display'];
        $brand   = priceTagGroupBrand($display);
        $type    = null;
        foreach (PRICE_TAG_GROUP_TYPES as [$re, $label]) {
            if (!preg_match($re, $display, $m, PREG_OFFSET_CAPTURE)) continue;
            // Brand-grouped types: skip when the "brand" is really the type itself ("Smart Watch …" has no brand)
            if (array_key_exists($label, PRICE_TAG_GROUP_ANY_BRAND) || $m[0][1] >= strlen($brand)) { $type = $label; break; }
        }
        // Category keeps kids' and adult versions apart (Dr Teal's Kids body wash vs Dr Teal's body wash)
        $cat = mb_strtolower($p['category'] ?? '');
        if ($type !== null && array_key_exists($type, PRICE_TAG_GROUP_ANY_BRAND)) {
            $key   = mb_strtolower($cat . '|any|' . $type);
            $label = PRICE_TAG_GROUP_ANY_BRAND[$type];
        } elseif ($type !== null) {
            $key   = mb_strtolower($cat . '|' . $brand . '|' . $type);
            $kids  = strpos($cat, 'kids') !== false && preg_match('/\bkids\b/i', $display) ? ' Kids' : '';
            $label = $brand . $kids . ' ' . $type;
        } else {
            // Colour variants: "Rechargeable Portable Fan (Pink)" → "Rechargeable Portable Fan"
            $base  = preg_replace('/\s*\([^()]*\)$/', '', $display);
            $key   = mb_strtolower($cat . '|variant|' . $base);
            $label = priceTagGroupShortName($base);
        }
        $buckets[$key]['label'] = $label;
        $buckets[$key]['items'][] = $p;
    }

    $suggestions = [];
    foreach ($buckets as $b) {
        if (count($b['items']) < 2) continue;
        $byPrice = [];
        foreach ($b['items'] as $p) $byPrice[(int)$p['price']][] = $p;
        krsort($byPrice);
        foreach ($byPrice as $price => $items) {
            if (count($items) < 2) continue;
            $name = $b['label'];
            // "Vaseline Lotion" → "Vaseline Body Lotion" when every member is a body lotion
            if (substr($name, -7) === ' Lotion') {
                $allBody = true;
                foreach ($items as $p) if (!preg_match('/\bbody lotion\b/i', $p['display'])) $allBody = false;
                if ($allBody) $name = substr($name, 0, -7) . ' Body Lotion';
            }
            $others = [];
            foreach ($byPrice as $op => $oitems) {
                if ($op === $price) continue;
                foreach ($oitems as $o) $others[] = ['name' => $o['display'], 'price' => $op];
            }
            $suggestions[] = [
                'name'     => $name,
                'price'    => $price,
                'products' => array_map(fn($p) => $p['name'], $items),
                'displays' => array_map(fn($p) => $p['display'], $items),
                'others'   => $others,
            ];
        }
    }
    usort($suggestions, fn($a, $b) => strcmp($a['name'], $b['name']) ?: $b['price'] <=> $a['price']);
    return $suggestions;
}
