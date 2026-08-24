<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_language') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $code       = strtolower(trim(Security::sanitizeString($_POST['code'] ?? '')));
        $name       = Security::sanitizeString($_POST['name'] ?? '');
        $nativeName = Security::sanitizeString($_POST['native_name'] ?? '');
        $order      = (int)($_POST['display_order'] ?? 0);

        if (strlen($code) >= 2 && strlen($code) <= 5 && !empty($name)) {
            $stmt = $db->prepare("INSERT INTO languages (code, name, native_name, is_default, is_active, display_order) VALUES (?, ?, ?, 0, 1, ?)");
            $stmt->bind_param('sssi', $code, $name, $nativeName, $order);
            if ($stmt->execute()) {
                $jsonFile = LANGUAGES_PATH . '/' . $code . '.json';
                if (!file_exists($jsonFile)) {
                    copy(LANGUAGES_PATH . '/en.json', $jsonFile);
                }
                $message = "Bahasa {$name} ({$code}) berhasil ditambahkan.";
            } else {
                $error = 'Kode bahasa sudah terdaftar.';
            }
        } else {
            $error = 'Format kode atau nama bahasa tidak valid.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_language') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $code = Security::sanitizeString($_POST['lang_code'] ?? '');
        if ($code !== DEFAULT_LOCALE) {
            $stmt = $db->prepare("UPDATE languages SET is_active = NOT is_active WHERE code = ?");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $message = "Status bahasa {$code} berhasil diubah.";
        } else {
            $error = 'Bahasa default sistem (ID) tidak dapat dinonaktifkan.';
        }
    }
}

$languages = $db->query("SELECT * FROM languages ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$activePage = 'languages';
$pageTitle = 'Kelola Bahasa';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<div class="admin-form-grid">
  <!-- Form Tambah -->
  <form method="POST" action="" class="admin-card">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
    <input type="hidden" name="action" value="add_language">

    <h2 style="font-size: 1.05rem; margin-bottom: 1.25rem;">+ Tambah Bahasa Baru</h2>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Kode Locale (2-5 Karakter) *</label>
      <input type="text" name="code" required maxlength="5" placeholder="it" style="width: 100%; padding: 0.7rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Nama Bahasa (English) *</label>
      <input type="text" name="name" required placeholder="Italian" style="width: 100%; padding: 0.7rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Nama Native *</label>
      <input type="text" name="native_name" required placeholder="Italiano" style="width: 100%; padding: 0.7rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Urutan</label>
      <input type="number" name="display_order" value="<?= count($languages) + 1 ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 0.85rem;">
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
      Daftarkan Bahasa
    </button>
  </form>

  <!-- Tabel Status Bahasa -->
  <div class="admin-card">
    <h2 style="font-size: 1.05rem; margin-bottom: 1.25rem;">Daftar Bahasa & Status Kamus</h2>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Bahasa</th>
            <th>File Kamus</th>
            <th>Status</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($languages as $lang): 
            $hasDict = file_exists(LANGUAGES_PATH . '/' . $lang['code'] . '.json');
          ?>
            <tr>
              <td><strong><?= strtoupper(Security::e($lang['code'])) ?></strong></td>
              <td><?= Security::e($lang['name']) ?> (<?= Security::e($lang['native_name']) ?>)</td>
              <td>
                <span style="font-size: 0.75rem; font-weight: 600; color: <?= $hasDict ? '#15803d' : '#dc2626' ?>;">
                  <?= $hasDict ? '✓ Tersedia' : '✗ Belum Ada' ?>
                </span>
              </td>
              <td>
                <span class="badge badge-<?= $lang['is_active'] ? 'confirmed' : 'cancelled' ?>">
                  <?= $lang['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <?php if ($lang['code'] !== DEFAULT_LOCALE): ?>
                  <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="toggle_language">
                    <input type="hidden" name="lang_code" value="<?= $lang['code'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                      <?= $lang['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span style="font-size: 0.75rem; color: var(--color-text-muted);">Default</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>