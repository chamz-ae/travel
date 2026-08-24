<?php
declare(strict_types=1);

$locale = I18n::getLocale();
$pageTitle = 'Tentang Tiranda Jogja — Layanan Transportasi & Wisata Terpercaya';
$pageDescription = 'Mengenal Tiranda Jogja, penyedia jasa rental mobil, airport transfer, dan pemandu wisata berpengalaman di Yogyakarta.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="padding-top: 3.5rem;">
  <div class="container" style="max-width: 960px;">
    <div class="section-header">
      <span class="tagline">Profil Perusahaan</span>
      <h1>Hospitality & Kenyamanan Perjalanan di Jogja</h1>
    </div>

    <div style="font-size: 1.05rem; line-height: 1.85; color: var(--color-text-main); margin-bottom: 3.5rem;">
      <p style="margin-bottom: 1.5rem;">
        <strong>Tiranda Jogja</strong> didirikan dengan satu komitmen utama: menghadirkan standar transportasi privat dan pendampingan wisata berkualitas tinggi di Daerah Istimewa Yogyakarta dan sekitarnya. Kami memahami bahwa kenyamanan, ketepatan waktu, dan rasa aman adalah prioritas utama setiap wisatawan dan pelaku perjalanan bisnis.
      </p>
      <p style="margin-bottom: 1.5rem;">
        Seluruh unit armada kami melalui inspeksi berkala, perawatan mesin ketat, serta pembersihan menyeluruh sebelum bertugas. Didukung oleh tim driver lokal berlisensi yang ramah, beretika santun, dan memahami rute wisata maupun jalur alternatif, kami memastikan setiap momen perjalanan Anda berjalan tenang dan berkesan.
      </p>
    </div>

    <!-- 4 Pilar Layanan -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 4rem;">
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="color: var(--color-accent); font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">01</div>
        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Armada Terawat</h3>
        <p style="font-size: 0.875rem; color: var(--color-text-muted);">Unit bersih, AC dingin prima, suspensi nyaman, dan tahun perakitan muda.</p>
      </div>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="color: var(--color-accent); font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">02</div>
        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Driver Profesional</h3>
        <p style="font-size: 0.875rem; color: var(--color-text-muted);">Paham etika hospitality, tepat waktu, jujur, dan siap memandu rute destinasi.</p>
      </div>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="color: var(--color-accent); font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">03</div>
        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Tarif Transparan</h3>
        <p style="font-size: 0.875rem; color: var(--color-text-muted);">Penetapan harga jelas sejak awal tanpa biaya siluman di akhir rute perjalanan.</p>
      </div>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="color: var(--color-accent); font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">04</div>
        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Respons Sigap</h3>
        <p style="font-size: 0.875rem; color: var(--color-text-muted);">Layanan operasional aktif mendampingi konsultasi rute dan reservasi Anda.</p>
      </div>
    </div>

    <!-- Conversion Banner -->
    <div style="background: var(--color-primary); color: #ffffff; border-radius: var(--radius-md); padding: 2.5rem; text-align: center;">
      <h2 style="color: #ffffff; margin-bottom: 0.75rem;">Siap Menjelajahi Keindahan Yogyakarta?</h2>
      <p style="color: #94a3b8; max-width: 550px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
        Konsultasikan kebutuhan rute, durasi trip, dan pemilihan tipe mobil bersama konsultan perjalanan kami.
      </p>
      <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/reservation" class="btn btn-primary">
        Reservasi Sekarang
      </a>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>