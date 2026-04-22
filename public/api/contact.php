<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://americanselect.net');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

const WEB3FORMS_KEY = '76ebc4cd-2b2b-42d3-b14a-c27e0191a990';

function send_email($fields) {
    $fields['access_key'] = WEB3FORMS_KEY;
    $ch = curl_init('https://api.web3forms.com/submit');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode >= 200 && $httpCode < 300;
}

$type     = $_POST['_type'] ?? 'contact';
$honeypot = trim($_POST['_gotcha'] ?? '');

if (!empty($honeypot)) {
    echo json_encode(['ok' => true]);
    exit;
}

if ($type === 'bulk') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $products = trim($_POST['products'] ?? '');
    $timeline = trim($_POST['timeline'] ?? '');
    $message  = trim($_POST['message']  ?? '');

    if (empty($name) || empty($phone) || empty($products)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name, phone, and products are required']);
        exit;
    }

    $sent = send_email([
        'subject'   => "Bulk Order Request from {$name} - American Select",
        'from_name' => $name,
        'name'      => $name,
        'email'     => $email ?: 'not provided',
        'phone'     => $phone,
        'timeline'  => $timeline ?: 'not specified',
        'products'  => $products,
        'message'   => $message ?: 'none',
    ]);

} elseif ($type === 'suggestion') {
    $name       = trim($_POST['name']       ?? 'Anonymous');
    $email      = trim($_POST['email']      ?? '');
    $suggestion = trim($_POST['suggestion'] ?? '');

    if (empty($suggestion)) {
        http_response_code(400);
        echo json_encode(['error' => 'Suggestion is required']);
        exit;
    }

    $sent = send_email([
        'subject'    => 'New Suggestion - American Select',
        'from_name'  => $name,
        'name'       => $name,
        'email'      => $email ?: 'not provided',
        'suggestion' => $suggestion,
    ]);

} else {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($phone) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name, phone, and message are required']);
        exit;
    }

    $sent = send_email([
        'subject'   => "Contact Form: {$subject} - American Select",
        'from_name' => $name,
        'name'      => $name,
        'email'     => $email ?: 'not provided',
        'phone'     => $phone,
        'topic'     => $subject,
        'message'   => $message,
    ]);
}

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send. Please contact us on WhatsApp.']);
}
