<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

$stmt = $db->prepare("
    SELECT p.*, 
           COALESCE(pt.title, pt_def.title) AS title,
           COALESCE(pt.short_description, pt_def.short_description) AS short_description,
           COALESCE(pt.itinerary_json, pt_def.itinerary_json) AS itinerary_json,
           COALESCE(pt.slug, pt_def.slug) AS slug
    FROM packages p
    LEFT JOIN package_translations pt ON p.id = pt.package_id AND pt.language_code = ?
    LEFT JOIN package_translations pt_def ON p.id = pt_def.package_id AND pt_def.language_code = 'id'
    WHERE p.is_active = 1
    ORDER BY p.display_order ASC
");
$stmt->bind_param('s', $locale);
$stmt->execute();
$packages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Paket Wisata & Tour Jogja — Tiranda Jogja';
$pageDescription = 'Pilihan paket liburan keluarga, tour heritage, dan petualangan di Yogyakarta berfasilitas lengkap.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="padding-top: 3.5rem;">
  <div class="container">
    <div class="section-header">
      <span class="tagline"><?= __('sections.packages_tag') ?></span>
      <h1><?= __('sections.packages_title') ?></h1>
      <p><?= __('sections.packages_sub') ?></p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
      <?php if (!empty($packages)): ?>
        <?php foreach ($packages as $pkg): 
          $itineraryPreview = !empty($pkg['itinerary_json']) ? array_slice(json_decode($pkg['itinerary_json'], true) ?: [], 0, 3) : [];
        ?>
          <article class="card" style="padding: 2rem; border-top: 4px solid var(--color-accent); justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <span class="tagline" style="margin-bottom: 0;">⏱ <?= Security::e($pkg['duration_text']) ?></span>
                <span style="font-size: 0.8rem; color: var(--color-text-muted); background: var(--color-bg-sand); padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600;">
                  Min. <?= (int)$pkg['min_pax'] ?> Pax
                </span>
              </div>

              <h2 style="font-size: 1.35rem; margin-bottom: 0.75rem; color: var(--color-primary);"><?= Security::e($pkg['title']) ?></h2>
              <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.7; margin-bottom: 1.5rem;">
                <?= Security::e($pkg['short_description']) ?>
              </p>

              <?php if (!empty($itineraryPreview)): ?>
                <div style="border-left: 2px solid var(--color-accent); padding-left: 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--color-text-main); display: flex; flex-direction: column; gap: 0.4rem;">
                  <?php foreach ($itineraryPreview as $step): ?>
                    <div>&bull; <?= Security::e($step) ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="card-footer" style="padding-top: 1.25rem; border-top: 1px solid var(--color-border); align-items: center;">
              <div class="card-price">
                IDR <?= number_format((float)$pkg['price_per_person'], 0, ',', '.') ?>
                <span>/ pax</span>
              </div>
              <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/packages/<?= Security::e($pkg['slug']) ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                <?= __('btn.itinerary') ?> &rarr;
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; grid-column: 1/-1; color: var(--color-text-muted);">
          Belum ada paket wisata aktif.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>