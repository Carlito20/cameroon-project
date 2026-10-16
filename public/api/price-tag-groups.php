<?php
header('Content-Type: application/json');
require_once __DIR__ . '/price-tag-groups-lib.php';
require_once __DIR__ . '/admin-session.php';

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

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'save';

try {
    $pdo = priceTagGroupsPdo();

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM price_tag_groups WHERE id = ?')->execute([(int)($body['id'] ?? 0)]);
        echo json_encode(['success' => true]);
        exit;
    }

    // save — creates a group, or updates one when id is given.
    // Accepts a list of groups too (for "Create all" suggestions).
    $items = isset($body['groups']) && is_array($body['groups']) ? $body['groups'] : [$body];
    $saved = [];
    foreach ($items as $g) {
        $name     = trim(preg_replace('/\s+/', ' ', $g['name'] ?? ''));
        $products = array_values(array_unique(array_filter(array_map('strval', $g['products'] ?? []), 'strlen')));
        $mode     = ($g['price_mode'] ?? 'max') === 'from' ? 'from' : 'max';
        $id       = (int)($g['id'] ?? 0);

        if ($name === '' || mb_strlen($name) > 200) {
            http_response_code(400);
            echo json_encode(['error' => 'Group name is required (max 200 characters)']);
            exit;
        }
        if (count($products) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'A group needs at least 2 products']);
            exit;
        }

        $json = json_encode($products, JSON_UNESCAPED_UNICODE);
        if ($id > 0) {
            $pdo->prepare('UPDATE price_tag_groups SET name = ?, products = ?, price_mode = ? WHERE id = ?')
                ->execute([$name, $json, $mode, $id]);
        } else {
            $pdo->prepare('INSERT INTO price_tag_groups (name, products, price_mode) VALUES (?, ?, ?)')
                ->execute([$name, $json, $mode]);
            $id = (int)$pdo->lastInsertId();
        }
        $saved[] = $id;
    }
    echo json_encode(['success' => true, 'ids' => $saved]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
