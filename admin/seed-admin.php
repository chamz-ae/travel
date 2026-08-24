<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/database.php';

$db = Database::getConnection();

$username = 'admin_tiranda';
$email    = 'admin@tirandajogja.com';
$password = 'AdminTiranda2026!';
$fullName = 'Tiranda Operations';
$role     = 'superadmin';

// Generate hash valid menggunakan PHP runtime
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Hapus atau update akun
$stmt = $db->prepare("
    INSERT INTO admins (username, email, password_hash, full_name, role, is_active)
    VALUES (?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE 
        password_hash = VALUES(password_hash),
        is_active = 1
");

$stmt->bind_param('sssss', $username, $email, $hash, $fullName, $role);

if ($stmt->execute()) {
    echo "<div style='font-family: sans-serif; padding: 2rem;'>";
    echo "<h2 style='color: green;'>✓ Akun Administrator Berhasil Direset!</h2>";
    echo "<p><strong>Username:</strong> {$username}</p>";
    echo "<p><strong>Password:</strong> {$password}</p>";
    echo "<p><a href='" . BASE_URL . "/admin/login.php'>Klik di sini untuk Login ke Backoffice</a></p>";
    echo "</div>";
} else {
    echo "Gagal memperbarui database: " . $db->error;
}