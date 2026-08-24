<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

$gallery = $db->query("SELECT * FROM gallery WHERE is_active = 1 ORDER BY display_order ASC, id DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Galeri & Dokumentasi Perjalanan — Tiranda Jogja';
$pageDescription = 'Dokumentasi perjalanan wisata, kondisi armada, dan momen kepuasan pelanggan Tiranda Jogja.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="padding-top: 3.5rem;">
  <div class="container">
    <div class="section-header">
      <span class="tagline">Dokumentasi Nyata</span>
      <h1>Galeri Perjalanan & Armada</h1>
      <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
        Kumpulan potret momen wisata dan unit kendaraan operasional kami di Daerah Istimewa Yogyakarta.
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
      <?php if (!empty($gallery)): ?>
        <?php foreach ($gallery as $item): ?>
          <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
            <div style="height: 220px; overflow: hidden;">
              <img src="<?= Security::e($item['image_url']) ?>" alt="<?= Security::e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=600&q=80'">
            </div>
            <div style="padding: 1rem 1.25rem;">
              <span class="tagline" style="font-size: 0.75rem; margin-bottom: 0.25rem;"><?= Security::e($item['category']) ?></span>
              <h3 style="font-size: 1rem;"><?= Security::e($item['title']) ?></h3>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">Belum ada dokumentasi terpublikasi.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>