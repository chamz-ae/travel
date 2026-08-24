<?php
declare(strict_types=1);

require_once INCLUDES_PATH . '/settings.php';

$currentLocale = I18n::getLocale();
$currentPath = $this->path ?? '';
$canonicalUrl = BASE_URL . '/' . $currentLocale . ($currentPath !== '' ? '/' . $currentPath : '');

$companyName = get_setting('company_name', 'Tiranda Jogja');
?>
<!DOCTYPE html>
<html lang="<?= Security::e($currentLocale) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= isset($pageTitle) ? Security::e($pageTitle) . ' — ' : '' ?><?= Security::e($companyName) ?></title>
  <meta name="description" content="<?= isset($pageDescription) ? Security::e($pageDescription) : 'Layanan transportasi, sewa mobil + driver, dan paket wisata Yogyakarta.' ?>">
  
  <link rel="canonical" href="<?= Security::e($canonicalUrl) ?>">
  
  <!-- Hreflang Tags 8 Locales -->
  <?php foreach (SUPPORTED_LOCALES as $code): ?>
    <link rel="alternate" hreflang="<?= Security::e($code) ?>" href="<?= Security::e(BASE_URL . '/' . $code . ($currentPath !== '' ? '/' . $currentPath : '')) ?>">
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= Security::e(BASE_URL . '/' . DEFAULT_LOCALE . ($currentPath !== '' ? '/' . $currentPath : '')) ?>">

  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>

<header class="site-header">
  <div class="container navbar">
    <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>" class="brand-logo">
      TIRANDA<span>JOGJA</span>
    </a>

    <nav>
      <ul class="nav-menu">
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>" class="nav-link <?= $currentPath === '' ? 'active' : '' ?>"><?= __('nav.home') ?></a></li>
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/services" class="nav-link <?= str_starts_with($currentPath, 'services') ? 'active' : '' ?>"><?= __('nav.services') ?></a></li>
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/packages" class="nav-link <?= str_starts_with($currentPath, 'packages') ? 'active' : '' ?>"><?= __('nav.packages') ?></a></li>
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/fleet" class="nav-link <?= $currentPath === 'fleet' ? 'active' : '' ?>"><?= __('nav.fleet') ?></a></li>
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/articles" class="nav-link <?= str_starts_with($currentPath, 'articles') ? 'active' : '' ?>"><?= __('nav.articles') ?></a></li>
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/about" class="nav-link <?= $currentPath === 'about' ? 'active' : '' ?>"><?= __('nav.about') ?></a></li>
        <li><a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/contact" class="nav-link <?= $currentPath === 'contact' ? 'active' : '' ?>"><?= __('nav.contact') ?></a></li>
      </ul>
    </nav>

    <div style="display: flex; align-items: center; gap: 0.85rem;">
      <select class="lang-select" onchange="location = this.value;" aria-label="Select Language">
        <?php foreach (SUPPORTED_LOCALES as $code): ?>
          <option value="<?= BASE_URL ?>/<?= Security::e($code) ?><?= $currentPath !== '' ? '/' . Security::e($currentPath) : '' ?>" <?= $code === $currentLocale ? 'selected' : '' ?>>
            <?= strtoupper(Security::e($code)) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <a href="<?= BASE_URL ?>/<?= Security::e($currentLocale) ?>/reservation" class="btn btn-primary" style="padding: 0.65rem 1.25rem; font-size: 0.85rem;">
        <?= __('nav.book_now') ?>
      </a>
    </div>
  </div>
</header>