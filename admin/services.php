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
        $deleteId = (int)($_POST['service_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param('i', $deleteId);
        if ($stmt->execute()) {
            $message = 'Layanan berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus layanan.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $svcId = (int)($_POST['service_id'] ?? 0);
        $stmt = $db->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param('i', $svcId);
        $stmt->execute();
        $message = 'Status publikasi diperbarui.';
    }
}

$languages = $db->query("SELECT code, name FROM languages WHERE is_active = 1 ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$services = $db->query("
    SELECT s.*, 
           COALESCE(st_id.title, s.identifier) AS title_id,
           COALESCE(st_id.slug, '') AS slug_id,
           GROUP_CONCAT(st_all.language_code) AS translated_locales
    FROM services s
    LEFT JOIN service_translations st_id ON s.id = st_id.service_id AND st_id.language_code = 'id'
    LEFT JOIN service_translations st_all ON s.id = st_all.service_id
    GROUP BY s.id
    ORDER BY s.display_order ASC, s.id DESC
")->fetch_all(MYSQLI_ASSOC);

$activePage = 'services';
$pageTitle = 'Kelola Layanan';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($error) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 style="font-size: 1.125rem;">Daftar Layanan & Terjemahan</h2>
    <a href="<?= BASE_URL ?>/admin/service-form.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
      + Tambah Layanan Baru
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 70px;">Urutan</th>
          <th>Layanan (ID)</th>
          <th>Identifier / Slug</th>
          <th>Tarif Dasar</th>
          <th>Terjemahan</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($services)): ?>
          <?php foreach ($services as $svc): 
            $translatedList = explode(',', (string)$svc['translated_locales']);
          ?>
            <tr>
              <td><strong>#<?= $svc['display_order'] ?></strong></td>
              <td><strong><?= Security::e($svc['title_id']) ?></strong></td>
              <td>
                <code><?= Security::e($svc['identifier']) ?></code><br>
                <small style="color: var(--color-text-muted);">/<?= Security::e($svc['slug_id']) ?></small>
              </td>
              <td>
                <?= $svc['base_price'] ? 'IDR ' . number_format((float)$svc['base_price'], 0, ',', '.') : '<span style="color: var(--color-text-muted);">-</span>' ?><br>
                <small style="color: var(--color-text-muted);"><?= Security::e($svc['price_unit']) ?></small>
              </td>
              <td>
                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                  <?php foreach ($languages as $lang): 
                    $isTranslated = in_array($lang['code'], $translatedList, true);
                  ?>
                    <span style="font-size: 0.65rem; padding: 0.15rem 0.35rem; border-radius: 4px; font-weight: 700; background: <?= $isTranslated ? '#dcfce7' : '#fee2e2' ?>; color: <?= $isTranslated ? '#15803d' : '#b91c1c' ?>;">
                      <?= strtoupper($lang['code']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </td>
              <td>
                <form method="POST" action="" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="service_id" value="<?= $svc['id'] ?>">
                  <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                    <span class="badge badge-<?= $svc['is_active'] ? 'confirmed' : 'cancelled' ?>">
                      <?= $svc['is_active'] ? 'Publik' : 'Draft' ?>
                    </span>
                  </button>
                </form>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.35rem;">
                  <a href="<?= BASE_URL ?>/admin/service-form.php?id=<?= $svc['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                    Edit
                  </a>
                  <form method="POST" action="" onsubmit="return confirm('Hapus layanan ini beserta seluruh terjemahannya?');" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="service_id" value="<?= $svc['id'] ?>">
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
            <td colspan="7" style="text-align: center; color: var(--color-text-muted);">Belum ada layanan terdaftar.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>