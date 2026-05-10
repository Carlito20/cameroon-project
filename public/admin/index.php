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
        $_SESSION['admin_role'] = 'admin';
        header('Location: dashboard.php');
        exit;
    } elseif ($password === '2026') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_role'] = 'employee';
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Admin Login — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh; min-height: -webkit-fill-available; min-height: 100dvh;
      display: flex; align-items: center; justify-content: center;
      background: #0a0a0a;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      -webkit-overflow-scrolling: touch; overflow-x: hidden;
      padding: env(safe-area-inset-top,0px) env(safe-area-inset-right,0px)
               env(safe-area-inset-bottom,0px) env(safe-area-inset-left,0px);
    }
    .card {
      background: #111; border: 1px solid #2a2a2a; border-radius: 12px;
      padding: 40px 36px; width: 100%; max-width: 380px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.6); margin: 16px;
    }
    .logo { text-align: center; margin-bottom: 28px; }
    .logo h1 { color: #d4af37; font-size: 22px; font-weight: 700; letter-spacing: 1px; }
    .logo p  { color: #555; font-size: 13px; margin-top: 4px; }

    /* Role tabs */
    .role-tabs {
      display: flex; gap: 0; margin-bottom: 24px;
      background: #1a1a1a; border-radius: 8px; padding: 4px;
    }
    .role-tab {
      flex: 1; padding: 10px; border: none; border-radius: 6px;
      font-size: 13px; font-weight: 600; cursor: pointer;
      background: transparent; color: #666;
      transition: background 0.2s, color 0.2s;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; -webkit-appearance: none; appearance: none;
      min-height: 40px;
    }
    .role-tab.active { background: #2a2a2a; color: #e0e0e0; }
    .role-tab.active.admin-tab  { color: #d4af37; }
    .role-tab.active.emp-tab    { color: #6dbf6d; }

    label {
      display: block; color: #aaa; font-size: 13px;
      margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .hint { font-size: 12px; color: #555; margin-top: 6px; }

    input[type="password"] {
      width: 100%; padding: 12px 16px;
      background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
      color: #fff; font-size: 16px; outline: none;
      transition: border-color 0.2s;
      -webkit-appearance: none; appearance: none;
      touch-action: manipulation; min-height: 44px;
    }
    input[type="password"]:focus { border-color: #d4af37; }

    button[type="submit"] {
      width: 100%; margin-top: 20px; padding: 13px;
      background: #d4af37; color: #000; border: none; border-radius: 8px;
      font-size: 15px; font-weight: 700; cursor: pointer; letter-spacing: 0.5px;
      transition: background 0.2s;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      min-height: 44px; -webkit-tap-highlight-color: transparent;
      -webkit-appearance: none; appearance: none;
    }
    button[type="submit"]:hover { background: #e8c547; }
    button[type="submit"].emp-btn { background: #2a5a2a; color: #6dbf6d; }
    button[type="submit"].emp-btn:hover { background: #336633; }

    .error {
      background: #2a0a0a; border: 1px solid #5c1a1a; color: #ff6b6b;
      border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">
      <h1>AMERICAN SELECT</h1>
      <p>Staff Portal</p>
    </div>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Role selector -->
    <div class="role-tabs">
      <button type="button" class="role-tab emp-tab active" id="tab-emp" onclick="setRole('employee')">
        👤 Employee
      </button>
      <button type="button" class="role-tab admin-tab" id="tab-admin" onclick="setRole('admin')">
        🔐 Admin
      </button>
    </div>

    <form method="POST" action="">
      <label id="pwd-label" for="password">Employee Code</label>
      <input type="password" id="password" name="password"
             autofocus autocomplete="current-password"
             inputmode="numeric" pattern="[0-9]*">
      <p class="hint" id="pwd-hint">Enter your 4-digit employee code</p>
      <button type="submit" id="submit-btn" class="emp-btn">Sign In as Employee</button>
    </form>
  </div>

  <script>
    function setRole(role) {
      const isEmp = role === 'employee';
      document.getElementById('tab-emp').classList.toggle('active', isEmp);
      document.getElementById('tab-admin').classList.toggle('active', !isEmp);
      document.getElementById('pwd-label').textContent   = isEmp ? 'Employee Code' : 'Admin Password';
      document.getElementById('pwd-hint').textContent    = isEmp ? 'Enter your 4-digit employee code' : 'Enter the admin password';
      document.getElementById('submit-btn').textContent  = isEmp ? 'Sign In as Employee' : 'Sign In as Admin';
      document.getElementById('submit-btn').className    = isEmp ? 'emp-btn' : '';
      const input = document.getElementById('password');
      input.inputMode = isEmp ? 'numeric' : 'text';
      if (isEmp) input.setAttribute('pattern', '[0-9]*');
      else       input.removeAttribute('pattern');
      input.value = '';
      input.focus();
    }
  </script>
</body>
</html>
