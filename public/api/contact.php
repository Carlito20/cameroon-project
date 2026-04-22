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

// ── Gmail SMTP sender ─────────────────────────────────────────────────────
function smtp_send($to, $subject, $body, $replyTo = '') {
    $username = 'americanselect2026@gmail.com';
    $password = 'lbcltnfvnroxjexw';
    $fromName = 'American Select';

    $socket = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 30);
    if (!$socket) return false;

    stream_set_timeout($socket, 30);

    $read = function() use ($socket) {
        $data = '';
        while ($line = fgets($socket, 512)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };

    $cmd = function($str) use ($socket) {
        fwrite($socket, $str . "\r\n");
    };

    $read(); // 220 greeting

    $cmd('EHLO americanselect.net');
    $read();

    $cmd('STARTTLS');
    $read();

    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    $cmd('EHLO americanselect.net');
    $read();

    $cmd('AUTH LOGIN');
    $read();

    $cmd(base64_encode($username));
    $read();

    $cmd(base64_encode($password));
    $resp = $read();
    if (strpos($resp, '235') === false) {
        fclose($socket);
        return false;
    }

    $cmd("MAIL FROM:<{$username}>");
    $read();

    $cmd("RCPT TO:<{$to}>");
    $read();

    $cmd('DATA');
    $read();

    $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $msg  = "From: {$fromName} <{$username}>\r\n";
    $msg .= "To: {$to}\r\n";
    $msg .= "Subject: {$subjectEncoded}\r\n";
    if ($replyTo) $msg .= "Reply-To: {$replyTo}\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Date: " . date('r') . "\r\n";
    $msg .= "\r\n";
    $msg .= $body . "\r\n.";

    $cmd($msg);
    $read();

    $cmd('QUIT');
    fclose($socket);

    return true;
}

// ── Build email content ───────────────────────────────────────────────────
$to   = 'americanselect2026@gmail.com';
$type = $_POST['_type'] ?? 'contact';

if ($type === 'bulk') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $products = trim($_POST['products'] ?? '');
    $timeline = trim($_POST['timeline'] ?? '');
    $message  = trim($_POST['message']  ?? '');
    $honeypot = trim($_POST['_gotcha']  ?? '');

    if (!empty($honeypot)) { echo json_encode(['ok' => true]); exit; }

    if (empty($name) || empty($phone) || empty($products)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name, phone, and products are required']);
        exit;
    }

    $subject = "Bulk Order Request from {$name} - American Select";
    $body    = "NEW BULK ORDER REQUEST\n";
    $body   .= "======================\n\n";
    $body   .= "Name/Company: {$name}\n";
    if ($email)    $body .= "Email:        {$email}\n";
    $body   .= "Phone:        {$phone}\n";
    if ($timeline) $body .= "Timeline:     {$timeline}\n";
    $body   .= "\nProducts of Interest:\n{$products}\n";
    if ($message)  $body .= "\nAdditional Info:\n{$message}\n";

} elseif ($type === 'suggestion') {
    $name       = trim($_POST['name']       ?? 'Anonymous');
    $email      = trim($_POST['email']      ?? '');
    $suggestion = trim($_POST['suggestion'] ?? '');

    if (empty($suggestion)) {
        http_response_code(400);
        echo json_encode(['error' => 'Suggestion is required']);
        exit;
    }

    $subject = 'New Suggestion - American Select';
    $body    = "NEW SUGGESTION\n";
    $body   .= "==============\n\n";
    $body   .= "From:  {$name}\n";
    if ($email) $body .= "Email: {$email}\n";
    $body   .= "\nSuggestion:\n{$suggestion}\n";

} else {
    $name     = trim($_POST['name']    ?? '');
    $email    = trim($_POST['email']   ?? '');
    $phone    = trim($_POST['phone']   ?? '');
    $subjectF = trim($_POST['subject'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $honeypot = trim($_POST['_gotcha'] ?? '');

    if (!empty($honeypot)) { echo json_encode(['ok' => true]); exit; }

    if (empty($name) || empty($phone) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name, phone, and message are required']);
        exit;
    }

    $subject = "Contact Form: {$subjectF} - American Select";
    $body    = "NEW CONTACT MESSAGE\n";
    $body   .= "===================\n\n";
    $body   .= "Name:    {$name}\n";
    if ($email) $body .= "Email:   {$email}\n";
    $body   .= "Phone:   {$phone}\n";
    $body   .= "Subject: {$subjectF}\n";
    $body   .= "\nMessage:\n{$message}\n";
}

$body .= "\n--\nSent from americanselect.net contact form";

$replyTo = !empty($email) ? $email : '';
$sent = smtp_send($to, $subject, $body, $replyTo);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email. Please contact us on WhatsApp.']);
}
