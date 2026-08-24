<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

// 1. Ambil Layanan Aktif
$svcStmt = $db->prepare("
    SELECT s.id, s.identifier, s.featured_image, s.base_price, s.price_unit,
           COALESCE(st.title, st_def.title) AS title,
           COALESCE(st.short_description, st_def.short_description) AS short_description,
           COALESCE(st.slug, st_def.slug) AS slug
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = ?
    LEFT JOIN service_translations st_def ON s.id = st_def.service_id AND st_def.language_code = 'id'
    WHERE s.is_active = 1
    ORDER BY s.display_order ASC
    LIMIT 3
");
$svcStmt->bind_param('s', $locale);
$svcStmt->execute();
$services = $svcStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Ambil Paket Wisata Aktif (Fokus Deskripsi & Itinerary — Tanpa Gambar)
$pkgStmt = $db->prepare("
    SELECT p.id, p.price_per_person, p.duration_text, p.min_pax,
           COALESCE(pt.title, pt_def.title, p.identifier) AS title,
           COALESCE(pt.short_description, pt_def.short_description, '') AS short_description,
           COALESCE(pt.itinerary_json, pt_def.itinerary_json) AS itinerary_json,
           COALESCE(pt.slug, pt_def.slug, p.identifier) AS slug
    FROM packages p
    LEFT JOIN package_translations pt ON p.id = pt.package_id AND pt.language_code = ?
    LEFT JOIN package_translations pt_def ON p.id = pt_def.package_id AND pt_def.language_code = 'id'
    WHERE p.is_active = 1
    ORDER BY p.display_order ASC, p.id DESC
    LIMIT 3
");
$pkgStmt->bind_param('s', $locale);
$pkgStmt->execute();
$packages = $pkgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Ambil Foto Galeri (Untuk Continuous Marquee Carousel)
$galRes = $db->query("SELECT title, image_url, category FROM gallery WHERE is_active = 1 ORDER BY display_order ASC LIMIT 8");
$galleryList = $galRes->fetch_all(MYSQLI_ASSOC);
$marqueeGallery = array_merge($galleryList, $galleryList);

// 4. Ambil Spesifikasi Armada
$fleetRes = $db->query("SELECT * FROM vehicles WHERE is_active = 1 ORDER BY display_order ASC LIMIT 3");
$fleet = $fleetRes->fetch_all(MYSQLI_ASSOC);

// 5. Ambil Artikel Panduan Wisata
$artStmt = $db->prepare("
    SELECT a.id, a.featured_image, a.published_at,
           COALESCE(at.title, at_def.title) AS title,
           COALESCE(at.excerpt, at_def.excerpt) AS excerpt,
           COALESCE(at.slug, at_def.slug) AS slug
    FROM articles a
    LEFT JOIN article_translations at ON a.id = at.article_id AND at.language_code = ?
    LEFT JOIN article_translations at_def ON a.id = at_def.article_id AND at_def.language_code = 'id'
    WHERE a.is_published = 1
    ORDER BY a.published_at DESC
    LIMIT 3
");
$artStmt->bind_param('s', $locale);
$artStmt->execute();
$articles = $artStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = __('hero.headline');
$pageDescription = __('hero.subheadline');

require_once INCLUDES_PATH . '/views/header.php';
?>

<!-- 1. Hero Section -->
<section class="hero-section">
  <div class="container hero-grid">
    <div>
      <span class="tagline"><?= __('hero.tagline') ?></span>
      <h1 class="hero-title"><?= __('hero.headline') ?></h1>
      <p class="hero-lead"><?= __('hero.subheadline') ?></p>
      
      <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/reservation" class="btn btn-primary">
          <?= __('hero.cta_primary') ?>
        </a>
        <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/packages" class="btn btn-secondary">
          <?= __('hero.cta_secondary') ?>
        </a>
      </div>
    </div>

    <div class="hero-visual">
      <img src="https://res.cloudinary.com/dhyufaqzh/image/upload/v1787593558/Dummy-image.png" alt="Tiranda Jogja Travel">
    </div>
  </div>
</section>

<!-- 2. Trust Metrics Bar -->
<section class="trust-bar">
  <div class="container trust-grid">
    <div class="trust-item">
      <div class="trust-icon">✓</div>
      <div>
        <h4 style="font-size: 0.95rem; margin-bottom: 0.2rem;"><?= __('trust.badge1_title') ?></h4>
        <p style="font-size: 0.85rem; color: var(--color-text-muted);"><?= __('trust.badge1_desc') ?></p>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon">★</div>
      <div>
        <h4 style="font-size: 0.95rem; margin-bottom: 0.2rem;"><?= __('trust.badge2_title') ?></h4>
        <p style="font-size: 0.85rem; color: var(--color-text-muted);"><?= __('trust.badge2_desc') ?></p>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon">⚡</div>
      <div>
        <h4 style="font-size: 0.95rem; margin-bottom: 0.2rem;"><?= __('trust.badge3_title') ?></h4>
        <p style="font-size: 0.85rem; color: var(--color-text-muted);"><?= __('trust.badge3_desc') ?></p>
      </div>
    </div>
  </div>
</section>

<!-- 3. Section Layanan Utama (Services) -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="tagline"><?= __('sections.services_tag') ?></span>
      <h2><?= __('sections.services_title') ?></h2>
      <p><?= __('sections.services_sub') ?></p>
    </div>

    <div class="cards-grid">
      <?php foreach ($services as $s): ?>
        <article class="card">
          <img src="<?= Security::e($s['featured_image']) ?>" alt="<?= Security::e($s['title']) ?>" class="card-img" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=600&q=80'">
          <div class="card-body">
            <h3 class="card-title"><?= Security::e($s['title']) ?></h3>
            <p class="card-text"><?= Security::e($s['short_description']) ?></p>
            <div class="card-footer">
              <div class="card-price">
                IDR <?= number_format((float)$s['base_price'], 0, ',', '.') ?>
                <span>/ <?= Security::e($s['price_unit']) ?></span>
              </div>
              <a href="<?= BASE_URL ?>/<?= $locale ?>/services/<?= Security::e($s['slug']) ?>" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
                <?= __('btn.detail') ?>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 2.5rem;">
      <a href="<?= BASE_URL ?>/<?= $locale ?>/services" class="btn btn-secondary"><?= __('btn.view_all_services') ?> &rarr;</a>
    </div>
  </div>
</section>

<!-- 4. Section Paket Wisata Terpadu (Fokus Narasi & Itinerary — Tanpa Gambar) -->
<section class="section" style="background: var(--color-surface); border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
  <div class="container">
    <div class="section-header">
      <span class="tagline"><?= __('sections.packages_tag') ?></span>
      <h2><?= __('sections.packages_title') ?></h2>
      <p><?= __('sections.packages_sub') ?></p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
      <?php foreach ($packages as $pkg): 
        $itineraryList = !empty($pkg['itinerary_json']) ? array_slice(json_decode($pkg['itinerary_json'], true) ?: [], 0, 3) : [];
      ?>
        <article class="card" style="padding: 2rem; border-top: 4px solid var(--color-accent); justify-content: space-between; border-radius: var(--radius-md);">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
              <span class="tagline" style="margin-bottom: 0;">⏱ <?= Security::e($pkg['duration_text']) ?></span>
              <span style="font-size: 0.75rem; color: var(--color-text-muted); background: var(--color-bg-sand); padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600;">
                Min. <?= (int)$pkg['min_pax'] ?> Pax
              </span>
            </div>

            <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem; color: var(--color-primary);"><?= Security::e($pkg['title']) ?></h3>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.7; margin-bottom: 1.25rem;">
              <?= Security::e($pkg['short_description']) ?>
            </p>

            <?php if (!empty($itineraryList)): ?>
              <div style="border-left: 2px solid var(--color-accent); padding-left: 0.85rem; margin-bottom: 1.5rem; font-size: 0.825rem; color: var(--color-text-main); display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($itineraryList as $step): ?>
                  <div>&bull; <?= Security::e($step) ?></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="card-footer" style="padding-top: 1rem; border-top: 1px solid var(--color-border); align-items: center;">
            <div class="card-price">
              IDR <?= number_format((float)$pkg['price_per_person'], 0, ',', '.') ?>
              <span>/ org</span>
            </div>
            <a href="<?= BASE_URL ?>/<?= $locale ?>/packages/<?= Security::e($pkg['slug']) ?>" class="btn btn-primary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
              <?= __('btn.itinerary') ?> &rarr;
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 2.5rem;">
      <a href="<?= BASE_URL ?>/<?= $locale ?>/packages" class="btn btn-secondary"><?= __('btn.view_all_packages') ?> &rarr;</a>
    </div>
  </div>
</section>

<!-- 5. CONTINUOUS CAROUSEL (KHUSUS GALERI FOTO DOKUMENTASI) -->
<section class="gallery-marquee-section">
  <div class="container" style="text-align: center; margin-bottom: 2rem;">
    <span class="tagline" style="color: var(--color-accent);"><?= __('sections.gallery_tag') ?></span>
    <h2 style="color: #ffffff; font-size: 2rem;"><?= __('sections.gallery_title') ?></h2>
  </div>

  <div class="marquee-track-wrapper">
    <div class="marquee-track">
      <?php foreach ($marqueeGallery as $g): ?>
        <div class="marquee-item">
          <img src="<?= Security::e($g['image_url']) ?>" alt="<?= Security::e($g['title']) ?>" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=500&q=80'">
          <div class="marquee-caption"><?= Security::e($g['title']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="text-align: center; margin-top: 2rem;">
    <a href="<?= BASE_URL ?>/<?= $locale ?>/gallery" class="btn btn-secondary" style="border-color: rgba(255,255,255,0.3); color: #ffffff;">
      <?= __('btn.view_all_gallery') ?> &rarr;
    </a>
  </div>
</section>

<!-- 6. Section Spesifikasi Armada (Fleet) -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="tagline"><?= __('sections.fleet_tag') ?></span>
      <h2><?= __('sections.fleet_title') ?></h2>
      <p><?= __('sections.fleet_sub') ?></p>
    </div>

    <div class="cards-grid">
      <?php foreach ($fleet as $f): ?>
        <article class="card">
          <img src="<?= Security::e($f['featured_image']) ?>" alt="<?= Security::e($f['name']) ?>" class="card-img" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=600&q=80'">
          <div class="card-body">
            <span class="tagline" style="margin-bottom: 0.25rem;"><?= strtoupper(Security::e($f['category'])) ?></span>
            <h3 class="card-title"><?= Security::e($f['name']) ?></h3>
            <div style="display: flex; gap: 0.85rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--color-text-muted); background: var(--color-bg-sand); padding: 0.5rem 0.75rem; border-radius: 6px;">
              <div>👥 <?= (int)$f['capacity_passengers'] ?> <?= __('fleet_spec.seats') ?></div>
              <div>🧳 <?= (int)$f['capacity_luggage'] ?> <?= __('fleet_spec.luggage') ?></div>
              <div>⚙️ <?= ucfirst(Security::e($f['transmission'])) ?></div>
            </div>
            <div class="card-footer">
              <div class="card-price">
                IDR <?= number_format((float)$f['with_driver_rate'], 0, ',', '.') ?>
                <span>/ 12 jam (Driver)</span>
              </div>
              <a href="<?= BASE_URL ?>/<?= $locale ?>/reservation" class="btn btn-primary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
                <?= __('btn.book_this') ?>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 7. Section Wawasan & Artikel Wisata (Articles) -->
<section class="section" style="background: var(--color-surface); border-top: 1px solid var(--color-border);">
  <div class="container">
    <div class="section-header">
      <span class="tagline"><?= __('sections.articles_tag') ?></span>
      <h2><?= __('sections.articles_title') ?></h2>
    </div>

    <div class="cards-grid">
      <?php foreach ($articles as $art): ?>
        <article class="card">
          <img src="<?= Security::e($art['featured_image']) ?>" alt="<?= Security::e($art['title']) ?>" class="card-img" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=600&q=80'">
          <div class="card-body">
            <small style="color: var(--color-accent); font-weight: 600; margin-bottom: 0.4rem; display: block;">
              <?= date('d M Y', strtotime($art['published_at'])) ?>
            </small>
            <h3 class="card-title" style="font-size: 1.1rem;"><?= Security::e($art['title']) ?></h3>
            <p class="card-text"><?= Security::e($art['excerpt']) ?></p>
            <div class="card-footer" style="padding-top: 0.75rem;">
              <a href="<?= BASE_URL ?>/<?= $locale ?>/articles/<?= Security::e($art['slug']) ?>" style="color: var(--color-accent); font-weight: 600; font-size: 0.875rem;">
                <?= __('btn.read_more') ?> &rarr;
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 8. Final Conversion CTA Section -->
<section style="background: var(--color-primary); color: #ffffff; padding: 5rem 0; text-align: center;">
  <div class="container" style="max-width: 750px;">
    <span class="tagline" style="color: var(--color-accent);">Tiranda Jogja Service</span>
    <h2 style="color: #ffffff; font-size: 2.25rem; margin-bottom: 1rem;"><?= __('sections.cta_title') ?></h2>
    <p style="color: #94a3b8; font-size: 1rem; margin-bottom: 2rem; line-height: 1.7;"><?= __('sections.cta_sub') ?></p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="<?= BASE_URL ?>/<?= $locale ?>/reservation" class="btn btn-primary">
        <?= __('hero.cta_primary') ?>
      </a>
      <a href="https://wa.me/6281200000000?text=Halo%20Tiranda%20Jogja,%20saya%20ingin%20konsultasi" target="_blank" rel="noopener" class="btn btn-secondary" style="border-color: rgba(255,255,255,0.3); color: #ffffff;">
        <?= __('btn.chat_wa') ?>
      </a>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>