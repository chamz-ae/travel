<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();
$slug = $packageSlug ?? '';

$stmt = $db->prepare("
    SELECT p.*,
           COALESCE(pt.title, pt_def.title) AS title,
           COALESCE(pt.short_description, pt_def.short_description) AS short_description,
           COALESCE(pt.itinerary_json, pt_def.itinerary_json) AS itinerary_json,
           COALESCE(pt.includes_json, pt_def.includes_json) AS includes_json,
           COALESCE(pt.meta_title, pt_def.meta_title) AS meta_title,
           COALESCE(pt.meta_description, pt_def.meta_description) AS meta_description
    FROM packages p
    LEFT JOIN package_translations pt ON p.id = pt.package_id AND pt.language_code = ?
    LEFT JOIN package_translations pt_def ON p.id = pt_def.package_id AND pt_def.language_code = 'id'
    WHERE (pt.slug = ? OR pt_def.slug = ?) AND p.is_active = 1
    LIMIT 1
");
$stmt->bind_param('sss', $locale, $slug, $slug);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();

if (!$pkg) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    exit;
}

$pageTitle = $pkg['meta_title'] ?: $pkg['title'];
$pageDescription = $pkg['meta_description'] ?: $pkg['short_description'];
$itinerary = !empty($pkg['itinerary_json']) ? json_decode($pkg['itinerary_json'], true) : [];
$includes  = !empty($pkg['includes_json']) ? json_decode($pkg['includes_json'], true) : [];

require_once INCLUDES_PATH . '/views/header.php';
?>

<div class="container" style="padding: 3.5rem 1.5rem 6rem;">
  <div style="margin-bottom: 2rem;">
    <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/packages" style="color: var(--color-text-muted); font-size: 0.875rem;">
      &larr; <?= __('btn.view_all_packages') ?>
    </a>
  </div>

  <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 3.5rem; align-items: start;">
    <article>
      <span class="tagline">⏱ Durasi: <?= Security::e($pkg['duration_text']) ?> &bull; Min. <?= (int)$pkg['min_pax'] ?> Orang</span>
      <h1 style="font-size: clamp(2rem, 3.5vw, 2.75rem); margin-bottom: 1.5rem; color: var(--color-primary);">
        <?= Security::e($pkg['title']) ?>
      </h1>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.15rem; margin-bottom: 0.75rem; color: var(--color-accent);">Eksplorasi & Pengalaman Wisata</h3>
        <p style="font-size: 1rem; line-height: 1.85; color: var(--color-text-main);">
          <?= nl2br(Security::e($pkg['short_description'])) ?>
        </p>
      </div>

      <!-- Timeline Rencana Perjalanan -->
      <?php if (!empty($itinerary) && is_array($itinerary)): ?>
        <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--color-primary);">Susunan Itinerary Lengkap</h3>
        <div style="border-left: 2px solid var(--color-accent); padding-left: 1.75rem; margin-bottom: 3rem; display: flex; flex-direction: column; gap: 1.25rem;">
          <?php foreach ($itinerary as $step): ?>
            <div style="position: relative; font-size: 0.95rem; line-height: 1.7; color: var(--color-text-main);">
              <span style="position: absolute; left: -2.35rem; top: 0.25rem; width: 10px; height: 10px; border-radius: 50%; background: var(--color-accent);"></span>
              <?= Security::e($step) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Fasilitas Termasuk -->
      <?php if (!empty($includes) && is_array($includes)): ?>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.75rem; margin-bottom: 2rem;">
          <h3 style="margin-bottom: 1rem; font-size: 1.125rem; color: var(--color-primary);">Fasilitas Termasuk:</h3>
          <ul style="list-style: none; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.85rem;">
            <?php foreach ($includes as $inc): ?>
              <li style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--color-text-muted);">
                <span style="color: var(--color-success); font-weight: bold;">✓</span> <?= Security::e($inc) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </article>

    <!-- Sticky Booking Summary Widget -->
    <aside style="position: sticky; top: 100px;">
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2.25rem; box-shadow: var(--shadow-lg);">
        <span class="tagline">Estimasi Biaya</span>
        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary); margin-bottom: 0.5rem;">
          IDR <?= number_format((float)$pkg['price_per_person'], 0, ',', '.') ?>
          <span style="font-size: 0.85rem; font-weight: 400; color: var(--color-text-muted);">/ orang</span>
        </div>
        <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1.75rem; line-height: 1.6;">
          *Tarif berbasis minimal pemesanan <?= (int)$pkg['min_pax'] ?> orang. Destinasi dan jadwal rute bersifat fleksibel dan dapat dikustomisasi.
        </p>

        <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/reservation" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
          Pesan Paket Tour Ini
        </a>
        <a href="https://wa.me/6281200000000?text=Halo%20Tiranda%20Jogja,%20saya%20ingin%20konsultasi%20paket%20<?= urlencode($pkg['title']) ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="width: 100%;">
          <?= __('btn.chat_wa') ?>
        </a>
      </div>
    </aside>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>