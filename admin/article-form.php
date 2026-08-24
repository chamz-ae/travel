<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once __DIR__ . '/includes/uploader.php';

Auth::requireAuth();
$db = Database::getConnection();

$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $articleId > 0;
$errors = [];
$success = '';

$languages = $db->query("SELECT code, name, native_name FROM languages WHERE is_active = 1 ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$article = [
    'featured_image' => '',
    'is_published'   => 1,
    'published_at'   => date('Y-m-d\TH:i')
];
$translations = [];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) {
        header('Location: ' . BASE_URL . '/admin/articles.php');
        exit;
    }
    $article = $existing;
    $article['published_at'] = date('Y-m-d\TH:i', strtotime($existing['published_at']));

    $stmtTrans = $db->prepare("SELECT * FROM article_translations WHERE article_id = ?");
    $stmtTrans->bind_param('i', $articleId);
    $stmtTrans->execute();
    foreach ($stmtTrans->get_result()->fetch_all(MYSQLI_ASSOC) as $tr) {
        $translations[$tr['language_code']] = $tr;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi keamanan kadaluarsa.';
    }

    $featuredImage = Security::sanitizeString($_POST['featured_image_url'] ?? $article['featured_image']);
    $publishedAt   = Security::sanitizeString($_POST['published_at'] ?? date('Y-m-d H:i:s'));
    $isPublished   = isset($_POST['is_published']) ? 1 : 0;

    // Handle Upload File Gambar
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = MediaUploader::upload($_FILES['image_file']);
        if ($uploadResult['success']) {
            $featuredImage = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    $idTitle = Security::sanitizeString($_POST['trans']['id']['title'] ?? '');
    if (empty($idTitle)) {
        $errors[] = 'Judul artikel versi Bahasa Indonesia wajib diisi.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE articles SET featured_image = ?, is_published = ?, published_at = ? WHERE id = ?");
            $stmt->bind_param('sisi', $featuredImage, $isPublished, $publishedAt, $articleId);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO articles (featured_image, is_published, published_at) VALUES (?, ?, ?)");
            $stmt->bind_param('sis', $featuredImage, $isPublished, $publishedAt);
            $stmt->execute();
            $articleId = (int)$db->insert_id;
            $isEdit = true;
        }

        $article['featured_image'] = $featuredImage;

        foreach ($languages as $lang) {
            $code = $lang['code'];
            $title       = Security::sanitizeString($_POST['trans'][$code]['title'] ?? '');
            $slug        = Security::sanitizeString($_POST['trans'][$code]['slug'] ?? '');
            $excerpt     = Security::sanitizeString($_POST['trans'][$code]['excerpt'] ?? '');
            $contentHtml = trim((string)($_POST['trans'][$code]['content_html'] ?? ''));
            $metaTitle   = Security::sanitizeString($_POST['trans'][$code]['meta_title'] ?? '');
            $metaDesc    = Security::sanitizeString($_POST['trans'][$code]['meta_description'] ?? '');

            if ($code !== 'id' && empty($title)) continue;

            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            }

            $stmtTransSave = $db->prepare("
                INSERT INTO article_translations (article_id, language_code, slug, title, excerpt, content_html, meta_title, meta_description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    slug = VALUES(slug),
                    title = VALUES(title),
                    excerpt = VALUES(excerpt),
                    content_html = VALUES(content_html),
                    meta_title = VALUES(meta_title),
                    meta_description = VALUES(meta_description)
            ");
            $stmtTransSave->bind_param('isssssss', $articleId, $code, $slug, $title, $excerpt, $contentHtml, $metaTitle, $metaDesc);
            $stmtTransSave->execute();
        }

        $success = 'Data artikel berhasil disimpan.';
    }
}

$activePage = 'articles';
$pageTitle = $isEdit ? 'Edit Artikel' : 'Tulis Artikel Baru';
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
  <a href="<?= BASE_URL ?>/admin/articles.php" style="color: var(--admin-text-muted); font-size: 0.875rem;">&larr; Kembali ke Daftar Artikel</a>
</div>

<?php if (!empty($success)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($success) ?>
  </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="admin-form-grid">
  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

  <!-- Kolom Kiri: Cover & Publikasi -->
  <div class="admin-card">
    <h3 style="font-size: 1rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
      Pengaturan Cover & Jadwal
    </h3>

    <!-- DUAL IMAGE UPLOAD WIDGET -->
    <div class="image-source-box">
      <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--admin-sidebar); margin-bottom: 0.4rem;">
        Gambar Sampul Artikel
      </label>
      
      <div style="margin-bottom: 0.75rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 1: Upload File Gambar</span>
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" style="font-size: 0.8rem; width: 100%;">
      </div>

      <div style="margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 2: Atau Masukkan URL Gambar</span>
        <input type="text" name="featured_image_url" placeholder="https://..." value="<?= Security::e($article['featured_image']) ?>" style="width: 100%; padding: 0.55rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.8rem;">
      </div>

      <?php if (!empty($article['featured_image'])): ?>
        <div class="image-preview-wrapper" style="height: 160px;">
          <img src="<?= Security::e($article['featured_image']) ?>" alt="Preview">
        </div>
      <?php endif; ?>
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Jadwal Rilis</label>
      <input type="datetime-local" name="published_at" value="<?= Security::e($article['published_at']) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1.5rem;">
      <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
        <input type="checkbox" name="is_published" value="1" <?= (int)$article['is_published'] === 1 ? 'checked' : '' ?>>
        Publikasikan Artikel
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
      Simpan Artikel
    </button>
  </div>

  <!-- Kolom Kanan: Multilingual Tabs -->
  <div class="admin-card">
    <div class="tab-headers-scroll">
      <?php foreach ($languages as $index => $lang): ?>
        <button type="button" class="tab-btn btn <?= $index === 0 ? 'btn-primary' : 'btn-secondary' ?>" onclick="switchArticleTab('<?= $lang['code'] ?>', this)" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
          <?= Security::e($lang['name']) ?> (<?= strtoupper($lang['code']) ?>)
        </button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($languages as $index => $lang): 
      $code = $lang['code'];
      $t = $translations[$code] ?? [];
    ?>
      <div id="tab-art-<?= $code ?>" class="art-tab-pane" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
        <div class="form-group-row">
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">
              Judul Artikel (<?= strtoupper($code) ?>) <?= $code === 'id' ? '*' : '' ?>
            </label>
            <input type="text" name="trans[<?= $code ?>][title]" value="<?= Security::e($t['title'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
          </div>
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Clean URL Slug</label>
            <input type="text" name="trans[<?= $code ?>][slug]" value="<?= Security::e($t['slug'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
          </div>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Ringkasan / Excerpt</label>
          <textarea name="trans[<?= $code ?>][excerpt]" rows="2" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;"><?= Security::e($t['excerpt'] ?? '') ?></textarea>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Konten Lengkap</label>
          <textarea name="trans[<?= $code ?>][content_html]" rows="8" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.875rem; font-family: inherit;"><?= Security::e($t['content_html'] ?? '') ?></textarea>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</form>

<script>
function switchArticleTab(langCode, btn) {
  document.querySelectorAll('.art-tab-pane').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  const target = document.getElementById('tab-art-' + langCode);
  if (target) target.style.display = 'block';
  btn.classList.add('btn-primary');
  btn.classList.remove('btn-secondary');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>