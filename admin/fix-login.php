<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/database.php';

$db = Database::getConnection();

$username = 'admin_tiranda';
$email    = 'admin@tirandajogja.com';
$rawPass  = 'AdminTiranda2026!';
$fullName = 'Tiranda Operations';
$role     = 'superadmin';

// 1. Generate hash langsung di PHP runtime lokal Anda
$passwordHash = password_hash($rawPass, PASSWORD_BCRYPT);

// 2. Pastikan tabel admins ada
$db->query("
    CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `role` ENUM('superadmin', 'editor') DEFAULT 'editor',
        `is_active` BOOLEAN DEFAULT TRUE,
        `last_login` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 3. Reset atau masukkan akun baru
$stmt = $db->prepare("
    INSERT INTO admins (username, email, password_hash, full_name, role, is_active)
    VALUES (?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE 
        password_hash = VALUES(password_hash),
        full_name = VALUES(full_name),
        is_active = 1
");
$stmt->bind_param('sssss', $username, $email, $passwordHash, $fullName, $role);
$stmt->execute();

// 4. Verifikasi ulang password_verify
$check = $db->query("SELECT * FROM admins WHERE username = '{$username}' LIMIT 1")->fetch_assoc();
$verifyTest = password_verify($rawPass, $check['password_hash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Verifikasi Akun Admin</title>
  <style>
    body { font-family: sans-serif; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .box { background: #1e293b; padding: 2.5rem; border-radius: 12px; max-width: 480px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    .status { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: bold; }
    .status-success { background: #15803d; color: #ffffff; }
    .status-fail { background: #dc2626; color: #ffffff; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; font-size: 0.9rem; }
    .btn { display: block; text-align: center; background: #b48c56; color: #ffffff; padding: 0.85rem; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 1.5rem; }
  </style>
</head>
<body>

<div class="box">
  <h2 style="margin-top: 0; margin-bottom: 1rem; color: #b48c56;">Diagnosis Kredensial Admin</h2>

  <?php if ($verifyTest): ?>
    <div class="status status-success">✓ Akun Siap & Terverifikasi Aktif!</div>
  <?php else: ?>
    <div class="status status-fail">&#10007; Verifikasi Gagal! Periksa Database.</div>
  <?php endif; ?>

  <div class="info-row">
    <span>Username</span>
    <strong><?= htmlspecialchars($check['username']) ?></strong>
  </div>
  <div class="info-row">
    <span>Email</span>
    <strong><?= htmlspecialchars($check['email']) ?></strong>
  </div>
  <div class="info-row">
    <span>Password</span>
    <code><?= htmlspecialchars($rawPass) ?></code>
  </div>
  <div class="info-row">
    <span>Status Akun</span>
    <strong><?= $check['is_active'] ? '1 (Aktif)' : '0 (Nonaktif)' ?></strong>
  </div>

  <a href="<?= BASE_URL ?>/admin/login.php" class="btn">Menuju Halaman Login &rarr;</a>
</div>

</body>
</html>