<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();

// Statistik
$totalResQuery = $db->query("SELECT COUNT(*) as cnt FROM reservations");
$totalReservations = (int)$totalResQuery->fetch_assoc()['cnt'];

$pendingResQuery = $db->query("SELECT COUNT(*) as cnt FROM reservations WHERE status = 'pending'");
$pendingReservations = (int)$pendingResQuery->fetch_assoc()['cnt'];

$totalServicesQuery = $db->query("SELECT COUNT(*) as cnt FROM services WHERE is_active = 1");
$totalServices = (int)$totalServicesQuery->fetch_assoc()['cnt'];

// 5 Reservasi Terbaru
$latestResQuery = $db->query("
    SELECT r.*, COALESCE(st.title, s.identifier, 'General Trip') AS service_name
    FROM reservations r
    LEFT JOIN services s ON r.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = 'id'
    ORDER BY r.created_at DESC
    LIMIT 5
");
$latestReservations = $latestResQuery->fetch_all(MYSQLI_ASSOC);

$activePage = 'dashboard';
$pageTitle = 'Ringkasan Operasional';
require_once __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 0.25rem;">Total Reservasi</div>
    <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-primary);"><?= $totalReservations ?></div>
  </div>
  <div class="stat-card">
    <div style="font-size: 0.85rem; color: #92400e; margin-bottom: 0.25rem;">Menunggu Konfirmasi</div>
    <div style="font-size: 1.75rem; font-weight: 700; color: #b45309;"><?= $pendingReservations ?></div>
  </div>
  <div class="stat-card">
    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 0.25rem;">Layanan Aktif</div>
    <div style="font-size: 1.75rem; font-weight: 700; color: var(--color-primary);"><?= $totalServices ?></div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 style="font-size: 1.125rem;">Reservasi Terbaru Masuk</h2>
    <a href="<?= BASE_URL ?>/admin/reservations.php" style="font-size: 0.85rem; color: var(--color-accent); font-weight: 600;">Lihat Semua &rarr;</a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Pelanggan</th>
          <th>Layanan</th>
          <th>Tgl Jemput</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($latestReservations)): ?>
          <?php foreach ($latestReservations as $r): ?>
            <tr>
              <td><strong><?= Security::e($r['booking_code']) ?></strong></td>
              <td>
                <?= Security::e($r['customer_name']) ?><br>
                <small style="color: var(--color-text-muted);"><?= Security::e($r['customer_phone']) ?></small>
              </td>
              <td><?= Security::e($r['service_name']) ?></td>
              <td><?= Security::e($r['pickup_date']) ?> (<?= Security::e($r['pickup_time']) ?>)</td>
              <td><span class="badge badge-<?= Security::e($r['status']) ?>"><?= Security::e($r['status']) ?></span></td>
              <td style="text-align: right;">
                <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                  Kelola
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: var(--color-text-muted);">Belum ada data reservasi.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>