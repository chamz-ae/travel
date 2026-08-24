<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

// Ambil semua layanan aktif
$stmt = $db->prepare("
    SELECT s.id, s.identifier, s.featured_image, s.base_price, s.price_unit,
           COALESCE(st.title, st_def.title) AS title,
           COALESCE(st.short_description, st_def.short_description) AS short_description,
           COALESCE(st.slug, st_def.slug) AS slug
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = ?
    LEFT JOIN service_translations st_def ON s.id = st_def.service_id AND st_def.language_code = 'id'
    WHERE s.is_active = 1
    ORDER BY s.display_order ASC
");
$stmt->bind_param('s', $locale);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = __('nav.services') . ' — Pilihan Sewa Mobil & Tour Jogja';
$pageDescription = 'Daftar layanan transportasi, rental mobil + driver, Hiace, dan paket tour privat di Yogyakarta.';

require INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="padding-top: 3.5rem;">
  <div class="container">
    <div class="section-header">
      <span class="tagline">Layanan & Armada</span>
      <h1>Transportasi Nyaman & Terpercaya</h1>
      <p style="color: var(--color-text-muted); margin-top: 0.75rem;">
        Pilihan kendaraan prima dengan driver ramah dan profesional untuk kebutuhan perjalanan Anda di Yogyakarta.
      </p>
    </div>

    <div class="cards-grid">
      <?php if (!empty($services)): ?>
        <?php foreach ($services as $service): ?>
          <article class="card">
            <div class="card-img-wrapper">
              <img src="<?= Security::e($service['featured_image']) ?>" alt="<?= Security::e($service['title']) ?>" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=600&q=80'">
            </div>
            <div class="card-body">
              <h2 class="card-title" style="font-size: 1.2rem;"><?= Security::e($service['title']) ?></h2>
              <p class="card-text"><?= Security::e($service['short_description']) ?></p>
              
              <div class="card-footer">
                <div class="card-price">
                  <?= $service['base_price'] ? 'IDR ' . number_format((float)$service['base_price'], 0, ',', '.') : 'Harga Khusus' ?>
                  <?php if ($service['base_price']): ?>
                    <span>/ <?= Security::e($service['price_unit']) ?></span>
                  <?php endif; ?>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                  <a href="<?= BASE_URL ?>/<?= $locale ?>/services/<?= Security::e($service['slug']) ?>" class="btn btn-secondary" style="padding: 0.5rem 0.85rem; font-size: 0.85rem;">
                    Detail
                  </a>
                  <a href="<?= BASE_URL ?>/<?= $locale ?>/reservation?service=<?= Security::e($service['identifier']) ?>" class="btn btn-primary" style="padding: 0.5rem 0.85rem; font-size: 0.85rem;">
                    Pesan
                  </a>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require INCLUDES_PATH . '/views/footer.php'; ?>
