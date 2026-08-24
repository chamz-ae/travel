<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();
$slug = $articleSlug ?? '';

$stmt = $db->prepare("
    SELECT a.id, a.featured_image, a.published_at,
           COALESCE(at.title, at_def.title) AS title,
           COALESCE(at.excerpt, at_def.excerpt) AS excerpt,
           COALESCE(at.content_html, at_def.content_html) AS content_html,
           COALESCE(at.meta_title, at_def.meta_title) AS meta_title,
           COALESCE(at.meta_description, at_def.meta_description) AS meta_description,
           COALESCE(at.slug, at_def.slug) AS current_slug
    FROM articles a
    LEFT JOIN article_translations at ON a.id = at.article_id AND at.language_code = ?
    LEFT JOIN article_translations at_def ON a.id = at_def.article_id AND at_def.language_code = 'id'
    WHERE (at.slug = ? OR at_def.slug = ?) AND a.is_published = 1
    LIMIT 1
");
$stmt->bind_param('sss', $locale, $slug, $slug);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

if (!$article) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    exit;
}

$pageTitle = $article['meta_title'] ?: $article['title'];
$pageDescription = $article['meta_description'] ?: $article['excerpt'];

require_once INCLUDES_PATH . '/views/header.php';
?>

<div class="container" style="max-width: 820px; padding: 3.5rem 1.5rem 6rem;">
  <div style="margin-bottom: 2rem;">
    <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/articles" style="color: var(--color-text-muted); font-size: 0.875rem;">
      &larr; Kembali ke Semua Artikel
    </a>
  </div>

  <article>
    <span class="tagline">Panduan Wisata • <?= date('d F Y', strtotime($article['published_at'])) ?></span>
    <h1 style="font-size: clamp(1.85rem, 3.5vw, 2.5rem); margin-bottom: 1.5rem; line-height: 1.3;">
      <?= Security::e($article['title']) ?>
    </h1>

    <div style="border-radius: var(--radius-md); overflow: hidden; margin-bottom: 2.5rem; box-shadow: var(--shadow-md);">
      <img src="<?= Security::e($article['featured_image']) ?>" alt="<?= Security::e($article['title']) ?>" style="width: 100%; height: auto; max-height: 480px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=1000&q=80'">
    </div>

    <div style="font-size: 1.05rem; line-height: 1.9; color: var(--color-text-main);">
      <?= nl2br(Security::e($article['content_html'])) ?>
    </div>
  </article>

  <!-- CTA Box di Bawah Artikel -->
  <div style="background: var(--color-bg-light); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem; margin-top: 4rem; text-align: center;">
    <h3 style="margin-bottom: 0.5rem; font-size: 1.25rem;">Rencanakan Perjalanan Anda Bersama Tiranda Jogja</h3>
    <p style="color: var(--color-text-muted); font-size: 0.9rem; max-width: 500px; margin: 0 auto 1.5rem;">
      Layanan sewa mobil + driver profesional dan paket tour privat terpercaya di Yogyakarta.
    </p>
    <a href="<?= BASE_URL ?>/<?= Security::e($locale) ?>/reservation" class="btn btn-primary">
      Reservasi Perjalanan
    </a>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>