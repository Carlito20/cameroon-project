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

$to = 'americanselect2026@gmail.com';
$type = isset($_POST['_type']) ? $_POST['_type'] : 'contact';

if ($type === 'suggestion') {
    $name       = trim($_POST['name'] ?? 'Anonymous');
    $email      = trim($_POST['email'] ?? '');
    $suggestion = trim($_POST['suggestion'] ?? '');

    if (empty($suggestion)) {
        http_response_code(400);
        echo json_encode(['error' => 'Suggestion is required']);
        exit;
    }

    $subject = 'New Suggestion - American Select';
    $body  = "NEW SUGGESTION\n";
    $body .= "==============\n\n";
    $body .= "From:       $name\n";
    if ($email) $body .= "Email:      $email\n";
    $body .= "\nSuggestion:\n$suggestion\n";

} else {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject_field = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $honeypot = trim($_POST['_gotcha'] ?? '');

    // Spam trap
    if (!empty($honeypot)) {
        echo json_encode(['ok' => true]);
        exit;
    }

    if (empty($name) || empty($phone) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name, phone, and message are required']);
        exit;
    }

    $subject = "Contact Form: $subject_field - American Select";
    $body  = "NEW CONTACT MESSAGE\n";
    $body .= "===================\n\n";
    $body .= "Name:    $name\n";
    if ($email) $body .= "Email:   $email\n";
    $body .= "Phone:   $phone\n";
    $body .= "Subject: $subject_field\n";
    $body .= "\nMessage:\n$message\n";
}

$body .= "\n--\nSent from americanselect.net contact form";

$headers  = "From: noreply@americanselect.net\r\n";
$headers .= "Reply-To: " . ($email ? $email : $to) . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email. Please contact us on WhatsApp.']);
}
