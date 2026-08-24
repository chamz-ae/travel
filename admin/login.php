<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi keamanan kadaluarsa. Silakan refresh halaman.';
    } else {
        $username = Security::sanitizeString($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            if (Auth::attempt($username, $password)) {
                header('Location: ' . BASE_URL . '/admin/index.php');
                exit;
            } else {
                $error = 'Kredensial login tidak valid atau akun dinonaktifkan.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Tiranda Jogja</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--color-primary);
    }
    .login-card {
      background: #ffffff;
      width: 100%;
      max-width: 420px;
      padding: 2.5rem;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-lg);
    }
  </style>
</head>
<body>

<div class="login-card">
  <div style="text-align: center; margin-bottom: 2rem;">
    <div class="brand-logo" style="margin-bottom: 0.5rem;">TIRANDA<span>JOGJA</span></div>
    <p style="color: var(--color-text-muted); font-size: 0.875rem;">Control Panel Backoffice</p>
  </div>

  <?php if (!empty($error)): ?>
    <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
      <?= Security::e($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--color-primary);">Username / Email</label>
      <input type="text" name="username" required autocomplete="username" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
    </div>

    <div style="margin-bottom: 1.75rem;">
      <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--color-primary);">Password</label>
      <input type="password" name="password" required autocomplete="current-password" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">
      Masuk ke Backoffice
    </button>
  </form>
</div>

</body>
</html>