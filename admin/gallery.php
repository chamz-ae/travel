<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once __DIR__ . '/includes/uploader.php';

Auth::requireAuth();
$db = Database::getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $title    = Security::sanitizeString($_POST['title'] ?? '');
        $category = Security::sanitizeString($_POST['category'] ?? 'destinasi');
        $imageUrl = Security::sanitizeString($_POST['image_url'] ?? '');
        $order    = (int)($_POST['display_order'] ?? 0);

        // Handle Upload File Gambar
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = MediaUploader::upload($_FILES['image_file']);
            if ($uploadResult['success']) {
                $imageUrl = $uploadResult['file_path'];
            } else {
                $error = $uploadResult['error'];
            }
        }

        if (empty($error)) {
            if (!empty($title) && !empty($imageUrl)) {
                $stmt = $db->prepare("INSERT INTO gallery (title, category, image_url, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param('sssi', $title, $category, $imageUrl, $order);
                $stmt->execute();
                $message = 'Foto galeri berhasil ditambahkan.';
            } else {
                $error = 'Judul dan gambar (upload atau URL) wajib diisi.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $imgId = (int)($_POST['gallery_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->bind_param('i', $imgId);
        $stmt->execute();
        $message = 'Foto berhasil dihapus.';
    }
}

$gallery = $db->query("SELECT * FROM gallery ORDER BY display_order ASC, id DESC")->fetch_all(MYSQLI_ASSOC);

$activePage = 'gallery';
$pageTitle = 'Kelola Galeri';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($error) ?>
  </div>
<?php endif; ?>

<div class="admin-form-grid">
  <!-- Form Tambah Foto -->
  <form method="POST" action="" enctype="multipart/form-data" class="admin-card">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
    <input type="hidden" name="action" value="create">

    <h2 style="font-size: 1.05rem; margin-bottom: 1.25rem;">+ Tambah Foto Dokumentasi</h2>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Judul Foto *</label>
      <input type="text" name="title" required placeholder="Candi Prambanan Sunset" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Kategori</label>
      <select name="category" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem; background: #ffffff;">
        <option value="destinasi">Destinasi Wisata</option>
        <option value="armada">Armada Kendaraan</option>
        <option value="aktivitas">Aktivitas Tamu / Tour</option>
      </select>
    </div>

    <!-- DUAL IMAGE UPLOAD WIDGET -->
    <div class="image-source-box">
      <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--admin-sidebar); margin-bottom: 0.4rem;">
        Pilih Gambar
      </label>
      <div style="margin-bottom: 0.75rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 1: Upload File Gambar</span>
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" style="font-size: 0.8rem; width: 100%;">
      </div>
      <div>
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 2: Atau Masukkan URL Gambar</span>
        <input type="text" name="image_url" placeholder="https://..." style="width: 100%; padding: 0.55rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.8rem;">
      </div>
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Urutan Tampilan</label>
      <input type="number" name="display_order" value="0" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
      Upload ke Galeri
    </button>
  </form>

  <!-- Grid Preview Foto -->
  <div class="admin-card">
    <h2 style="font-size: 1.05rem; margin-bottom: 1.25rem;">Dokumentasi Terpublikasi</h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
      <?php if (!empty($gallery)): ?>
        <?php foreach ($gallery as $img): ?>
          <div style="border: 1px solid var(--admin-border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; background: #ffffff;">
            <img src="<?= Security::e($img['image_url']) ?>" alt="Gallery" style="width: 100%; height: 120px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1596405835974-98444a77e8ca?auto=format&fit=crop&w=300&q=80'">
            <div style="padding: 0.65rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
              <div>
                <strong style="font-size: 0.8rem; display: block; line-height: 1.3;"><?= Security::e($img['title']) ?></strong>
                <span style="font-size: 0.7rem; color: var(--admin-accent); text-transform: uppercase; font-weight: 600;"><?= Security::e($img['category']) ?></span>
              </div>
              <form method="POST" action="" onsubmit="return confirm('Hapus foto ini?');" style="margin-top: 0.5rem; text-align: right;">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="gallery_id" value="<?= $img['id'] ?>">
                <button type="submit" style="background: none; border: none; color: #dc2626; font-size: 0.75rem; cursor: pointer; padding: 0;">Hapus</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--admin-text-muted);">Belum ada foto di galeri.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>