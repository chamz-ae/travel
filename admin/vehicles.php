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
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->bind_param('i', $vehId);
        if ($stmt->execute()) {
            $message = 'Armada berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus data armada.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $stmt = $db->prepare("UPDATE vehicles SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param('i', $vehId);
        $stmt->execute();
        $message = 'Status armada berhasil diperbarui.';
    }
}

$vehicles = $db->query("SELECT * FROM vehicles ORDER BY display_order ASC, id DESC")->fetch_all(MYSQLI_ASSOC);

$activePage = 'vehicles';
$pageTitle = 'Kelola Armada';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 style="font-size: 1.125rem;">Daftar Unit Kendaraan</h2>
    <a href="<?= BASE_URL ?>/admin/vehicle-form.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
      + Tambah Armada Baru
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 70px;">Urutan</th>
          <th style="width: 80px;">Unit</th>
          <th>Nama Mobil</th>
          <th>Spesifikasi</th>
          <th>Tarif Harian (Lepas / Driver)</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($vehicles)): ?>
          <?php foreach ($vehicles as $v): ?>
            <tr>
              <td><strong>#<?= $v['display_order'] ?></strong></td>
              <td>
                <img src="<?= Security::e($v['featured_image']) ?>" alt="Fleet" style="width: 65px; height: 45px; object-fit: cover; border-radius: 4px;" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=120&q=80'">
              </td>
              <td>
                <strong><?= Security::e($v['name']) ?></strong><br>
                <small style="color: var(--color-text-muted);"><?= strtoupper(Security::e($v['category'])) ?> (<?= ucfirst(Security::e($v['transmission'])) ?>)</small>
              </td>
              <td style="font-size: 0.85rem;">
                👥 <?= (int)$v['capacity_passengers'] ?> Kursi | 🧳 <?= (int)$v['capacity_luggage'] ?> Koper
              </td>
              <td>
                IDR <?= number_format((float)$v['daily_rate'], 0, ',', '.') ?> / 
                <strong style="color: var(--color-primary);">IDR <?= number_format((float)$v['with_driver_rate'], 0, ',', '.') ?></strong>
              </td>
              <td>
                <form method="POST" action="" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                  <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                    <span class="badge badge-<?= $v['is_active'] ? 'confirmed' : 'cancelled' ?>">
                      <?= $v['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                  </button>
                </form>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.35rem;">
                  <a href="<?= BASE_URL ?>/admin/vehicle-form.php?id=<?= $v['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                    Edit
                  </a>
                  <form method="POST" action="" onsubmit="return confirm('Hapus armada ini?');" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
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
            <td colspan="7" style="text-align: center; color: var(--color-text-muted);">Belum ada unit armada terdaftar.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>