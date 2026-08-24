<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

// $serviceSlug diperoleh dari parsing router.php
$slug = $serviceSlug ?? '';

// Query detail layanan berdasarkan slug (mencocokkan localized slug atau fallback ID)
$stmt = $db->prepare("
    SELECT s.id, s.identifier, s.featured_image, s.base_price, s.price_unit,
           COALESCE(st.title, st_def.title) AS title,
           COALESCE(st.short_description, st_def.short_description) AS short_description,
           COALESCE(st.content_html, st_def.content_html) AS content_html,
           COALESCE(st.features_json, st_def.features_json) AS features_json,
           COALESCE(st.meta_title, st_def.meta_title) AS meta_title,
           COALESCE(st.meta_description, st_def.meta_description) AS meta_description,
           COALESCE(st.slug, st_def.slug) AS current_slug
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = ?
    LEFT JOIN service_translations st_def ON s.id = st_def.service_id AND st_def.language_code = 'id'
    WHERE (st.slug = ? OR st_def.slug = ?) AND s.is_active = 1
    LIMIT 1
");
$stmt->bind_param('sss', $locale, $slug, $slug);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    exit;
}

$pageTitle = $service['meta_title'] ?: $service['title'];
$pageDescription = $service['meta_description'] ?: $service['short_description'];
$features = !empty($service['features_json']) ? json_decode($service['features_json'], true) : [];

require INCLUDES_PATH . '/views/header.php';
?>

<div class="container" style="padding: 3rem 1.5rem 5rem;">
  <div style="margin-bottom: 2rem;">
    <a href="<?= BASE_URL ?>/<?= $locale ?>/services" style="color: var(--color-text-muted); font-size: 0.875rem;">
      &larr; Kembali ke Semua Layanan
    </a>
  </div>

  <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 3.5rem; align-items: start;">
    <!-- Main Content -->
    <article>
      <div style="border-radius: var(--radius-md); overflow: hidden; margin-bottom: 2rem; box-shadow: var(--shadow-md);">
        <img src="<?= Security::e($service['featured_image']) ?>" alt="<?= Security::e($service['title']) ?>" style="width: 100%; height: auto; max-height: 450px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1000&q=80'">
      </div>

      <h1 style="font-size: 2.25rem; margin-bottom: 1.5rem;"><?= Security::e($service['title']) ?></h1>

      <div style="font-size: 1rem; line-height: 1.8; color: var(--color-text-main); margin-bottom: 2.5rem;">
        <?= nl2br(Security::e($service['content_html'])) ?>
      </div>

      <?php if (!empty($features) && is_array($features)): ?>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.75rem; margin-bottom: 2rem;">
          <h3 style="margin-bottom: 1rem; font-size: 1.125rem;">Fasilitas & Keunggulan Termasuk:</h3>
          <ul style="list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
            <?php foreach ($features as $feature): ?>
              <li style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--color-text-muted);">
                <span style="color: var(--color-success); font-weight: bold;">✓</span> <?= Security::e($feature) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </article>

    <!-- Sticky Booking Card -->
    <aside style="position: sticky; top: 100px;">
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-lg);">
        <span class="tagline">Estimasi Tarif</span>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--color-primary); margin-bottom: 0.5rem;">
          <?= $service['base_price'] ? 'IDR ' . number_format((float)$service['base_price'], 0, ',', '.') : 'Hubungi Operasional' ?>
          <?php if ($service['base_price']): ?>
            <span style="font-size: 0.9rem; font-weight: 400; color: var(--color-text-muted);">/ <?= Security::e($service['price_unit']) ?></span>
          <?php endif; ?>
        </div>
        <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
          *Termasuk unit bersih, driver profesional, dan BBM rute dalam kota/sesuai kesepakatan.
        </p>

        <a href="<?= BASE_URL ?>/<?= $locale ?>/reservation?service=<?= Security::e($service['identifier']) ?>" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
          Pesan Layanan Ini
        </a>
        <a href="https://wa.me/6281200000000?text=Halo%20Tiranda%20Jogja,%20saya%20ingin%20tanya%20layanan%20<?= urlencode($service['title']) ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="width: 100%;">
          Konsultasi via WhatsApp
        </a>
      </div>
    </aside>
  </div>
</div>

<?php require INCLUDES_PATH . '/views/footer.php'; ?>