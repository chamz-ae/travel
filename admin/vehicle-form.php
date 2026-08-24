<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once __DIR__ . '/includes/uploader.php';

Auth::requireAuth();
$db = Database::getConnection();

$vehicleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $vehicleId > 0;
$errors = [];
$success = '';

$vehicle = [
    'identifier'          => '',
    'name'                => '',
    'category'            => 'mpv',
    'capacity_passengers' => 5,
    'capacity_luggage'    => 2,
    'transmission'        => 'both',
    'featured_image'      => '',
    'daily_rate'          => 450000,
    'with_driver_rate'    => 650000,
    'is_active'           => 1,
    'display_order'       => 0
];
$translations = [];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $vehicleId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) {
        header('Location: ' . BASE_URL . '/admin/vehicles.php');
        exit;
    }
    $vehicle = $existing;

    $stmtTrans = $db->prepare("SELECT * FROM vehicle_translations WHERE vehicle_id = ?");
    $stmtTrans->bind_param('i', $vehicleId);
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
    $name           = Security::sanitizeString($_POST['name'] ?? '');
    $category       = Security::sanitizeString($_POST['category'] ?? 'mpv');
    $passengers     = (int)($_POST['capacity_passengers'] ?? 5);
    $luggage        = (int)($_POST['capacity_luggage'] ?? 2);
    $transmission   = Security::sanitizeString($_POST['transmission'] ?? 'both');
    $featuredImage  = Security::sanitizeString($_POST['featured_image_url'] ?? $vehicle['featured_image']);
    $dailyRate      = (float)($_POST['daily_rate'] ?? 0);
    $withDriverRate = (float)($_POST['with_driver_rate'] ?? 0);
    $displayOrder   = (int)($_POST['display_order'] ?? 0);
    $isActive       = isset($_POST['is_active']) ? 1 : 0;

    // Handle Upload File Gambar
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = MediaUploader::upload($_FILES['image_file']);
        if ($uploadResult['success']) {
            $featuredImage = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    if (empty($name) || empty($identifier)) {
        $errors[] = 'Nama kendaraan dan identifier wajib diisi.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("
                UPDATE vehicles SET identifier = ?, name = ?, category = ?, capacity_passengers = ?, capacity_luggage = ?, transmission = ?, featured_image = ?, daily_rate = ?, with_driver_rate = ?, is_active = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->bind_param('sssiissddiii', $identifier, $name, $category, $passengers, $luggage, $transmission, $featuredImage, $dailyRate, $withDriverRate, $isActive, $displayOrder, $vehicleId);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("
                INSERT INTO vehicles (identifier, name, category, capacity_passengers, capacity_luggage, transmission, featured_image, daily_rate, with_driver_rate, is_active, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssiissddii', $identifier, $name, $category, $passengers, $luggage, $transmission, $featuredImage, $dailyRate, $withDriverRate, $isActive, $displayOrder);
            $stmt->execute();
            $vehicleId = (int)$db->insert_id;
            $isEdit = true;
        }

        $vehicle['featured_image'] = $featuredImage;

        foreach (['id', 'en'] as $code) {
            $desc = Security::sanitizeString($_POST['trans'][$code]['description'] ?? '');
            $stmtTransSave = $db->prepare("
                INSERT INTO vehicle_translations (vehicle_id, language_code, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE description = VALUES(description)
            ");
            $stmtTransSave->bind_param('iss', $vehicleId, $code, $desc);
            $stmtTransSave->execute();
        }

        $success = 'Data armada berhasil disimpan.';
    }
}

$activePage = 'vehicles';
$pageTitle = $isEdit ? 'Edit Armada' : 'Tambah Armada Baru';
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
  <a href="<?= BASE_URL ?>/admin/vehicles.php" style="color: var(--admin-text-muted); font-size: 0.875rem;">&larr; Kembali ke Daftar Armada</a>
</div>

<?php if (!empty($success)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($success) ?>
  </div>
<?php endif; ?>

<div class="admin-card" style="max-width: 880px;">
  <form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

    <div class="form-group-row">
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Nama Mobil *</label>
        <input type="text" name="name" required value="<?= Security::e($vehicle['name']) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;">
      </div>
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Identifier *</label>
        <input type="text" name="identifier" required value="<?= Security::e($vehicle['identifier']) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;">
      </div>
    </div>

    <!-- DUAL IMAGE UPLOAD WIDGET -->
    <div class="image-source-box">
      <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--admin-sidebar); margin-bottom: 0.4rem;">
        Foto Armada Kendaraan
      </label>
      
      <div style="margin-bottom: 0.75rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 1: Upload File Gambar</span>
        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" style="font-size: 0.8rem; width: 100%;">
      </div>

      <div style="margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">Opsi 2: Atau Masukkan URL Gambar</span>
        <input type="text" name="featured_image_url" placeholder="https://..." value="<?= Security::e($vehicle['featured_image']) ?>" style="width: 100%; padding: 0.55rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.8rem;">
      </div>

      <?php if (!empty($vehicle['featured_image'])): ?>
        <div class="image-preview-wrapper" style="height: 180px;">
          <img src="<?= Security::e($vehicle['featured_image']) ?>" alt="Preview">
        </div>
      <?php endif; ?>
    </div>

    <div class="form-group-row">
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Kategori</label>
        <select name="category" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #ffffff;">
          <option value="mpv" <?= $vehicle['category'] === 'mpv' ? 'selected' : '' ?>>MPV Family</option>
          <option value="suv" <?= $vehicle['category'] === 'suv' ? 'selected' : '' ?>>SUV Adventure</option>
          <option value="van" <?= $vehicle['category'] === 'van' ? 'selected' : '' ?>>Van / Hiace</option>
          <option value="luxury" <?= $vehicle['category'] === 'luxury' ? 'selected' : '' ?>>Luxury Premium</option>
        </select>
      </div>
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Kapasitas Kursi</label>
        <input type="number" name="capacity_passengers" value="<?= (int)$vehicle['capacity_passengers'] ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;">
      </div>
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Kapasitas Bagasi (Koper)</label>
        <input type="number" name="capacity_luggage" value="<?= (int)$vehicle['capacity_luggage'] ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;">
      </div>
    </div>

    <div class="form-group-row">
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Transmisi</label>
        <select name="transmission" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #ffffff;">
          <option value="both" <?= $vehicle['transmission'] === 'both' ? 'selected' : '' ?>>Manual & Matic</option>
          <option value="manual" <?= $vehicle['transmission'] === 'manual' ? 'selected' : '' ?>>Manual</option>
          <option value="automatic" <?= $vehicle['transmission'] === 'automatic' ? 'selected' : '' ?>>Automatic</option>
        </select>
      </div>
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Tarif Lepas Kunci (IDR)</label>
        <input type="number" name="daily_rate" value="<?= (float)$vehicle['daily_rate'] ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;">
      </div>
      <div>
        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Tarif + Driver & BBM (IDR)</label>
        <input type="number" name="with_driver_rate" value="<?= (float)$vehicle['with_driver_rate'] ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;">
      </div>
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Deskripsi Singkat</label>
      <textarea name="trans[id][description]" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px;"><?= Security::e($translations['id']['description'] ?? '') ?></textarea>
    </div>

    <div style="margin-bottom: 1.5rem;">
      <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" <?= (int)$vehicle['is_active'] === 1 ? 'checked' : '' ?>>
        Tampilkan Unit di Halaman Publik
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem;">
      Simpan Unit Armada
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>