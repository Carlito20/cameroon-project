<?php
require_once __DIR__ . '/../api/db.php';
session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Incorrect password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0a0a0a;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    .card {
      background: #111;
      border: 1px solid #2a2a2a;
      border-radius: 12px;
      padding: 48px 40px;
      width: 100%;
      max-width: 380px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.6);
    }
    .logo {
      text-align: center;
      margin-bottom: 32px;
    }
    .logo h1 {
      color: #d4af37;
      font-size: 22px;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .logo p {
      color: #666;
      font-size: 13px;
      margin-top: 4px;
    }
    label {
      display: block;
      color: #aaa;
      font-size: 13px;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    input[type="password"] {
      width: 100%;
      padding: 12px 16px;
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 8px;
      color: #fff;
      font-size: 15px;
      outline: none;
      transition: border-color 0.2s;
      -webkit-appearance: none;
      appearance: none;
    }
    input[type="password"]:focus {
      border-color: #d4af37;
    }
    button[type="submit"] {
      width: 100%;
      margin-top: 20px;
      padding: 13px;
      background: #d4af37;
      color: #000;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 0.5px;
      transition: background 0.2s;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
    }
    button[type="submit"]:hover {
      background: #e8c547;
    }
    .error {
      background: #2a0a0a;
      border: 1px solid #5c1a1a;
      color: #ff6b6b;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">
      <h1>AMERICAN SELECT</h1>
      <p>Admin Panel</p>
    </div>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autofocus autocomplete="current-password">
      <button type="submit">Sign In</button>
    </form>
  </div>
</body>
</html>
