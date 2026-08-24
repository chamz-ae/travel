<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();
$message = '';

// 1. Handle Update Status Reservasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $resId  = (int)($_POST['reservation_id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'pending'));
        $allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        
        if ($resId > 0 && in_array($status, $allowedStatuses, true)) {
            $stmt = $db->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $resId);
            $stmt->execute();
            $message = 'Status reservasi berhasil diperbarui.';
        }
    }
}

// 2. Filter & Query Data Reservasi
$filterStatus = Security::sanitizeString($_GET['status'] ?? '');
$allowedFilters = ['pending', 'confirmed', 'completed', 'cancelled'];

$sql = "
    SELECT r.*, COALESCE(st.title, s.identifier, 'Custom Trip') AS service_name
    FROM reservations r
    LEFT JOIN services s ON r.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = 'id'
";

if (!empty($filterStatus) && in_array($filterStatus, $allowedFilters, true)) {
    $stmt = $db->prepare($sql . " WHERE r.status = ? ORDER BY r.created_at DESC");
    $stmt->bind_param('s', $filterStatus);
    $stmt->execute();
    $reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $reservations = $db->query($sql . " ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

$activePage = 'reservations';
$pageTitle = 'Manajemen Reservasi';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn <?= empty($filterStatus) ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">Semua</a>
      <a href="<?= BASE_URL ?>/admin/reservations.php?status=pending" class="btn <?= $filterStatus === 'pending' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">Pending</a>
      <a href="<?= BASE_URL ?>/admin/reservations.php?status=confirmed" class="btn <?= $filterStatus === 'confirmed' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">Confirmed</a>
      <a href="<?= BASE_URL ?>/admin/reservations.php?status=completed" class="btn <?= $filterStatus === 'completed' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">Completed</a>
    </div>

    <a href="<?= BASE_URL ?>/admin/export-reservations.php<?= !empty($filterStatus) ? '?status=' . Security::e($filterStatus) : '' ?>" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">
      📥 Export CSV
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Kode Booking</th>
          <th>Pelanggan</th>
          <th>Layanan</th>
          <th>Penjemputan</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($reservations)): ?>
          <?php foreach ($reservations as $res): ?>
            <tr>
              <td>
                <strong><?= Security::e($res['booking_code']) ?></strong><br>
                <small style="color: var(--admin-text-muted);"><?= date('d/m/Y H:i', strtotime($res['created_at'])) ?></small>
              </td>
              <td>
                <strong><?= Security::e($res['customer_name']) ?></strong><br>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $res['customer_phone']) ?>" target="_blank" style="color: var(--color-success); font-weight: 600; font-size: 0.8rem;">
                  WA: <?= Security::e($res['customer_phone']) ?>
                </a><br>
                <small style="color: var(--admin-text-muted);"><?= Security::e($res['customer_email']) ?></small>
              </td>
              <td><?= Security::e($res['service_name']) ?></td>
              <td>
                <?= date('d M Y', strtotime($res['pickup_date'])) ?> @ <?= Security::e($res['pickup_time']) ?><br>
                <small style="color: var(--admin-text-muted);">Lokasi: <?= Security::e($res['pickup_location']) ?></small><br>
                <small style="color: var(--admin-text-muted);">Durasi: <?= (int)$res['duration_days'] ?> Hari (<?= (int)$res['passengers'] ?> Pax)</small>
              </td>
              <td>
                <span class="badge badge-<?= Security::e($res['status']) ?>"><?= Security::e($res['status']) ?></span>
              </td>
              <td style="text-align: right;">
                <form method="POST" action="" style="display: inline-flex; gap: 0.35rem; align-items: center;">
                  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="reservation_id" value="<?= (int)$res['id'] ?>">
                  
                  <select name="status" style="font-size: 0.8rem; padding: 0.35rem 0.5rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #ffffff;">
                    <option value="pending" <?= $res['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $res['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="completed" <?= $res['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $res['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                  <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                    Update
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: var(--admin-text-muted);">Tidak ada data reservasi ditemukan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>