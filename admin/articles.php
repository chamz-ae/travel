<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->bind_param('i', $articleId);
        if ($stmt->execute()) {
            $message = 'Artikel berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus artikel.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_publish') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $artId = (int)($_POST['article_id'] ?? 0);
        $stmt = $db->prepare("UPDATE articles SET is_published = NOT is_published WHERE id = ?");
        $stmt->bind_param('i', $artId);
        $stmt->execute();
        $message = 'Status publikasi artikel berhasil diubah.';
    }
}

$languages = $db->query("SELECT code, name FROM languages WHERE is_active = 1 ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$articles = $db->query("
    SELECT a.*, 
           COALESCE(at_id.title, '(Tanpa Judul ID)') AS title_id,
           COALESCE(at_id.slug, '') AS slug_id,
           GROUP_CONCAT(at_all.language_code) AS translated_locales
    FROM articles a
    LEFT JOIN article_translations at_id ON a.id = at_id.article_id AND at_id.language_code = 'id'
    LEFT JOIN article_translations at_all ON a.id = at_all.article_id
    GROUP BY a.id
    ORDER BY a.published_at DESC
")->fetch_all(MYSQLI_ASSOC);

$activePage = 'articles';
$pageTitle = 'Kelola Artikel';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 style="font-size: 1.125rem;">Daftar Artikel & Panduan Wisata</h2>
    <a href="<?= BASE_URL ?>/admin/article-form.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
      + Buat Artikel Baru
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 80px;">Cover</th>
          <th>Judul Artikel (ID)</th>
          <th>Slug</th>
          <th>Bahasa</th>
          <th>Tanggal Rilis</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($articles)): ?>
          <?php foreach ($articles as $art): 
            $translatedList = explode(',', (string)$art['translated_locales']);
          ?>
            <tr>
              <td>
                <img src="<?= Security::e($art['featured_image']) ?>" alt="Cover" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=120&q=80'">
              </td>
              <td><strong><?= Security::e($art['title_id']) ?></strong></td>
              <td><small style="color: var(--color-text-muted);">/articles/<?= Security::e($art['slug_id']) ?></small></td>
              <td>
                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                  <?php foreach ($languages as $lang): 
                    $isTranslated = in_array($lang['code'], $translatedList, true);
                  ?>
                    <span style="font-size: 0.65rem; padding: 0.15rem 0.35rem; border-radius: 4px; font-weight: 700; background: <?= $isTranslated ? '#dcfce7' : '#fee2e2' ?>; color: <?= $isTranslated ? '#15803d' : '#b91c1c' ?>;">
                      <?= strtoupper($lang['code']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </td>
              <td style="font-size: 0.85rem; color: var(--color-text-muted);">
                <?= date('d M Y', strtotime($art['published_at'])) ?>
              </td>
              <td>
                <form method="POST" action="" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_publish">
                  <input type="hidden" name="article_id" value="<?= $art['id'] ?>">
                  <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                    <span class="badge badge-<?= $art['is_published'] ? 'confirmed' : 'cancelled' ?>">
                      <?= $art['is_published'] ? 'Publish' : 'Draft' ?>
                    </span>
                  </button>
                </form>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.35rem;">
                  <a href="<?= BASE_URL ?>/admin/article-form.php?id=<?= $art['id'] ?>" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                    Edit
                  </a>
                  <form method="POST" action="" onsubmit="return confirm('Hapus artikel ini?');" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="article_id" value="<?= $art['id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; color: #dc2626; border-color: #fca5a5;">
                      Hapus
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center; color: var(--color-text-muted);">Belum ada artikel dipublikasikan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>