<?php
/**
 * Simple display state API — used by checkout.php (write) and customer-display.php (read)
 * GET  → returns current state JSON
 * POST → updates state JSON
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

$stateFile = __DIR__ . '/display-state.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!$data) { echo json_encode(['error' => 'Invalid JSON']); exit; }
    $data['updated'] = time();
    file_put_contents($stateFile, json_encode($data));
    echo json_encode(['ok' => true]);
} else {
    if (!file_exists($stateFile)) {
        echo json_encode(['items' => [], 'total' => 0, 'payment' => '', 'active' => false, 'updated' => 0]);
    } else {
        echo file_get_contents($stateFile);
    }
}
