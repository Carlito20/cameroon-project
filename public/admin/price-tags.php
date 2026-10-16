<?php
require_once __DIR__ . '/../api/admin-session.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/discount-lib.php';
require_once __DIR__ . '/../api/product-names-lib.php';
require_once __DIR__ . '/../api/price-tag-groups-lib.php';

$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
}

// Merge in custom products from DB
try {
    $pdoCustom = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $customRows = $pdoCustom->query('SELECT name, price FROM custom_products')->fetchAll(PDO::FETCH_ASSOC);
    $existingNames = array_column($products, 'name');
    foreach ($customRows as $row) {
        if (!in_array($row['name'], $existingNames)) {
            $products[] = ['name' => $row['name'], 'price' => (int)$row['price']];
        }
    }
} catch (Exception $e) { /* custom_products table optional */ }

// Apply live price overrides from DB
try {
    $pdoP = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdoP->query('SELECT product_name, price FROM product_prices');
    $dbPrices = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dbPrices[$row['product_name']] = (int)$row['price'];
    }
    foreach ($products as &$p) {
        if (isset($dbPrices[$p['name']])) {
            $p['price'] = $dbPrices[$p['name']];
        }
    }
    unset($p);
} catch (Exception $e) { /* price overrides optional */ }

// Apply active discounts — tag shows the sale price with the old price struck through
$activeDiscounts = loadActiveDiscounts();
foreach ($products as &$p) {
    if (!isset($p['price'])) continue;
    [$final, $orig] = discountedPrice($activeDiscounts, $p['name'], (int)$p['price'], $p['category'] ?? null);
    if ($orig !== null) { $p['price'] = $final; $p['original_price'] = $orig; }
}
unset($p);

// Load live stock from DB
try {
    $pdoS = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmtS = $pdoS->query('SELECT product_name, quantity FROM product_stock');
    $dbStock = [];
    foreach ($stmtS->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dbStock[$row['product_name']] = (int)$row['quantity'];
    }
    foreach ($products as &$p) {
        if (isset($dbStock[$p['name']])) {
            $p['quantity'] = $dbStock[$p['name']];
        }
    }
    unset($p);
} catch (Exception $e) { /* stock optional */ }

usort($products, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

function fmt_price($n) {
    return number_format((float)$n, 0, '.', ' ') . ' FCFA';
}

// ── Group labels — price comes from the members' live prices ──
$productNames = loadProductNames();
$byName = [];
foreach ($products as $p) {
    if (!empty($p['name']) && isset($p['price'])) $byName[$p['name']] = $p;
}

$groups = loadPriceTagGroups();
foreach ($groups as &$g) {
    // Members no longer in the catalog are dropped from the label (and from the group on next save)
    $members = array_values(array_filter($g['products'], fn($n) => isset($byName[$n])));
    $g['missing']  = count($g['products']) - count($members);
    $g['products'] = $members;
    $g['displays'] = array_map(fn($n) => productDisplayName($n, $productNames), $members);
    $prices = array_map(fn($n) => (int)$byName[$n]['price'], $members);
    $origs  = array_map(fn($n) => $byName[$n]['original_price'] ?? null, $members);
    $distinct = array_values(array_unique($prices));
    rsort($distinct);
    $g['prices']     = $distinct;
    $g['price_text'] = '';
    $g['was_text']   = '';
    if ($distinct) {
        if (count($distinct) === 1) {
            $g['price_text'] = fmt_price($distinct[0]);
            // "Was" line only when every member has the same sale
            if ($origs[0] !== null && count(array_unique($origs)) === 1) $g['was_text'] = fmt_price($origs[0]);
        } else {
            $g['price_text'] = $g['price_mode'] === 'from' ? '<span class="tag-from">From</span> ' . fmt_price(min($distinct)) : fmt_price(max($distinct));
        }
    }
}
unset($g);

$suggestions = suggestPriceTagGroups(array_map(fn($p) => [
    'name'    => $p['name'],
    'display' => productDisplayName($p['name'], $productNames),
    'price'    => (int)$p['price'],
    'category' => $p['category'] ?? '',
], array_values($byName)), $groups);

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Price Tags — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a;
      color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
      min-height: 100dvh;
      -webkit-overflow-scrolling: touch;
    }

    /* ── Header ─────────────────────────────────────────────── */
    header {
      background: #111;
      border-bottom: 1px solid #222;
      padding: 16px 24px;
      padding-top: calc(16px + env(safe-area-inset-top, 0px));
      padding-left: calc(24px + env(safe-area-inset-left, 0px));
      padding-right: calc(24px + env(safe-area-inset-right, 0px));
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    header h1 { color: #d4af37; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    .header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      min-height: 44px;
      -webkit-tap-highlight-color: transparent;
      -webkit-appearance: none;
      appearance: none;
    }
    .btn-gold { background: #d4af37; color: #000; }
    .btn-gold:hover { background: #e8c547; }
    .btn:disabled { opacity: 0.45; cursor: default; }
    .btn-outline { background: transparent; color: #888; border: 1px solid #333; }
    .btn-outline:hover { color: #ccc; border-color: #555; }
    .btn-danger { background: transparent; color: #e05050; border: 1px solid #3a1a1a; }

    /* ── Controls ────────────────────────────────────────────── */
    .controls {
      max-width: 1100px;
      margin: 20px auto 0;
      padding: 0 16px;
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .search-input {
      flex: 1;
      min-width: 220px;
      padding: 10px 14px;
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 8px;
      color: #e0e0e0;
      font-size: 14px;
      -webkit-appearance: none;
      appearance: none;
      min-height: 44px;
    }
    .search-input::placeholder { color: #555; }
    .search-input:focus { outline: none; border-color: #d4af37; }
    .select-all-wrap {
      display: flex; align-items: center; gap: 8px;
      color: #888; font-size: 13px; cursor: pointer;
      -webkit-user-select: none; user-select: none;
    }
    .select-all-wrap input[type=checkbox] { width: 18px; height: 18px; accent-color: #d4af37; cursor: pointer; }
    .count-badge {
      background: #1e1e1e; border: 1px solid #2a2a2a;
      border-radius: 20px; padding: 4px 12px; font-size: 12px; color: #888;
    }

    /* ── Print mode toggle ───────────────────────────────────── */
    .mode-row {
      max-width: 1100px;
      margin: 12px auto 0;
      padding: 0 16px;
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .mode-label { font-size: 12px; color: #666; margin-right: 4px; }
    .mode-btn {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid #333;
      background: transparent;
      color: #666;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      min-height: 36px;
      -webkit-tap-highlight-color: transparent;
      -webkit-appearance: none;
      appearance: none;
      transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .mode-btn.active {
      background: #1e1e1e;
      color: #d4af37;
      border-color: #3a3010;
    }
    .mode-hint { font-size: 11px; color: #444; }

    /* ── Tag Grid (screen) ──────────────────────────────────── */
    .tag-grid {
      max-width: 1100px;
      margin: 16px auto 40px;
      padding: 0 16px;
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 12px;
    }
    .tag-card {
      background: #161616;
      border: 1px solid #2a2a2a;
      border-radius: 10px;
      padding: 14px 14px 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    }
    .tag-card:hover { border-color: #444; background: #1c1c1c; }
    .tag-card.selected { border-color: #d4af37; background: #1c1810; }
    .tag-card-top { display: flex; align-items: flex-start; gap: 10px; }
    .tag-card-top input[type=checkbox] {
      margin-top: 3px; width: 16px; height: 16px;
      accent-color: #d4af37; cursor: pointer; flex-shrink: 0;
    }
    .tag-name { font-size: 13px; font-weight: 600; color: #ddd; line-height: 1.4; }
    .tag-price { font-size: 18px; font-weight: 800; color: #d4af37; letter-spacing: 0.5px; }
    .tag-from { font-size: 0.6em; font-weight: 700; }
    .tag-was { font-size: 12px; font-weight: 600; color: #888; text-decoration: line-through; }
    .tag-was .tag-sale { display: inline-block; text-decoration: none; color: #e05050; font-weight: 800; margin-left: 6px; }
    @media print { .tag-was, .tag-was .tag-sale { color: #000 !important; } }
    /* Phones: 16px text in fields so iOS Safari doesn't zoom in on focus */
    @media (max-width: 768px) {
      input:not([type=checkbox]):not([type=radio]):not([type=file]), select, textarea { font-size: 16px !important; }
    }
    .tag-store { font-size: 10px; color: #555; letter-spacing: 1px; text-transform: uppercase; }
    .tag-qty { font-size: 11px; color: #555; }

    /* ── Group labels ───────────────────────────────────────── */
    .grid-heading {
      grid-column: 1 / -1;
      font-size: 12px; font-weight: 700; color: #d4af37;
      letter-spacing: 1px; text-transform: uppercase;
      padding: 8px 2px 0;
    }
    .grid-heading span { color: #555; font-weight: 400; text-transform: none; letter-spacing: 0; }
    .group-card { border-style: dashed; border-color: #3a3010; }
    .group-card.just-saved { border-style: solid; border-color: #d4af37; box-shadow: 0 0 0 3px rgba(212,175,55,0.35); }
    .custom-card { border-style: dashed; border-color: #1a2a40; }
    .custom-card.selected { border-style: solid; }
    .custom-badge {
      font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #7b9fd4;
      background: #101a28; border-radius: 10px; padding: 3px 8px; margin-top: 1px;
    }
    /* Name-only custom label: the text sits where the price goes, so it prints big */
    .tag-card.no-price .tag-price { word-break: normal !important; overflow-wrap: anywhere; line-height: 1.15; }
    .tag-card .tag-name:empty { display: none !important; }
    .group-card.selected { border-style: solid; }
    .group-badge {
      font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #d4af37;
      background: #1e1a0c; border-radius: 10px; padding: 3px 8px; margin-top: 1px;
    }
    .group-edit {
      margin-left: auto; background: transparent; color: #888;
      border: 1px solid #333; border-radius: 6px; font-size: 12px; font-weight: 600;
      padding: 0 12px; min-height: 32px; min-width: 44px; cursor: pointer;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; -webkit-appearance: none; appearance: none;
    }
    .group-edit:hover { color: #ccc; border-color: #555; }
    .group-warn { font-size: 11px; color: #e0a040; line-height: 1.4; }
    .group-empty { font-size: 13px; color: #666; font-weight: 600; }
    .group-members { font-size: 11px; color: #777; }
    .group-members summary {
      cursor: pointer; color: #666; padding: 4px 0; list-style-position: inside;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
    }
    .group-members ul { list-style: none; display: flex; flex-direction: column; gap: 3px; margin-top: 4px; }
    .group-members b { color: #999; white-space: nowrap; }

    /* ── Modal ──────────────────────────────────────────────── */
    .modal-backdrop {
      display: none;
      position: fixed; inset: 0; z-index: 200;
      background: rgba(0,0,0,0.7);
      -webkit-backdrop-filter: blur(4px);
      backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
      padding: 16px;
      padding-top: calc(16px + env(safe-area-inset-top, 0px));
      padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      transform: translateZ(0);
      will-change: transform;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
      background: #141414; border: 1px solid #2a2a2a; border-radius: 12px;
      width: 100%; max-width: 620px; max-height: 100%;
      display: flex; flex-direction: column; overflow: hidden;
    }
    .modal-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 18px; border-bottom: 1px solid #222;
    }
    .modal-head h2 { font-size: 16px; color: #d4af37; }
    .modal-close {
      background: transparent; border: none; color: #888; font-size: 26px; line-height: 1;
      min-width: 44px; min-height: 44px; cursor: pointer;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; -webkit-appearance: none; appearance: none;
    }
    .modal-body {
      padding: 16px 18px; overflow-y: auto; flex: 1;
      -webkit-overflow-scrolling: touch; overscroll-behavior: contain;
      display: flex; flex-direction: column; gap: 14px;
    }
    .modal-foot {
      display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;
      padding: 12px 18px; border-top: 1px solid #222;
    }
    .modal-foot .btn-danger { margin-right: auto; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field > span { font-size: 12px; color: #888; }
    .field input[type=text], .field select {
      padding: 10px 12px; background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
      color: #e0e0e0; font-size: 14px; min-height: 44px;
      -webkit-appearance: none; appearance: none;
    }
    .field input[type=text]:focus, .field select:focus { outline: none; border-color: #d4af37; }
    .field select {
      padding-right: 38px; cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23d4af37' stroke-width='2'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 14px center;
    }
    .field input[type=text]:disabled { opacity: 0.55; }
    .modal-note { font-size: 12px; color: #888; line-height: 1.5; }
    .modal-note.warn { color: #e0a040; }
    .member-list { list-style: none; display: flex; flex-direction: column; gap: 2px; }
    .member-list label {
      display: flex; align-items: center; gap: 10px; font-size: 13px; color: #ccc;
      padding: 6px 4px; min-height: 40px; cursor: pointer;
      -webkit-user-select: none; user-select: none; touch-action: manipulation;
    }
    .member-list input[type=checkbox] { width: 18px; height: 18px; accent-color: #d4af37; flex-shrink: 0; }
    .member-list .m-price { margin-left: auto; color: #d4af37; font-weight: 700; white-space: nowrap; }
    .sug {
      border: 1px solid #2a2a2a; border-radius: 10px; padding: 12px;
      display: flex; flex-direction: column; gap: 8px;
    }
    .sug.off { opacity: 0.45; }
    .sug-top { display: flex; align-items: center; gap: 10px; }
    .sug-top input[type=checkbox] { width: 20px; height: 20px; accent-color: #d4af37; flex-shrink: 0; }
    .sug-top input[type=text] {
      flex: 1; min-width: 0; padding: 8px 10px; background: #1a1a1a; border: 1px solid #333;
      border-radius: 6px; color: #e0e0e0; font-size: 14px; font-weight: 600; min-height: 40px;
      -webkit-appearance: none; appearance: none;
    }
    .sug-top input[type=text]:focus { outline: none; border-color: #d4af37; }
    .sug-price { font-size: 15px; font-weight: 800; color: #d4af37; white-space: nowrap; }
    @media (max-width: 520px) {
      .sug-top { flex-wrap: wrap; }
      .sug-top input[type=text] { flex-basis: calc(100% - 30px); }
      .sug-price { padding-left: 30px; }
    }
    .sug ul { list-style: none; font-size: 12px; color: #888; display: flex; flex-direction: column; gap: 2px; padding-left: 30px; }

    /* ── Empty ───────────────────────────────────────────────── */
    .empty { text-align: center; color: #444; padding: 60px 20px; font-size: 15px; }

    /* ════════════════════════════════════════════════════════════
       PRINT — A4 SHEET MODE (default)
    ════════════════════════════════════════════════════════════ */
    @media print {
      body.mode-sheet { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-sheet header,
      body.mode-sheet .controls,
      body.mode-sheet .mode-row,
      body.mode-sheet .no-print { display: none !important; }

      body.mode-sheet .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6mm;
      }
      body.mode-sheet @page { size: A4; margin: 10mm; }

      body.mode-sheet .tag-card { display: none !important; }
      body.mode-sheet .tag-card.selected {
        display: flex !important;
        background: #fff !important;
        border: 1.5px solid #000 !important;
        border-radius: 6px;
        padding: 7mm 7mm 6mm;
        gap: 4px;
        page-break-inside: avoid;
        break-inside: avoid;
        flex-direction: column;
      }
      body.mode-sheet .tag-card-top input[type=checkbox] { display: none !important; }
      body.mode-sheet .tag-card-top { gap: 0; }
      body.mode-sheet .tag-name { font-size: 10pt; font-weight: 700; color: #000 !important; line-height: 1.35; }
      body.mode-sheet .tag-price { font-size: 16pt; font-weight: 900; color: #000 !important; }
      body.mode-sheet .tag-store { font-size: 7pt; color: #555; letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }
      body.mode-sheet .tag-qty { display: none !important; }
      body.mode-sheet .empty { display: none !important; }

      /* ════════════════════════════════════════════════════════
         PRINT — SHELF MODE (Munbyn 2" × 1" shelf labels)
      ════════════════════════════════════════════════════════ */
      body.mode-shelf { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-shelf header,
      body.mode-shelf .controls,
      body.mode-shelf .mode-row,
      body.mode-shelf .no-print { display: none !important; }

      body.mode-shelf .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-shelf .tag-card { display: none !important; }
      body.mode-shelf .tag-card.selected {
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff !important;
        border: none !important;
        border-radius: 0;
        padding: 1mm 3mm;
        width: 51mm;
        height: 25mm;
        min-height: 25mm;
        max-height: 25mm;
        overflow: hidden;
        box-sizing: border-box;
        text-align: center;
        page-break-after: always;
        break-after: page;
      }
      body.mode-shelf .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-shelf .tag-card-top { display: none !important; }
      body.mode-shelf .tag-price {
        font-size: 15pt;
        font-weight: 900;
        color: #000 !important;
        line-height: 1.1;
        word-break: break-all;
      }
      body.mode-shelf .tag-name {
        font-size: 6.5pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-top: 1mm;
      }
      body.mode-shelf .tag-store {
        font-size: 4pt;
        color: #777;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-top: 0.5mm;
      }
      body.mode-shelf .tag-qty { display: none !important; }
      body.mode-shelf .empty { display: none !important; }

      /* ════════════════════════════════════════════════════════
         PRINT — 3×2 MODE (Munbyn 3" × 2" product labels)
      ════════════════════════════════════════════════════════ */
      body.mode-3x2 { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-3x2 header,
      body.mode-3x2 .controls,
      body.mode-3x2 .mode-row,
      body.mode-3x2 .no-print { display: none !important; }

      body.mode-3x2 .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-3x2 .tag-card { display: none !important; }
      body.mode-3x2 .tag-card.selected {
        display: block !important;
        position: relative;
        background: #fff !important;
        border: 1pt solid #000 !important;
        border-radius: 0;
        padding: 4mm 5mm;
        width: 76mm;
        height: 51mm;
        min-height: 51mm;
        max-height: 51mm;
        page-break-after: always;
        break-after: page;
        overflow: hidden;
        box-sizing: border-box;
      }
      body.mode-3x2 .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-3x2 .tag-card-top { display: none !important; }
      body.mode-3x2 .tag-name {
        order: 1;
        font-size: 9pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      body.mode-3x2 .tag-price {
        order: 2;
        font-size: 26pt;
        font-weight: 900;
        color: #000 !important;
        letter-spacing: 0.5px;
        line-height: 1;
        margin: 2mm 0 1mm;
      }
      body.mode-3x2 .tag-store {
        order: 3;
        font-size: 6.5pt;
        color: #555;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        border-top: 0.5pt solid #bbb;
        padding-top: 1.5mm;
      }
      body.mode-3x2 .tag-qty { display: none !important; }
      body.mode-3x2 .empty { display: none !important; }

      /* ════════════════════════════════════════════════════════
         PRINT — PHOMEMO MODE (Phomemo M110 40mm × 30mm labels)
         Not sent to a Windows printer — used only as a preview;
         actual output comes from "Download Labels (PNG)" below,
         printed via the Phomemo phone app.
      ════════════════════════════════════════════════════════ */
      body.mode-phomemo { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-phomemo header,
      body.mode-phomemo .controls,
      body.mode-phomemo .mode-row,
      body.mode-phomemo .no-print { display: none !important; }

      body.mode-phomemo .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-phomemo .tag-card { display: none !important; }
      body.mode-phomemo .tag-card.selected {
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff !important;
        border: none !important;
        border-radius: 0;
        padding: 1.5mm 2.5mm;
        width: 40mm;
        height: 30mm;
        min-height: 30mm;
        max-height: 30mm;
        overflow: hidden;
        box-sizing: border-box;
        text-align: center;
        page-break-after: always;
        break-after: page;
      }
      body.mode-phomemo .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-phomemo .tag-card-top { display: none !important; }
      body.mode-phomemo .tag-price {
        font-size: 15pt;
        font-weight: 900;
        color: #000 !important;
        line-height: 1.1;
        word-break: break-all;
      }
      body.mode-phomemo .tag-name {
        font-size: 6.5pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.25;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-top: 1mm;
      }
      body.mode-phomemo .tag-store {
        font-size: 4.5pt;
        color: #777;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 0.5mm;
      }
      body.mode-phomemo .tag-qty { display: none !important; }
      body.mode-phomemo .empty { display: none !important; }
    }
  </style>

  <!-- Dynamic @page size injected by JS based on selected mode -->
  <style id="page-size-style">
    @page { size: A4; margin: 10mm; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
</head>
<body class="mode-sheet">

<header>
  <h1>Price Tags</h1>
  <div class="header-actions">
    <button class="btn btn-gold no-print" id="print-btn" onclick="printSelected()">Print Selected</button>
    <button class="btn btn-outline no-print" id="munbyn-btn" onclick="printToMunbyn()" style="color:#6dbf6d;border-color:#1a3020;">&#9889; Print to Munbyn</button>
    <button class="btn btn-outline no-print" id="pdf-btn" onclick="downloadPNGs()" style="color:#7b9fd4;border-color:#1a2a40;">&#11015; Download Labels (PNG)</button>
    <a href="dashboard.php" class="btn btn-outline no-print">Dashboard</a>
    <a href="logout.php" class="btn btn-danger no-print">Logout</a>
  </div>
</header>

<div class="controls no-print">
  <input class="search-input" type="search" id="search" placeholder="Search products..." oninput="filterTags()">
  <label class="select-all-wrap">
    <input type="checkbox" id="select-all" onchange="toggleSelectAll(this.checked)">
    Select All
  </label>
  <span class="count-badge" id="count-badge">0 selected</span>
  <button class="btn btn-outline" onclick="openCustomModal()">&#43; Custom Label</button>
  <button class="btn btn-outline" id="group-btn" onclick="openGroupModal()">&#43; Group Selected</button>
  <button class="btn btn-outline" onclick="openSuggestModal()" style="color:#d4af37;border-color:#3a3010;">&#10024; Suggest Groups<?= $suggestions ? ' (' . count($suggestions) . ')' : '' ?></button>
</div>

<?php if ($groups): ?>
<div class="mode-row no-print">
  <span class="mode-label">Show:</span>
  <button class="mode-btn view-btn active" data-view="all" onclick="setView('all')">All</button>
  <button class="mode-btn view-btn" data-view="groups" onclick="setView('groups')">Groups (<?= count($groups) ?>)</button>
  <button class="mode-btn view-btn" data-view="products" onclick="setView('products')">Products</button>
</div>
<?php endif; ?>

<div class="mode-row no-print">
  <span class="mode-label">Print on:</span>
  <button class="mode-btn active" id="mode-sheet" onclick="setMode('sheet')">A4 Sheet (3 per row)</button>
  <button class="mode-btn" id="mode-shelf" onclick="setMode('shelf')">2"×1" Label (Shelf)</button>
  <button class="mode-btn" id="mode-3x2" onclick="setMode('3x2')">3"×2" Label (Product)</button>
  <button class="mode-btn" id="mode-phomemo" onclick="setMode('phomemo')">Phomemo M110 (40×30mm)</button>
  <span class="mode-hint" id="mode-hint">Multiple tags per page, cut apart after printing</span>
</div>

<div class="tag-grid" id="tag-grid">
<?php if ($groups): ?>
  <div class="grid-heading no-print" id="heading-groups">Group labels <span>&middot; one tag covers several products at the same price</span></div>
<?php foreach ($groups as $g):
  $searchText = mb_strtolower($g['name'] . ' ' . implode(' ', $g['displays']));
  $mismatch = count($g['prices']) > 1;
?>
  <div class="tag-card group-card" data-name="<?= htmlspecialchars($searchText) ?>" data-group-id="<?= $g['id'] ?>" onclick="toggleCard(this)">
    <div class="tag-card-top">
      <input type="checkbox" class="tag-check" onclick="event.stopPropagation(); syncCheck(this)" onchange="updateCount()" <?= $g['price_text'] === '' ? 'disabled' : '' ?>>
      <span class="group-badge no-print">GROUP &middot; <?= count($g['products']) ?> products</span>
      <button type="button" class="group-edit no-print" onclick="event.stopPropagation(); openEditModal(<?= $g['id'] ?>)">Edit</button>
    </div>
    <?php if ($mismatch): ?>
    <div class="group-warn no-print">&#9888; Prices differ: <?= implode(' / ', array_map(fn($x) => number_format($x, 0, '.', ' '), $g['prices'])) ?>. Label shows <?= $g['price_mode'] === 'from' ? '"From" lowest' : 'the highest' ?> price.</div>
    <?php endif; ?>
    <?php if ($g['missing']): ?>
    <div class="group-warn no-print">&#9888; <?= $g['missing'] ?> product<?= $g['missing'] > 1 ? 's' : '' ?> in this group no longer exist.</div>
    <?php endif; ?>
    <?php if ($g['was_text']): ?>
    <div class="tag-was"><?= $g['was_text'] ?><span class="tag-sale">SALE</span></div>
    <?php endif; ?>
    <div class="tag-price"><?= $g['price_text'] !== '' ? $g['price_text'] : '<span class="group-empty">No products</span>' ?></div>
    <div class="tag-name"><?= htmlspecialchars($g['name']) ?></div>
    <div class="tag-store">American Select</div>
    <details class="group-members no-print" onclick="event.stopPropagation()">
      <summary>Show products</summary>
      <ul>
        <?php foreach ($g['products'] as $i => $n): ?>
        <li><?= htmlspecialchars($g['displays'][$i]) ?> <b><?= number_format((int)$byName[$n]['price'], 0, '.', ' ') ?></b></li>
        <?php endforeach; ?>
      </ul>
    </details>
  </div>
<?php endforeach; ?>
  <div class="grid-heading no-print" id="heading-products">Individual products</div>
<?php endif; ?>
<?php foreach ($products as $p):
  $name  = htmlspecialchars(productDisplayName($p['name'] ?? ''));
  $price = isset($p['price']) ? fmt_price($p['price']) : '';
  $qty   = isset($p['quantity']) ? (int)$p['quantity'] : 0;
  if (!$name || !$price) continue;
?>
  <div class="tag-card" data-name="<?= strtolower($name) ?>" data-key="<?= htmlspecialchars($p['name']) ?>" onclick="toggleCard(this)">
    <div class="tag-card-top">
      <input type="checkbox" class="tag-check" onclick="event.stopPropagation(); syncCheck(this)" onchange="updateCount()">
    </div>
    <?php if (!empty($p['original_price'])): ?>
    <div class="tag-was"><?= fmt_price($p['original_price']) ?><span class="tag-sale">SALE</span></div>
    <?php endif; ?>
    <div class="tag-price"><?= $price ?></div>
    <div class="tag-name"><?= $name ?></div>
    <div class="tag-store">American Select</div>
  </div>
<?php endforeach; ?>
</div>

<div class="empty" id="empty-msg" style="display:none;">No products match your search.</div>

<!-- Group / suggest modal — content built by JS -->
<div class="modal-backdrop no-print" id="modal" onclick="if (event.target === this) closeModal()">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-head">
      <h2 id="modal-title"></h2>
      <button type="button" class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body" id="modal-body"></div>
    <div class="modal-foot" id="modal-foot"></div>
  </div>
</div>

<script>
let currentMode = 'sheet';

function setMode(mode) {
  currentMode = mode;
  document.body.className = 'mode-' + mode;
  document.getElementById('mode-sheet').classList.toggle('active', mode === 'sheet');
  document.getElementById('mode-shelf').classList.toggle('active', mode === 'shelf');
  document.getElementById('mode-3x2').classList.toggle('active', mode === '3x2');
  document.getElementById('mode-phomemo').classList.toggle('active', mode === 'phomemo');

  const pageStyle = document.getElementById('page-size-style');
  const hint = document.getElementById('mode-hint');
  const munbynBtn = document.getElementById('munbyn-btn');

  if (mode === 'shelf') {
    pageStyle.textContent = '@page { size: 51mm 25mm; margin: 0; }';
    hint.textContent = 'One tag per label — load 2"×1" labels on your Munbyn';
  } else if (mode === '3x2') {
    pageStyle.textContent = '@page { size: 76mm 51mm; margin: 0; }';
    hint.textContent = 'One tag per label — load 3"×2" labels on your Munbyn';
  } else if (mode === 'phomemo') {
    pageStyle.textContent = '@page { size: 40mm 30mm; margin: 0; }';
    hint.textContent = 'Not printed from here — use "Download Labels (PNG)" and print from the Phomemo app on your phone';
  } else {
    pageStyle.textContent = '@page { size: A4; margin: 10mm; }';
    hint.textContent = 'Multiple tags per page, cut apart after printing';
  }

  // Munbyn direct-print only supports its two physical label modes
  munbynBtn.style.display = (mode === 'shelf' || mode === '3x2') ? '' : 'none';
}

function toggleCard(card) {
  const cb = card.querySelector('.tag-check');
  if (cb.disabled) return;
  cb.checked = !cb.checked;
  card.classList.toggle('selected', cb.checked);
  updateCount();
}

function syncCheck(cb) {
  cb.closest('.tag-card').classList.toggle('selected', cb.checked);
  updateCount();
}

function toggleSelectAll(checked) {
  document.querySelectorAll('.tag-card:not([style*="display: none"])').forEach(card => {
    const cb = card.querySelector('.tag-check');
    if (cb.disabled) return;
    cb.checked = checked;
    card.classList.toggle('selected', checked);
  });
  updateCount();
}

function updateCount() {
  const n = document.querySelectorAll('.tag-card.selected').length;
  document.getElementById('count-badge').textContent = n + ' selected';
  document.getElementById('print-btn').textContent =
    n > 0 ? 'Print ' + n + ' Tag' + (n !== 1 ? 's' : '') : 'Print Selected';
}

// All / Groups / Products — remembered per browser
let currentView = 'all';
try { currentView = localStorage.getItem('priceTagView') || 'all'; } catch (e) {}

function setView(view) {
  currentView = view;
  try { localStorage.setItem('priceTagView', view); } catch (e) {}
  filterTags();
}

function filterTags() {
  const q = document.getElementById('search').value.toLowerCase().trim();
  const hasGroups = !!document.querySelector('.group-card');
  const view = hasGroups ? currentView : 'all';
  let visible = 0;
  document.querySelectorAll('.tag-card').forEach(card => {
    const isGroup = card.classList.contains('group-card');
    const isCustom = card.classList.contains('custom-card');
    const match = (isCustom || view === 'all' || (view === 'groups') === isGroup) && (!q || card.dataset.name.includes(q));
    card.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  document.querySelectorAll('.view-btn').forEach(b => b.classList.toggle('active', b.dataset.view === view));
  for (const id of ['heading-groups', 'heading-products']) {
    const h = document.getElementById(id);
    if (h) h.style.display = view === 'all' ? '' : 'none';
  }
  const empty = document.getElementById('empty-msg');
  empty.textContent = q ? 'No ' + (view === 'groups' ? 'groups' : 'products') + ' match your search.'
    : view === 'groups' ? 'No groups yet.' : 'No products.';
  empty.style.display = visible === 0 ? 'block' : 'none';
  document.getElementById('select-all').checked = false;
}

function printSelected() {
  const n = document.querySelectorAll('.tag-card.selected').length;
  if (n === 0) { alert('Select at least one product to print.'); return; }
  window.print();
}

updateCount();
filterTags();

// ── Group labels ───────────────────────────────────────
const GROUPS      = <?= json_encode($groups, $jsonFlags) ?>;
const SUGGESTIONS = <?= json_encode($suggestions, $jsonFlags) ?>;
const PRODUCTS    = <?= json_encode(array_map(fn($p) => ['display' => productDisplayName($p['name'], $productNames), 'price' => (int)$p['price']], $byName), $jsonFlags) ?>;

// Bold Arial at `size`, shrunk until `text` fits `maxWidth` ("From 8 000 FCFA" on a 2"×1" label)
function fitFont(ctx, text, maxWidth, size) {
  ctx.font = `bold ${size}px Arial`;
  while (size > 8 && ctx.measureText(text).width > maxWidth) {
    size -= 2;
    ctx.font = `bold ${size}px Arial`;
  }
}

function fmtFcfa(n) { return Number(n).toLocaleString('fr-FR').replace(/\s/g, ' ') + ' FCFA'; }

function el(tag, attrs = {}, ...children) {
  const e = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class') e.className = v;
    else if (k.startsWith('on')) e.addEventListener(k.slice(2), v);
    else if (v === true) e.setAttribute(k, '');
    else if (v !== false && v != null) e.setAttribute(k, v);
  }
  for (const c of children) if (c != null) e.append(c);
  return e;
}

function openModal(title, bodyNodes, footNodes) {
  document.getElementById('modal-title').textContent = title;
  document.getElementById('modal-body').replaceChildren(...bodyNodes);
  document.getElementById('modal-foot').replaceChildren(...footNodes);
  document.getElementById('modal').classList.add('open');
}
function closeModal() { document.getElementById('modal').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

async function saveGroups(payload, btn) {
  if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
  try {
    const r = await fetch('/api/price-tag-groups.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.error || ('HTTP ' + r.status));
    // After the reload, scroll to and highlight what was just saved
    try { sessionStorage.setItem('priceTagSaved', JSON.stringify(data.ids || [])); } catch (e) {}
    location.reload();
  } catch (e) {
    alert('Could not save: ' + e.message);
    if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
  }
}

// Price summary + "highest / From lowest" choice when members' prices differ
function priceSection(names, mode) {
  const prices = [...new Set(names.map(n => PRODUCTS[n]?.price).filter(p => p != null))].sort((a, b) => b - a);
  const wrap = el('div', { class: 'field' });
  if (prices.length <= 1) {
    wrap.append(el('span', {}, 'Label price'), el('div', { class: 'sug-price' }, prices.length ? fmtFcfa(prices[0]) : '—'));
    return { node: wrap, select: null };
  }
  const select = el('select', {},
    el('option', { value: 'max', selected: mode !== 'from' }, 'Show highest: ' + fmtFcfa(prices[0])),
    el('option', { value: 'from', selected: mode === 'from' }, 'Show "From ' + fmtFcfa(prices[prices.length - 1]) + '"'));
  wrap.append(
    el('div', { class: 'modal-note warn' }, '⚠ These products have different prices (' + prices.map(p => p.toLocaleString('fr-FR')).join(' / ') +
      '). Check the prices are right, or choose what the label shows:'),
    select);
  return { node: wrap, select };
}

// Longest run of leading words every name shares — a starting point for the group name
function commonName(names) {
  const split = names.map(n => n.split(/\s+/));
  const out = [];
  for (let i = 0; i < split[0].length; i++) {
    const w = split[0][i];
    if (split.every(s => (s[i] || '').toLowerCase() === w.toLowerCase())) out.push(w); else break;
  }
  return out.join(' ').replace(/[,\-–:]+$/, '');
}

// ── Custom labels — one-off, gone on page reload ────────
// Any text, optional price. Without a price the text takes the price's spot, so it prints big.
function openCustomModal() {
  const textInput = el('input', { type: 'text', maxlength: 80, placeholder: 'e.g. Wireless Speakers' });
  const priceInput = el('input', { type: 'text', inputmode: 'numeric', maxlength: 12, placeholder: 'Leave empty for name only' });
  const add = el('button', { class: 'btn btn-gold', onclick: () => {
    const text = textInput.value.trim().replace(/\s+/g, ' ');
    if (!text) { textInput.focus(); return; }
    const raw = priceInput.value.replace(/[\s.,]/g, '').replace(/fcfa$/i, '');
    if (raw && !/^\d+$/.test(raw)) { alert('Price should be a number, e.g. 10000 — or leave it empty.'); priceInput.focus(); return; }
    addCustomCard(text, raw ? Number(raw) : null);
    closeModal();
  } }, 'Add Label');
  for (const i of [textInput, priceInput]) i.addEventListener('keydown', e => { if (e.key === 'Enter') add.click(); });
  openModal('Custom label', [
    el('label', { class: 'field' }, el('span', {}, 'Label text'), textInput),
    el('label', { class: 'field' }, el('span', {}, 'Price (optional)'), priceInput),
    el('div', { class: 'modal-note' }, 'The label is added to the top of the page, already ticked. It is not saved — it disappears when you leave or reload the page.')
  ], [el('button', { class: 'btn btn-outline', onclick: closeModal }, 'Cancel'), add]);
  textInput.focus();
}

function addCustomCard(text, price) {
  const cb = el('input', { type: 'checkbox', class: 'tag-check', checked: true,
    onclick: e => { e.stopPropagation(); syncCheck(cb); }, onchange: updateCount });
  const remove = el('button', { type: 'button', class: 'group-edit no-print', onclick: e => {
    e.stopPropagation(); card.remove(); updateCount();
  } }, 'Remove');
  const card = el('div', { class: 'tag-card custom-card selected' + (price == null ? ' no-price' : ''), 'data-name': text.toLowerCase() },
    el('div', { class: 'tag-card-top' }, cb, el('span', { class: 'custom-badge no-print' }, 'CUSTOM'), remove),
    el('div', { class: 'tag-price' }, price == null ? text : fmtFcfa(price)),
    el('div', { class: 'tag-name' }, price == null ? '' : text),
    el('div', { class: 'tag-store' }, 'American Select'));
  card.addEventListener('click', () => toggleCard(card));
  document.getElementById('tag-grid').prepend(card);
  filterTags();
  updateCount();
  card.scrollIntoView({ block: 'nearest' });
}

function openGroupModal() {
  const keys = [...document.querySelectorAll('.tag-card.selected[data-key]')].map(c => c.dataset.key);
  if (keys.length < 1) { alert('Tick the products you want on one label first, then click "Group Selected".'); return; }

  // Name box first — type any name. Adding to an existing group is an optional second step.
  const nameInput = el('input', { type: 'text', maxlength: 200, value: commonName(keys.map(k => PRODUCTS[k].display)), placeholder: 'Type a name, e.g. Wireless Earbuds' });
  const nameHint = el('div', { class: 'modal-note warn', style: 'display:none' }, 'Type a name for the group.');
  const nameField = el('label', { class: 'field' }, el('span', {}, 'Group name — type any name, this is what prints on the tag'), nameInput, nameHint);
  const target = el('select', {},
    el('option', { value: '' }, 'No — create a new group with the name above'),
    ...GROUPS.map(g => el('option', { value: g.id }, g.name + ' (' + g.products.length + ' products)')));
  const targetField = GROUPS.length ? el('label', { class: 'field' }, el('span', {}, 'Or add these to a group you already have?'), target) : null;
  const priceBox = el('div');
  const list = el('ul', { class: 'member-list' }, ...keys.map(k => el('li', {},
    el('label', {}, PRODUCTS[k].display, el('span', { class: 'm-price' }, fmtFcfa(PRODUCTS[k].price))))));

  let typedName = nameInput.value;
  function refresh() {
    const g = GROUPS.find(g => String(g.id) === target.value);
    // Adding to an existing group keeps that group's name; switching back restores what was typed
    if (g) { if (!nameInput.disabled) typedName = nameInput.value; nameInput.value = g.name; nameInput.disabled = true; }
    else if (nameInput.disabled) { nameInput.value = typedName; nameInput.disabled = false; }
    const all = g ? [...new Set([...g.products, ...keys])] : keys;
    priceBox.replaceChildren(priceSection(all, g?.price_mode).node);
  }
  target.addEventListener('change', refresh);
  nameInput.addEventListener('input', () => { nameHint.style.display = 'none'; });
  nameInput.addEventListener('keydown', e => { if (e.key === 'Enter') save.click(); });
  refresh();

  const save = el('button', { class: 'btn btn-gold', onclick: () => {
    const g = GROUPS.find(g => String(g.id) === target.value);
    const products = g ? [...new Set([...g.products, ...keys])] : keys;
    const sel = priceBox.querySelector('select');
    const price_mode = sel ? sel.value : (g?.price_mode || 'max');
    if (!g && !nameInput.value.trim()) { nameHint.style.display = ''; nameInput.focus(); return; }
    if (products.length < 2) { alert('A group needs at least 2 products.'); return; }
    saveGroups({ id: g?.id, name: g ? g.name : nameInput.value, products, price_mode }, save);
  } }, 'Save');

  openModal('Group ' + keys.length + ' product' + (keys.length !== 1 ? 's' : ''), [
    nameField, targetField, priceBox,
    el('div', { class: 'field' }, el('span', {}, 'Products'), list)
  ], [el('button', { class: 'btn btn-outline', onclick: closeModal }, 'Cancel'), save]);
  nameInput.focus();
  nameInput.select();
}

function openEditModal(id) {
  const g = GROUPS.find(g => g.id === id);
  if (!g) return;
  const nameInput = el('input', { type: 'text', maxlength: 200, value: g.name });
  const priceBox = el('div');
  const checks = g.products.map((n, i) => el('input', { type: 'checkbox', checked: true, value: n, onchange: refresh }));
  const list = el('ul', { class: 'member-list' }, ...g.products.map((n, i) => el('li', {},
    el('label', {}, checks[i], g.displays[i], el('span', { class: 'm-price' }, fmtFcfa(PRODUCTS[n].price))))));

  function kept() { return checks.filter(c => c.checked).map(c => c.value); }
  function refresh() {
    const prev = priceBox.querySelector('select')?.value || g.price_mode;
    priceBox.replaceChildren(priceSection(kept(), prev).node);
  }
  refresh();

  const save = el('button', { class: 'btn btn-gold', onclick: () => {
    const products = kept();
    if (!nameInput.value.trim()) { nameInput.focus(); return; }
    if (products.length < 2) { alert('A group needs at least 2 products. Delete the group instead?'); return; }
    const sel = priceBox.querySelector('select');
    saveGroups({ id: g.id, name: nameInput.value, products, price_mode: sel ? sel.value : g.price_mode }, save);
  } }, 'Save');
  const del = el('button', { class: 'btn btn-danger', onclick: () => {
    if (confirm('Delete the group "' + g.name + '"? The products themselves are not affected.')) saveGroups({ action: 'delete', id: g.id }, del);
  } }, 'Delete Group');

  openModal('Edit group', [
    el('label', { class: 'field' }, el('span', {}, 'Label name (what prints on the tag)'), nameInput),
    priceBox,
    el('div', { class: 'field' }, el('span', {}, 'Products — untick to remove. To add more, tick products on the page and use "Group Selected".'), list)
  ], [del, el('button', { class: 'btn btn-outline', onclick: closeModal }, 'Cancel'), save]);
}

function openSuggestModal() {
  if (!SUGGESTIONS.length) {
    openModal('Suggest groups', [el('div', { class: 'modal-note' },
      'No new suggestions. Every set of same-brand, same-type products at the same price is already in a group.')],
      [el('button', { class: 'btn btn-outline', onclick: closeModal }, 'Close')]);
    return;
  }
  const rows = SUGGESTIONS.map(s => {
    const cb = el('input', { type: 'checkbox', 'aria-label': 'Create this group' });
    const name = el('input', { type: 'text', maxlength: 200, value: s.name, 'aria-label': 'Group name' });
    const box = el('div', { class: 'sug off' },
      el('div', { class: 'sug-top' }, cb, name, el('span', { class: 'sug-price' }, fmtFcfa(s.price))),
      el('ul', {}, ...s.displays.map(d => el('li', {}, '• ' + d))),
      s.others.length ? el('div', { class: 'modal-note warn' },
        '⚠ Left out (different price — check it\'s right): ' + s.others.map(o => o.name + ' (' + o.price.toLocaleString('fr-FR') + ')').join(', ')) : null);
    cb.addEventListener('change', () => { box.classList.toggle('off', !cb.checked); updateBtn(); });
    return { s, cb, name, box };
  });
  const create = el('button', { class: 'btn btn-gold', onclick: () => {
    const groups = rows.filter(r => r.cb.checked).map(r => ({ name: r.name.value, products: r.s.products }));
    if (!groups.length) return;
    if (groups.some(g => !g.name.trim())) { alert('Every group needs a name.'); return; }
    saveGroups({ groups }, create);
  } });
  function updateBtn() {
    const n = rows.filter(r => r.cb.checked).length;
    create.textContent = n ? 'Create ' + n + ' Group' + (n !== 1 ? 's' : '') : 'Tick groups to create';
    create.disabled = n === 0;
  }
  updateBtn();
  openModal('Suggested groups', [
    el('div', { class: 'modal-note' },
      'Products of the same brand and type (lotion, body wash, deodorant…) or colour variants of one product that have the same price. ' +
      'Tick the ones you want and rename them as you like. The name is what prints on the label.'),
    ...rows.map(r => r.box)
  ], [el('button', { class: 'btn btn-outline', onclick: closeModal }, 'Cancel'), create]);
}

// Highlight groups saved just before this reload
(function showSavedGroups() {
  let ids = [];
  try { ids = JSON.parse(sessionStorage.getItem('priceTagSaved') || '[]'); sessionStorage.removeItem('priceTagSaved'); } catch (e) {}
  const cards = ids.map(id => document.querySelector('.group-card[data-group-id="' + id + '"]')).filter(Boolean);
  if (!cards.length) return;
  if (currentView === 'products') setView('all');
  cards.forEach(c => c.classList.add('just-saved'));
  cards[0].scrollIntoView({ block: 'center' });
  setTimeout(() => cards.forEach(c => c.classList.remove('just-saved')), 4000);
})();

// ── Download PNG labels for Munbyn app import ──────────
async function downloadPNGs() {
  const selected = [...document.querySelectorAll('.tag-card.selected')];
  if (selected.length === 0) { alert('Select at least one product first.'); return; }

  const btn = document.getElementById('pdf-btn');
  btn.textContent = '⏳ Generating…';
  btn.disabled = true;

  const DPI = 203; // Munbyn ITPP130 resolution
  const mode = currentMode;

  // Label size in pixels at 203 DPI
  const MM_PER_IN = 25.4;
  let PW, PH;
  if (mode === '3x2') { PW = Math.round(3 * DPI); PH = Math.round(2 * DPI); } // 609x406
  else if (mode === 'phomemo') { PW = Math.round(40 / MM_PER_IN * DPI); PH = Math.round(30 / MM_PER_IN * DPI); } // 320x240
  else { PW = Math.round(2 * DPI); PH = Math.round(1 * DPI); } // shelf: 406x203

  const canvas = document.createElement('canvas');
  canvas.width = PW;
  canvas.height = PH;
  const ctx = canvas.getContext('2d');

  function wrapText(ctx, text, x, y, maxWidth, lineHeight, maxLines = Infinity) {
    const words = text.split(' ');
    let line = '';
    let lines = [];
    for (const word of words) {
      const test = line ? line + ' ' + word : word;
      if (ctx.measureText(test).width > maxWidth && line) {
        lines.push(line);
        line = word;
      } else { line = test; }
    }
    if (line) lines.push(line);
    lines = lines.slice(0, maxLines);
    lines.forEach((l, i) => ctx.fillText(l, x, y + i * lineHeight));
    return lines.length;
  }

  function drawLabel(price, name) {
    ctx.clearRect(0, 0, PW, PH);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, PW, PH);

    const pad = Math.round(0.08 * DPI); // ~8% of inch padding

    if (mode === '3x2') {
      // Name at top
      ctx.fillStyle = '#000';
      ctx.font = `bold ${Math.round(0.1 * DPI)}px Arial`;
      const nameLines = wrapText(ctx, name, pad, Math.round(0.2 * DPI), PW - pad * 2, Math.round(0.13 * DPI));

      // Price in middle
      fitFont(ctx, price, PW - pad * 2, Math.round(0.28 * DPI));
      ctx.fillText(price, pad, Math.round(0.7 * DPI));

      // Divider
      ctx.strokeStyle = '#ccc'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(pad, Math.round(0.78 * DPI)); ctx.lineTo(PW - pad, Math.round(0.78 * DPI)); ctx.stroke();

      // Store
      ctx.font = `${Math.round(0.07 * DPI)}px Arial`;
      ctx.fillStyle = '#555';
      ctx.fillText('AMERICAN SELECT', pad, Math.round(0.88 * DPI));
    } else if (mode === 'phomemo') {
      // 40mm×30mm: name top, price center, store bottom — all centered
      const cx = PW / 2;
      ctx.fillStyle = '#000';
      ctx.textAlign = 'center';
      ctx.font = `bold ${Math.round(0.09 * DPI)}px Arial`;
      const nameLineHeight = Math.round(0.12 * DPI);
      const nameLines = wrapText(ctx, name, cx, Math.round(0.16 * DPI), PW - pad * 2, nameLineHeight, 2);

      fitFont(ctx, price, PW - pad * 2, Math.round(0.26 * DPI));
      const priceY = Math.round(0.16 * DPI) + nameLines * nameLineHeight + Math.round(0.22 * DPI);
      ctx.fillText(price, cx, priceY);

      ctx.font = `${Math.round(0.055 * DPI)}px Arial`;
      ctx.fillStyle = '#777';
      ctx.fillText('AMERICAN SELECT', cx, PH - Math.round(0.08 * DPI));
    } else {
      // 2"×1" landscape: price centered top, name below, store at bottom
      const cx = PW / 2;
      ctx.fillStyle = '#000';
      fitFont(ctx, price, PW - pad * 2, Math.round(0.34 * DPI));
      ctx.textAlign = 'center';
      ctx.fillText(price, cx, Math.round(0.42 * DPI));

      ctx.font = `bold ${Math.round(0.09 * DPI)}px Arial`;
      const nameLineHeight = Math.round(0.13 * DPI);
      const nameLines = wrapText(ctx, name, cx, Math.round(0.62 * DPI), PW - pad * 2, nameLineHeight, 2);

      ctx.font = `${Math.round(0.055 * DPI)}px Arial`;
      ctx.fillStyle = '#777';
      ctx.fillText('AMERICAN SELECT', cx, Math.round(0.62 * DPI) + nameLines * nameLineHeight + Math.round(0.08 * DPI));
    }
    ctx.textAlign = 'left';
  }

  // Single label — direct download
  if (selected.length === 1) {
    const card = selected[0];
    const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
    const name  = card.querySelector('.tag-name')?.textContent?.trim() || '';
    drawLabel(price, name);
    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = 'label-' + name.substring(0, 30).replace(/[^a-z0-9]/gi, '_') + '.png';
    a.click();
  } else {
    // Multiple labels — zip them
    const zip = new JSZip();
    for (let i = 0; i < selected.length; i++) {
      const card = selected[i];
      const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
      const name  = card.querySelector('.tag-name')?.textContent?.trim() || '';
      drawLabel(price, name);
      const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
      const fname = (i + 1) + '-' + name.substring(0, 25).replace(/[^a-z0-9]/gi, '_') + '.png';
      zip.file(fname, blob);
    }
    const zipBlob = await zip.generateAsync({ type: 'blob' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(zipBlob);
    a.download = 'labels-' + selected.length + '.zip';
    a.click();
  }

  btn.textContent = '⬇ Download Labels (PNG)';
  btn.disabled = false;
}

// ── Direct print to Munbyn via relay ─────────────────────
async function printToMunbyn() {
  const selected = [...document.querySelectorAll('.tag-card.selected')];
  if (selected.length === 0) { alert('Select at least one product to print.'); return; }
  if (currentMode !== 'shelf' && currentMode !== '3x2') {
    alert('Direct Munbyn printing only works with a Munbyn label size selected above — pick "2"×1" Label" or "3"×2" Label" first. For Phomemo, use "Download Labels (PNG)" instead.');
    return;
  }

  const btn = document.getElementById('munbyn-btn');
  const orig = btn.textContent;
  btn.disabled = true;

  const isShelf = currentMode === 'shelf';
  // 300 DPI canvas sized to match the physical label
  const W = isShelf ? 600 : 900;
  const H = isShelf ? 300 : 600;
  const widthIn = isShelf ? 2 : 3;
  const heightIn = isShelf ? 1 : 2;

  const canvas = document.createElement('canvas');
  canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext('2d');

  function wrapLines(text, maxWidth, maxLines = Infinity) {
    const words = text.split(' ');
    const lines = [];
    let line = '';
    for (const word of words) {
      const test = line ? line + ' ' + word : word;
      if (ctx.measureText(test).width > maxWidth && line) {
        lines.push(line); line = word;
      } else line = test;
    }
    if (line) lines.push(line);
    return lines.slice(0, maxLines);
  }

  function drawPriceTag3x2(price, name) {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, W, H);
    const pad = 44;

    // Product name — big, fills top portion
    ctx.fillStyle = '#000';
    ctx.font = 'bold 68px Arial';
    ctx.textAlign = 'left';
    const nameLines = wrapLines(name, W - pad * 2, 3);
    nameLines.forEach((l, i) => ctx.fillText(l, pad, 88 + i * 82));

    // Divider below name
    const divY = nameLines.length === 1 ? 130 : nameLines.length === 2 ? 214 : 298;
    ctx.strokeStyle = '#aaa'; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(pad, divY + 20); ctx.lineTo(W - pad, divY + 20); ctx.stroke();

    // Price — very large, pushed to lower portion
    fitFont(ctx, price, W - pad * 2, 158);
    ctx.textAlign = 'center';
    ctx.fillStyle = '#000';
    ctx.fillText(price, W / 2, 468);

    // Bottom rule
    ctx.strokeStyle = '#aaa';
    ctx.beginPath(); ctx.moveTo(pad, 466); ctx.lineTo(W - pad, 466); ctx.stroke();

    // American Select branding — big and clear
    ctx.font = 'bold 44px Arial';
    ctx.fillStyle = '#111';
    ctx.fillText('American Select', W / 2, 516);

    // Website
    ctx.font = '26px Arial';
    ctx.fillStyle = '#777';
    ctx.fillText('americanselect.net', W / 2, 552);
    ctx.textAlign = 'left';
  }

  function drawPriceTagShelf(price, name) {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, W, H);
    const cx = W / 2;

    // Price — dominant, centered near the top
    ctx.fillStyle = '#000';
    fitFont(ctx, price, W - 40, 92);
    ctx.textAlign = 'center';
    ctx.fillText(price, cx, 118);

    // Product name — small, wraps to at most 2 lines
    ctx.font = 'bold 26px Arial';
    const nameLines = wrapLines(name, W - 60, 2);
    nameLines.forEach((l, i) => ctx.fillText(l, cx, 168 + i * 32));

    // Store name — tiny, bottom
    ctx.font = '18px Arial';
    ctx.fillStyle = '#777';
    ctx.fillText('AMERICAN SELECT', cx, 168 + nameLines.length * 32 + 20);
    ctx.textAlign = 'left';
  }

  const drawFn = isShelf ? drawPriceTagShelf : drawPriceTag3x2;

  let printed = 0, failed = 0;
  for (let i = 0; i < selected.length; i++) {
    const card = selected[i];
    const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
    const name  = card.querySelector('.tag-name')?.textContent?.trim()  || '';
    btn.textContent = 'Printing ' + (i + 1) + '/' + selected.length + '…';

    drawFn(price, name);
    const image = canvas.toDataURL('image/png');

    try {
      const r = await fetch('http://localhost:3099/barcode', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image, widthIn, heightIn })
      });
      if (!r.ok) { const e = await r.json().catch(() => ({})); throw new Error(e.error || r.status); }
      printed++;
    } catch(e) {
      failed++;
      console.error('Label print failed for', name, e.message);
    }
  }

  if (failed === 0) {
    btn.textContent = '✓ Printed ' + printed;
    btn.style.color = '#6dbf6d';
  } else {
    btn.textContent = '⚠ ' + printed + ' ok, ' + failed + ' failed';
    btn.style.color = '#e05c5c';
  }
  setTimeout(() => { btn.textContent = orig; btn.style.color = ''; btn.disabled = false; }, 3000);
}
</script>
<script src="/admin/idle-logout.js" defer></script>
</body>
</html>
