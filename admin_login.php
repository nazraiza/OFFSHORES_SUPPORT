<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Secure session cookie flags (works if not already set in php.ini)
if (PHP_VERSION_ID >= 70300) {
  @ini_set('session.cookie_httponly', '1');
  @ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
  @ini_set('session.cookie_samesite', 'Lax');
}

$error = '';
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $error = 'Invalid session. Please refresh and try again.';
  } else {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
      $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE (username=? OR email=?) AND COALESCE(is_active,1)=1 LIMIT 1');
      $stmt->execute([$username,$username]);
      $user = $stmt->fetch();

      if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header('Location: admin_dashboard.php');
        exit;
      } else {
        $error = 'Invalid username or password';
      }
    } else {
      $error = 'Please fill both fields';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login</title>
  <style>
    :root{color-scheme:dark}
    body{font-family:system-ui,Arial,sans-serif;background:#0b1220;color:#eaf1ff;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .login-box{background:#121a2b;padding:24px;border-radius:12px;min-width:320px;box-shadow:0 8px 30px rgba(0,0,0,.35)}
    h2{margin-top:0}
    input,button{width:100%;padding:10px;border-radius:8px;border:1px solid #2b3550;background:#0f1628;color:#fff;margin-top:8px}
    button:hover{filter:brightness(1.08); cursor:pointer}
    .error {color:#ff9d9d; margin-top:10px;}
  </style>
</head>
<body>
  <form class="login-box" method="POST" autocomplete="off">
    <h2>Admin Login</h2>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
    <input type="text" name="username" placeholder="Username or Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
    <?php if($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  </form>
</body>
</html>
