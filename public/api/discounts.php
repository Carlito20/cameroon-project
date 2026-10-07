<?php
header('Content-Type: application/json');
require_once __DIR__ . '/discount-lib.php';
require_once __DIR__ . '/admin-session.php';

$method = $_SERVER['REQUEST_METHOD'];
$isAdmin = !empty($_SESSION['admin_logged_in']) && ($_SESSION['admin_role'] ?? '') === 'admin';

// GET — discounts in effect now (public), or every discount with ?all=1 (logged-in staff)
if ($method === 'GET') {
    try {
        if (isset($_GET['all'])) {
            if (empty($_SESSION['admin_logged_in'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                exit;
            }
            echo json_encode(['discounts' => loadAllDiscounts(), 'today' => discountToday()]);
        } else {
            header('Cache-Control: no-store');
            $active = loadActiveDiscounts();
            // Objects, not [], when empty — the shop looks discounts up by name
            echo json_encode(['products' => (object)$active['products'], 'categories' => (object)$active['categories']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'save';
$scope  = ($body['scope'] ?? 'product') === 'category' ? 'category' : 'product';
$target = trim($body['target'] ?? '');

if ($target === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product or category']);
    exit;
}

function validDate($s) {
    if ($s === null || $s === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : false;
}

try {
    $pdo = discountPdo();

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM product_discounts WHERE scope = ? AND target = ?')->execute([$scope, $target]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE product_discounts SET active = ? WHERE scope = ? AND target = ?')
            ->execute([empty($body['active']) ? 0 : 1, $scope, $target]);
        echo json_encode(['success' => true]);
        exit;
    }

    // save (create or replace)
    $type  = ($body['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $value = (int)($body['value'] ?? 0);
    $start = validDate($body['starts_on'] ?? null);
    $end   = validDate($body['ends_on'] ?? null);

    if ($type === 'percent' && ($value < 1 || $value > 99)) {
        http_response_code(400);
        echo json_encode(['error' => 'Percentage must be between 1 and 99']);
        exit;
    }
    if ($type === 'fixed' && $value < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Amount must be at least 1 FCFA']);
        exit;
    }
    if ($start === false || $end === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date']);
        exit;
    }
    if ($start && $end && $end < $start) {
        http_response_code(400);
        echo json_encode(['error' => 'End date is before start date']);
        exit;
    }

    $pdo->prepare(
        'INSERT INTO product_discounts (scope, target, type, value, starts_on, ends_on, active)
         VALUES (:scope, :target, :type, :value, :start, :end, 1)
         ON DUPLICATE KEY UPDATE type = VALUES(type), value = VALUES(value),
           starts_on = VALUES(starts_on), ends_on = VALUES(ends_on), active = 1'
    )->execute([':scope' => $scope, ':target' => $target, ':type' => $type, ':value' => $value, ':start' => $start, ':end' => $end]);

    $row = ['type' => $type, 'value' => $value, 'starts_on' => $start, 'ends_on' => $end, 'active' => 1];
    echo json_encode(['success' => true, 'status' => discountStatus($row)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
