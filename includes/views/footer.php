<?php
declare(strict_types=1);
$currentLocale = I18n::getLocale();
$currentPath = $this->path ?? '';
?>

<!-- Safe-Area Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav" aria-label="Mobile Navigation">
  <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>" class="mob-nav-item <?= $currentPath === '' ? 'active' : '' ?>">
    <span class="mob-nav-icon">✦</span>
    <span>Home</span>
  </a>
  <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/services" class="mob-nav-item <?= str_starts_with($currentPath, 'services') ? 'active' : '' ?>">
    <span class="mob-nav-icon">🚙</span>
    <span><?= __('nav.services') ?></span>
  </a>
  <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/packages" class="mob-nav-item <?= str_starts_with($currentPath, 'packages') ? 'active' : '' ?>">
    <span class="mob-nav-icon">🧭</span>
    <span><?= __('nav.packages') ?></span>
  </a>
  <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/reservation" class="mob-nav-item <?= $currentPath === 'reservation' ? 'active' : '' ?>" style="color: var(--color-accent);">
    <span class="mob-nav-icon">📅</span>
    <span><?= __('nav.book_now') ?></span>
  </a>
  <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/contact" class="mob-nav-item <?= $currentPath === 'contact' ? 'active' : '' ?>">
    <span class="mob-nav-icon">💬</span>
    <span><?= __('nav.contact') ?></span>
  </a>
</nav>

<footer class="site-footer" style="background: var(--color-primary); color: #ffffff; padding: 4.5rem 0 2.5rem; margin-top: 4rem;">
  <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 3rem; margin-bottom: 3rem;">
    <div>
      <div class="brand-logo" style="color: #ffffff; margin-bottom: 1rem;">TIRANDA<span>JOGJA</span></div>
      <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.7;">
        <?= __('footer.tagline') ?>
      </p>
    </div>

    <div>
      <h4 style="color: #ffffff; margin-bottom: 1.25rem; font-size: 1rem;"><?= __('footer.services_title') ?></h4>
      <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.875rem; color: #94a3b8;">
        <li><a href="<?= BASE_URL ?>/<?= $currentLocale ?>/packages">One Day Heritage Tour</a></li>
        <li><a href="<?= BASE_URL ?>/<?= $currentLocale ?>/services/airport-transfer-yia-jogja">Airport Transfer Bandara YIA</a></li>
        <li><a href="<?= BASE_URL ?>/<?= $currentLocale ?>/fleet">Pilihan Armada Hiace & SUV</a></li>
      </ul>
    </div>

    <div>
      <h4 style="color: #ffffff; margin-bottom: 1.25rem; font-size: 1rem;"><?= __('footer.contact_title') ?></h4>
      <p style="color: #94a3b8; font-size: 0.875rem; line-height: 1.8;">
        D.I. Yogyakarta, Indonesia<br>
        Layanan Armada: 24 Jam Nonstop<br>
        Email: info@tirandajogja.com
      </p>
    </div>
  </div>

  <div class="container" style="border-top: 1px solid var(--color-primary-light); padding-top: 2rem; display: flex; justify-content: space-between; font-size: 0.85rem; color: #94a3b8; flex-wrap: wrap; gap: 1rem;">
    <p>&copy; <?= date('Y') ?> Tiranda Jogja. <?= __('footer.copyright') ?></p>
    <p style="letter-spacing: 0.02em;">Designed & Developed by <strong style="color: var(--color-accent);">Sem Adler</strong></p>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>