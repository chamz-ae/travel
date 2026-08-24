<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/settings.php';

Auth::requireAuth();
$db = Database::getConnection();
$message = '';
$error = '';

$defaultSettings = [
    'company_name'    => 'Tiranda Jogja',
    'whatsapp_number' => '6281234567890',
    'support_email'   => 'info@tirandajogja.com',
    'office_address'  => 'Jl. Malioboro No. 123, Yogyakarta',
    'operating_hours' => '24 Jam (Armada) | CS: 07:00 - 22:00 WIB',
    'instagram_url'   => 'https://instagram.com/tirandajogja',
    'google_maps_url' => 'https://maps.google.com'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi keamanan kadaluarsa.';
    } else {
        $stmt = $db->prepare("
            INSERT INTO settings (key_name, value, group_name, is_serialized)
            VALUES (?, ?, 'general', 0)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");

        foreach ($defaultSettings as $key => $defaultVal) {
            $val = Security::sanitizeString($_POST[$key] ?? $defaultVal);
            $stmt->bind_param('ss', $key, $val);
            $stmt->execute();
        }

        $message = 'Pengaturan operasional berhasil diperbarui.';
        Settings::load();
    }
}

$activePage = 'settings';
$pageTitle = 'Pengaturan Sistem & Kontak';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<div class="admin-card" style="max-width: 820px;">
  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Nama Brand Perusahaan</label>
      <input type="text" name="company_name" value="<?= Security::e(get_setting('company_name', $defaultSettings['company_name'])) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
    </div>

    <div class="form-group-row">
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">WhatsApp Operasional</label>
        <input type="text" name="whatsapp_number" value="<?= Security::e(get_setting('whatsapp_number', $defaultSettings['whatsapp_number'])) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
      </div>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Email Support</label>
        <input type="email" name="support_email" value="<?= Security::e(get_setting('support_email', $defaultSettings['support_email'])) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
      </div>
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Alamat Garasi / Kantor</label>
      <textarea name="office_address" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);"><?= Security::e(get_setting('office_address', $defaultSettings['office_address'])) ?></textarea>
    </div>

    <div class="form-group-row">
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Instagram URL</label>
        <input type="url" name="instagram_url" value="<?= Security::e(get_setting('instagram_url', $defaultSettings['instagram_url'])) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
      </div>

      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Google Maps URL</label>
        <input type="url" name="google_maps_url" value="<?= Security::e(get_setting('google_maps_url', $defaultSettings['google_maps_url'])) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem;">
      Simpan Pengaturan
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>