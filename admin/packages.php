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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $pkgId = (int)($_POST['package_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->bind_param('i', $pkgId);
        if ($stmt->execute()) {
            $message = 'Paket wisata berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus paket.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $pkgId = (int)($_POST['package_id'] ?? 0);
        $stmt = $db->prepare("UPDATE packages SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param('i', $pkgId);
        $stmt->execute();
        $message = 'Status paket berhasil diperbarui.';
    }
}

$packages = $db->query("
    SELECT p.*, 
           COALESCE(pt_id.title, p.identifier) AS title_id,
           COALESCE(pt_id.slug, '') AS slug_id
    FROM packages p
    LEFT JOIN package_translations pt_id ON p.id = pt_id.package_id AND pt_id.language_code = 'id'
    ORDER BY p.display_order ASC, p.id DESC
")->fetch_all(MYSQLI_ASSOC);

$activePage = 'packages';
$pageTitle = 'Kelola Paket Tour';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 style="font-size: 1.125rem;">Daftar Paket Tour Jogja</h2>
    <a href="<?= BASE_URL ?>/admin/package-form.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
      + Tambah Paket Baru
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 70px;">Urutan</th>
          <th>Paket Tour (ID)</th>
          <th>Durasi & Min Pax</th>
          <th>Harga / Pax</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($packages)): ?>
          <?php foreach ($packages as $pkg): ?>
            <tr>
              <td><strong>#<?= $pkg['display_order'] ?></strong></td>
              <td>
                <strong><?= Security::e($pkg['title_id']) ?></strong><br>
                <small style="color: var(--color-text-muted);">/packages/<?= Security::e($pkg['slug_id']) ?></small>
              </td>
              <td style="font-size: 0.85rem;">
                ⏱ <?= Security::e($pkg['duration_text']) ?><br>
                <small style="color: var(--color-text-muted);">Min. <?= (int)$pkg['min_pax'] ?> Orang</small>
              </td>
              <td>
                <strong style="color: var(--color-primary);">IDR <?= number_format((float)$pkg['price_per_person'], 0, ',', '.') ?></strong>
              </td>
              <td>
                <form method="POST" action="" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                  <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                    <span class="badge badge-<?= $pkg['is_active'] ? 'confirmed' : 'cancelled' ?>">
                      <?= $pkg['is_active'] ? 'Publik' : 'Draft' ?>
                    </span>
                  </button>
                </form>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.35rem;">
                  <a href="<?= BASE_URL ?>/admin/package-form.php?id=<?= $pkg['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                    Edit
                  </a>
                  <form method="POST" action="" onsubmit="return confirm('Hapus paket ini?');" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; color: #dc2626; border-color: #fca5a5;">
                      Hapus
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: var(--color-text-muted);">Belum ada paket wisata.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>