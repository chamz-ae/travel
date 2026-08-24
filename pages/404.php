<?php
declare(strict_types=1);

$locale = I18n::getLocale();
$pageTitle = '404 — Halaman Tidak Ditemukan';
$pageDescription = 'Halaman yang Anda cari tidak ditemukan atau telah dipindahkan.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="min-height: 65vh; display: flex; align-items: center; text-align: center;">
  <div class="container" style="max-width: 600px;">
    <span class="tagline">Error 404</span>
    <h1 style="font-size: 3rem; margin-bottom: 1rem; color: var(--color-primary);">Halaman Tidak Ditemukan</h1>
    <p style="color: var(--color-text-muted); margin-bottom: 2rem; font-size: 1.05rem;">
      Maaf, rute atau halaman yang Anda tuju tidak tersedia atau telah diperbarui. Silakan kembali ke beranda atau pilih layanan yang tersedia.
    </p>

    <div style="display: flex; gap: 1rem; justify-content: center;">
      <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>" class="btn btn-primary">
        Kembali ke Beranda
      </a>
      <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/services" class="btn btn-secondary">
        Lihat Semua Layanan
      </a>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>