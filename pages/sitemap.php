<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

$db = Database::getConnection();
$staticRoutes = ['', 'about', 'services', 'reservation', 'contact'];

// Ambil semua service aktif beserta seluruh varian slug terjemahannya
$servicesQuery = $db->query("
    SELECT s.id, s.updated_at, st.language_code, st.slug
    FROM services s
    JOIN service_translations st ON s.id = st.service_id
    WHERE s.is_active = 1
    ORDER BY s.id, st.language_code
");

$servicesByItem = [];
while ($row = $servicesQuery->fetch_assoc()) {
    $servicesByItem[$row['id']]['updated_at'] = $row['updated_at'];
    $servicesByItem[$row['id']]['translations'][$row['language_code']] = $row['slug'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

  <!-- 1. Halaman Statis Multibahasa -->
  <?php foreach ($staticRoutes as $route): ?>
    <?php foreach (SUPPORTED_LOCALES as $locale): 
      $pageUrl = BASE_URL . '/' . $locale . ($route !== '' ? '/' . $route : '');
    ?>
      <url>
        <loc><?= Security::e($pageUrl) ?></loc>
        <?php foreach (SUPPORTED_LOCALES as $altLocale): ?>
          <xhtml:link rel="alternate" hreflang="<?= Security::e($altLocale) ?>" href="<?= Security::e(BASE_URL . '/' . $altLocale . ($route !== '' ? '/' . $route : '')) ?>" />
        <?php endforeach; ?>
        <changefreq>weekly</changefreq>
        <priority><?= $route === '' ? '1.0' : '0.8' ?></priority>
      </url>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <!-- 2. Halaman Detail Layanan Dinamis Multibahasa -->
  <?php foreach ($servicesByItem as $item): ?>
    <?php foreach ($item['translations'] as $langCode => $slug): 
      $serviceUrl = BASE_URL . '/' . $langCode . '/services/' . $slug;
    ?>
      <url>
        <loc><?= Security::e($serviceUrl) ?></loc>
        <?php foreach ($item['translations'] as $altLang => $altSlug): ?>
          <xhtml:link rel="alternate" hreflang="<?= Security::e($altLang) ?>" href="<?= Security::e(BASE_URL . '/' . $altLang . '/services/' . $altSlug) ?>" />
        <?php endforeach; ?>
        <lastmod><?= date('Y-m-d', strtotime($item['updated_at'] ?: date('Y-m-d'))) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
      </url>
    <?php endforeach; ?>
  <?php endforeach; ?>

</urlset>