<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();

$stmt = $db->prepare("
    SELECT a.id, a.featured_image, a.published_at,
           COALESCE(at.title, at_def.title) AS title,
           COALESCE(at.excerpt, at_def.excerpt) AS excerpt,
           COALESCE(at.slug, at_def.slug) AS slug
    FROM articles a
    LEFT JOIN article_translations at ON a.id = at.article_id AND at.language_code = ?
    LEFT JOIN article_translations at_def ON a.id = at_def.article_id AND at_def.language_code = 'id'
    WHERE a.is_published = 1
    ORDER BY a.published_at DESC
");
$stmt->bind_param('s', $locale);
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Artikel & Tips Wisata Jogja';
$pageDescription = 'Kumpulan panduan perjalanan, rekomendasi destinasi, dan tips transportasi di Yogyakarta.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<section class="section" style="padding-top: 3.5rem;">
  <div class="container">
    <div class="section-header">
      <span class="tagline">Wawasan Perjalanan</span>
      <h1>Artikel & Panduan Wisata Jogja</h1>
      <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
        Rekomendasi destinasi wisata, panduan rute transportasi, dan tips liburan nyaman di Yogyakarta.
      </p>
    </div>

    <div class="cards-grid">
      <?php if (!empty($articles)): ?>
        <?php foreach ($articles as $art): ?>
          <article class="card">
            <div class="card-img-wrapper">
              <img src="<?= Security::e($art['featured_image']) ?>" alt="<?= Security::e($art['title']) ?>" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=600&q=80'">
            </div>
            <div class="card-body">
              <small style="color: var(--color-accent); font-weight: 600; margin-bottom: 0.5rem; display: block;">
                <?= date('d M Y', strtotime($art['published_at'])) ?>
              </small>
              <h2 class="card-title" style="font-size: 1.15rem;"><?= Security::e($art['title']) ?></h2>
              <p class="card-text"><?= Security::e($art['excerpt']) ?></p>
              
              <div class="card-footer" style="padding-top: 0.75rem;">
                <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/articles/<?= Security::e($art['slug']) ?>" style="color: var(--color-accent); font-weight: 600; font-size: 0.875rem;">
                  Baca Selengkapnya &rarr;
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; grid-column: 1/-1; color: var(--color-text-muted);">
          Belum ada artikel yang dipublikasikan.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>