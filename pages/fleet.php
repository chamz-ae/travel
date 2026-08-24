<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

// Ambil Seluruh Data Armada Kendaraan
$fleetQuery = $db->query("
    SELECT v.*, COALESCE(vt.description, '') as localized_desc
    FROM vehicles v
    LEFT JOIN vehicle_translations vt ON v.id = vt.vehicle_id AND vt.language_code = '{$locale}'
    WHERE v.is_active = 1
    ORDER BY v.display_order ASC
");
$fleet = $fleetQuery->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Daftar Armada Mobil & Van — Tiranda Jogja';
$pageDescription = 'Pilihan armada kendaraan rental mobil, Hiace, dan minibus berstandar kenyamanan tinggi di Yogyakarta.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="padding-top: 3.5rem;">
  <div class="container">
    <div class="section-header">
      <span class="tagline">Spesifikasi Armada</span>
      <h1>Kendaraan Bersih, Prima & Nyaman</h1>
      <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
        Seluruh unit armada kami dirawat berkala dengan standar kebersihan ketat untuk perjalanan bisnis maupun liburan keluarga.
      </p>
    </div>

    <div class="cards-grid">
      <?php if (!empty($fleet)): ?>
        <?php foreach ($fleet as $car): ?>
          <article class="card">
            <div class="card-img-wrapper">
              <img src="<?= Security::e($car['featured_image']) ?>" alt="<?= Security::e($car['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=600&q=80'">
            </div>
            <div class="card-body">
              <span class="tagline" style="margin-bottom: 0.25rem;"><?= strtoupper(Security::e($car['category'])) ?></span>
              <h2 class="card-title"><?= Security::e($car['name']) ?></h2>
              
              <!-- Spesifikasi Kendaraan -->
              <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--color-text-muted); background: var(--color-bg-light); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm);">
                <div>👥 <?= (int)$car['capacity_passengers'] ?> Kursi</div>
                <div>🧳 <?= (int)$car['capacity_luggage'] ?> Koper</div>
                <div>⚙️ <?= ucfirst(Security::e($car['transmission'])) ?></div>
              </div>

              <p class="card-text"><?= Security::e($car['localized_desc']) ?></p>
              
              <div class="card-footer">
                <div class="card-price">
                  IDR <?= number_format((float)$car['with_driver_rate'], 0, ',', '.') ?>
                  <span>/ 12 jam (Driver)</span>
                </div>
                <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/reservation" class="btn btn-primary" style="padding: 0.5rem 0.85rem; font-size: 0.85rem;">
                  Pesan Unit
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; grid-column: 1/-1; color: var(--color-text-muted);">
          Data spesifikasi armada sedang diperbarui.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>