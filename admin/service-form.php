<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once __DIR__ . '/includes/uploader.php';

Auth::requireAuth();
$db = Database::getConnection();

$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $serviceId > 0;
$errors = [];
$success = '';

$languages = $db->query("SELECT code, name, native_name FROM languages WHERE is_active = 1 ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$service = [
    'identifier'     => '',
    'featured_image' => '',
    'base_price'     => '',
    'price_unit'     => 'per hari / 12 jam',
    'display_order'  => 0,
    'is_active'      => 1
];
$translations = [];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) {
        header('Location: ' . BASE_URL . '/admin/services.php');
        exit;
    }
    $service = $existing;

    $stmtTrans = $db->prepare("SELECT * FROM service_translations WHERE service_id = ?");
    $stmtTrans->bind_param('i', $serviceId);
    $stmtTrans->execute();
    foreach ($stmtTrans->get_result()->fetch_all(MYSQLI_ASSOC) as $tr) {
        $translations[$tr['language_code']] = $tr;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi keamanan kadaluarsa.';
    }

    $identifier     = Security::sanitizeString($_POST['identifier'] ?? '');
    $featuredImage  = Security::sanitizeString($_POST['featured_image_url'] ?? $service['featured_image']);
    $basePrice      = !empty($_POST['base_price']) ? (float)$_POST['base_price'] : null;
    $priceUnit      = Security::sanitizeString($_POST['price_unit'] ?? 'per hari');
    $displayOrder   = (int)($_POST['display_order'] ?? 0);
    $isActive       = isset($_POST['is_active']) ? 1 : 0;

    // Handle Upload File Gambar dari Perangkat
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = MediaUploader::upload($_FILES['image_file']);
        if ($uploadResult['success']) {
            $featuredImage = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    $idTitle = Security::sanitizeString($_POST['trans']['id']['title'] ?? '');
    if (empty($identifier) || empty($idTitle)) {
        $errors[] = 'Identifier dan Judul Bahasa Indonesia wajib diisi.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE services SET identifier = ?, featured_image = ?, base_price = ?, price_unit = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param('ssdsiii', $identifier, $featuredImage, $basePrice, $priceUnit, $displayOrder, $isActive, $serviceId);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO services (identifier, featured_image, base_price, price_unit, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssdsii', $identifier, $featuredImage, $basePrice, $priceUnit, $displayOrder, $isActive);
            $stmt->execute();
            $serviceId = (int)$db->insert_id;
            $isEdit = true;
        }

        $service['featured_image'] = $featuredImage;

        foreach ($languages as $lang) {
            $code = $lang['code'];
            $title       = Security::sanitizeString($_POST['trans'][$code]['title'] ?? '');
            $slug        = Security::sanitizeString($_POST['trans'][$code]['slug'] ?? '');
            $shortDesc   = Security::sanitizeString($_POST['trans'][$code]['short_description'] ?? '');
            $contentHtml = trim((string)($_POST['trans'][$code]['content_html'] ?? ''));
            $featuresRaw = trim((string)($_POST['trans'][$code]['features'] ?? ''));
            $metaTitle   = Security::sanitizeString($_POST['trans'][$code]['meta_title'] ?? '');
            $metaDesc    = Security::sanitizeString($_POST['trans'][$code]['meta_description'] ?? '');

            if ($code !== 'id' && empty($title)) continue;

            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            }

            $featuresArray = array_values(array_filter(array_map('trim', explode("\n", $featuresRaw))));
            $featuresJson  = !empty($featuresArray) ? json_encode($featuresArray, JSON_UNESCAPED_UNICODE) : null;

            $stmtTransSave = $db->prepare("
                INSERT INTO service_translations (service_id, language_code, slug, title, short_description, content_html, features_json, meta_title, meta_description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    slug = VALUES(slug),
                    title = VALUES(title),
                    short_description = VALUES(short_description),
                    content_html = VALUES(content_html),
                    features_json = VALUES(features_json),
                    meta_title = VALUES(meta_title),
                    meta_description = VALUES(meta_description)
            ");
            $stmtTransSave->bind_param('issssssss', $serviceId, $code, $slug, $title, $shortDesc, $contentHtml, $featuresJson, $metaTitle, $metaDesc);
            $stmtTransSave->execute();
        }

        $success = 'Data layanan dan gambar berhasil disimpan.';
    }
}

$activePage = 'services';
$pageTitle = $isEdit ? 'Edit Layanan' : 'Tambah Layanan Baru';
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
  <a href="<?= BASE_URL ?>/admin/services.php" style="color: var(--admin-text-muted); font-size: 0.875rem;">&larr; Kembali ke Daftar Layanan</a>
</div>

<?php if (!empty($success)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($success) ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <ul style="margin-left: 1.25rem;">
      <?php foreach ($errors as $err): ?>
        <li><?= Security::e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="admin-form-grid">
  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

  <!-- Kolom Kiri: Konfigurasi & Dual Image Input -->
  <div class="admin-card">
    <h3 style="font-size: 1rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
      Konfigurasi & Media
    </h3>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Identifier (Code) *</label>
      <input type="text" name="identifier" required placeholder="sewa-mobil-driver" value="<?= Security::e($service['identifier']) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <!-- DUAL IMAGE INPUT WIDGET -->
    <div class="image-source-box">
      <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--admin-sidebar); margin-bottom: 0.4rem;">
        Foto Layanan
      </label>
      
      <div style="margin-bottom: 0.75rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 1: Upload File dari Perangkat</span>
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" style="font-size: 0.8rem; width: 100%;">
      </div>

      <div style="margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 2: Atau Masukkan URL Gambar</span>
        <input type="text" name="featured_image_url" placeholder="https://..." value="<?= Security::e($service['featured_image']) ?>" style="width: 100%; padding: 0.55rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.8rem;">
      </div>

      <?php if (!empty($service['featured_image'])): ?>
        <div class="image-preview-wrapper">
          <img src="<?= Security::e($service['featured_image']) ?>" alt="Preview" onerror="this.src='https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=400&q=80'">
        </div>
      <?php endif; ?>
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Tarif Dasar (IDR)</label>
      <input type="number" name="base_price" value="<?= Security::e((string)$service['base_price']) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Satuan Tarif</label>
      <input type="text" name="price_unit" value="<?= Security::e($service['price_unit']) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Urutan Tampilan</label>
      <input type="number" name="display_order" value="<?= (int)$service['display_order'] ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1.5rem;">
      <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" <?= (int)$service['is_active'] === 1 ? 'checked' : '' ?>>
        Publikasikan Layanan (Aktif)
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
      Simpan Layanan
    </button>
  </div>

  <!-- Kolom Kanan: Multilingual Content -->
  <div class="admin-card">
    <div class="tab-headers-scroll">
      <?php foreach ($languages as $index => $lang): ?>
        <button type="button" class="tab-btn btn <?= $index === 0 ? 'btn-primary' : 'btn-secondary' ?>" onclick="switchLangTab('<?= $lang['code'] ?>', this)" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
          <?= Security::e($lang['name']) ?> (<?= strtoupper($lang['code']) ?>)
        </button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($languages as $index => $lang): 
      $code = $lang['code'];
      $t = $translations[$code] ?? [];
      $featuresText = !empty($t['features_json']) ? implode("\n", json_decode($t['features_json'], true) ?: []) : '';
    ?>
      <div id="tab-lang-<?= $code ?>" class="lang-tab-pane" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
        <div class="form-group-row">
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">
              Judul Layanan (<?= strtoupper($code) ?>) <?= $code === 'id' ? '*' : '' ?>
            </label>
            <input type="text" name="trans[<?= $code ?>][title]" value="<?= Security::e($t['title'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
          </div>
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Clean URL Slug</label>
            <input type="text" name="trans[<?= $code ?>][slug]" value="<?= Security::e($t['slug'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
          </div>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Ringkasan Singkat</label>
          <textarea name="trans[<?= $code ?>][short_description]" rows="2" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;"><?= Security::e($t['short_description'] ?? '') ?></textarea>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Deskripsi Lengkap</label>
          <textarea name="trans[<?= $code ?>][content_html]" rows="6" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem; font-family: inherit;"><?= Security::e($t['content_html'] ?? '') ?></textarea>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Fasilitas Termasuk (1 per Baris)</label>
          <textarea name="trans[<?= $code ?>][features]" rows="3" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;"><?= Security::e($featuresText) ?></textarea>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</form>

<script>
function switchLangTab(langCode, btn) {
  document.querySelectorAll('.lang-tab-pane').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  const target = document.getElementById('tab-lang-' + langCode);
  if (target) target.style.display = 'block';
  btn.classList.add('btn-primary');
  btn.classList.remove('btn-secondary');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>