<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

function getDB() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL UNIQUE,
        price INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    return $pdo;
}

// ── Minimal .xlsx reader (ZipArchive + SimpleXML — no external library) ──

function xlsxSharedStrings(ZipArchive $zip) {
    $strings = [];
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) return $strings;
    $doc = new SimpleXMLElement($xml);
    foreach ($doc->si as $si) {
        if (isset($si->t)) {
            $strings[] = (string)$si->t;
        } else {
            $text = '';
            foreach ($si->r as $r) { $text .= (string)$r->t; }
            $strings[] = $text;
        }
    }
    return $strings;
}

function xlsxColIndex($cellRef) {
    preg_match('/^([A-Z]+)/', $cellRef, $m);
    $letters = $m[1] ?? 'A';
    $index = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $index - 1;
}

function xlsxSheetList(ZipArchive $zip) {
    $sheets = [];
    $wbXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if (!$wbXml || !$relsXml) return $sheets;
    $wb = new SimpleXMLElement($wbXml);
    $rels = new SimpleXMLElement($relsXml);
    $relMap = [];
    foreach ($rels->Relationship as $rel) {
        $relMap[(string)$rel['Id']] = (string)$rel['Target'];
    }
    foreach ($wb->sheets->sheet as $sheet) {
        $rAttrs = $sheet->attributes('r', true);
        $rId = (string)$rAttrs['id'];
        $target = $relMap[$rId] ?? null;
        if ($target) {
            $path = 'xl/' . ltrim($target, '/');
            $sheets[] = ['name' => (string)$sheet['name'], 'path' => $path];
        }
    }
    return $sheets;
}

function xlsxParseSheet(ZipArchive $zip, $sheetPath, $sharedStrings) {
    $xml = $zip->getFromName($sheetPath);
    if ($xml === false) return [];
    $doc = new SimpleXMLElement($xml);
    $rows = [];
    if (!isset($doc->sheetData->row)) return $rows;
    foreach ($doc->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $colIndex = xlsxColIndex($ref);
            $type = (string)$c['t'];
            if ($type === 's') {
                $idx = isset($c->v) ? (int)$c->v : -1;
                $value = $idx >= 0 ? ($sharedStrings[$idx] ?? '') : '';
            } elseif ($type === 'inlineStr') {
                $value = isset($c->is->t) ? (string)$c->is->t : '';
            } elseif ($type === 'str') {
                $value = isset($c->v) ? (string)$c->v : '';
            } else {
                $value = isset($c->v) ? (string)$c->v : '';
            }
            $rowData[$colIndex] = $value;
        }
        if (!empty($rowData)) $rows[] = $rowData;
    }
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
if (($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmpPath = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'];
if (strtolower(pathinfo($origName, PATHINFO_EXTENSION)) !== 'xlsx') {
    http_response_code(400);
    echo json_encode(['error' => 'Please upload a .xlsx file']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($tmpPath) !== true) {
    http_response_code(400);
    echo json_encode(['error' => 'Could not read the uploaded file — is it a valid .xlsx?']);
    exit;
}

// Load catalog product names so we only write prices for real products
$jsonPath = __DIR__ . '/products-list.json';
$catalogNames = [];
if (file_exists($jsonPath)) {
    $catalog = json_decode(file_get_contents($jsonPath), true) ?? [];
    foreach ($catalog as $p) {
        if (!empty($p['name'])) $catalogNames[$p['name']] = true;
    }
}

$sharedStrings = xlsxSharedStrings($zip);
$sheets = xlsxSheetList($zip);
$zip->close();

$updated = 0;
$skippedNoPrice = 0;
$notFound = [];

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO product_prices (product_name, price)
         VALUES (:name, :price)
         ON DUPLICATE KEY UPDATE price = VALUES(price)'
    );

    $zip = new ZipArchive();
    $zip->open($tmpPath);

    foreach ($sheets as $sheetInfo) {
        $rows = xlsxParseSheet($zip, $sheetInfo['path'], $sharedStrings);
        if (count($rows) < 2) continue;

        $header = $rows[0];
        $productCol = null;
        $priceCol = null;
        foreach ($header as $idx => $h) {
            $hLower = strtolower(trim($h));
            if ($productCol === null && strpos($hLower, 'product') !== false) $productCol = $idx;
            if (strpos($hLower, 'price') !== false && strpos($hLower, 'current') !== false) $priceCol = $idx;
        }
        if ($priceCol === null) {
            foreach ($header as $idx => $h) {
                if (strpos(strtolower(trim($h)), 'price') !== false) { $priceCol = $idx; break; }
            }
        }
        // Not a pricing sheet (e.g. "Legend" / "Summary") — skip it
        if ($productCol === null || $priceCol === null) continue;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $name = trim($row[$productCol] ?? '');
            $priceRaw = trim($row[$priceCol] ?? '');
            if ($name === '' || $priceRaw === '') continue;

            $price = (int)preg_replace('/[^\d]/', '', $priceRaw);
            if ($price <= 0) { $skippedNoPrice++; continue; }

            if (!isset($catalogNames[$name])) {
                $notFound[] = $name;
                continue;
            }

            $stmt->execute([':name' => $name, ':price' => $price]);
            $updated++;
        }
    }
    $zip->close();

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'skippedNoPrice' => $skippedNoPrice,
        'notFound' => array_slice(array_unique($notFound), 0, 30),
        'notFoundCount' => count(array_unique($notFound)),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
