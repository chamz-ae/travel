<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $packageId > 0;
$errors = [];
$success = '';

$languages = $db->query("SELECT code, name, native_name FROM languages WHERE is_active = 1 ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$package = [
    'identifier'       => '',
    'price_per_person' => 450000,
    'duration_text'    => '1 Hari / Full Day',
    'min_pax'          => 2,
    'display_order'    => 0,
    'is_active'        => 1
];
$translations = [];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM packages WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $packageId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) {
        header('Location: ' . BASE_URL . '/admin/packages.php');
        exit;
    }
    $package = $existing;

    $stmtTrans = $db->prepare("SELECT * FROM package_translations WHERE package_id = ?");
    $stmtTrans->bind_param('i', $packageId);
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
    $pricePerPerson = (float)($_POST['price_per_person'] ?? 0);
    $durationText   = Security::sanitizeString($_POST['duration_text'] ?? '1 Hari');
    $minPax         = (int)($_POST['min_pax'] ?? 2);
    $displayOrder   = (int)($_POST['display_order'] ?? 0);
    $isActive       = isset($_POST['is_active']) ? 1 : 0;

    $idTitle = Security::sanitizeString($_POST['trans']['id']['title'] ?? '');
    if (empty($identifier) || empty($idTitle)) {
        $errors[] = 'Identifier dan Judul Bahasa Indonesia wajib diisi.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE packages SET identifier = ?, price_per_person = ?, duration_text = ?, min_pax = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param('sdsiiii', $identifier, $pricePerPerson, $durationText, $minPax, $displayOrder, $isActive, $packageId);
            $stmt->execute();
        } else {
            $dummyImage = '';
            $stmt = $db->prepare("INSERT INTO packages (identifier, featured_image, price_per_person, duration_text, min_pax, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssdsiii', $identifier, $dummyImage, $pricePerPerson, $durationText, $minPax, $displayOrder, $isActive);
            $stmt->execute();
            $packageId = (int)$db->insert_id;
            $isEdit = true;
        }

        foreach ($languages as $lang) {
            $code = $lang['code'];
            $title       = Security::sanitizeString($_POST['trans'][$code]['title'] ?? '');
            $slug        = Security::sanitizeString($_POST['trans'][$code]['slug'] ?? '');
            $shortDesc   = Security::sanitizeString($_POST['trans'][$code]['short_description'] ?? '');
            $itinRaw     = trim((string)($_POST['trans'][$code]['itinerary'] ?? ''));
            $incRaw      = trim((string)($_POST['trans'][$code]['includes'] ?? ''));
            $metaTitle   = Security::sanitizeString($_POST['trans'][$code]['meta_title'] ?? '');
            $metaDesc    = Security::sanitizeString($_POST['trans'][$code]['meta_description'] ?? '');

            if ($code !== 'id' && empty($title)) continue;

            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            }

            $itinArray = array_values(array_filter(array_map('trim', explode("\n", $itinRaw))));
            $itinJson  = !empty($itinArray) ? json_encode($itinArray, JSON_UNESCAPED_UNICODE) : null;

            $incArray = array_values(array_filter(array_map('trim', explode("\n", $incRaw))));
            $incJson  = !empty($incArray) ? json_encode($incArray, JSON_UNESCAPED_UNICODE) : null;

            $stmtTransSave = $db->prepare("
                INSERT INTO package_translations (package_id, language_code, slug, title, short_description, itinerary_json, includes_json, meta_title, meta_description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    slug = VALUES(slug),
                    title = VALUES(title),
                    short_description = VALUES(short_description),
                    itinerary_json = VALUES(itinerary_json),
                    includes_json = VALUES(includes_json),
                    meta_title = VALUES(meta_title),
                    meta_description = VALUES(meta_description)
            ");
            $stmtTransSave->bind_param('issssssss', $packageId, $code, $slug, $title, $shortDesc, $itinJson, $incJson, $metaTitle, $metaDesc);
            $stmtTransSave->execute();
        }

        $success = 'Paket wisata dan itinerary berhasil disimpan.';
    }
}

$activePage = 'packages';
$pageTitle = $isEdit ? 'Edit Paket Tour' : 'Tambah Paket Tour Baru';
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
  <a href="<?= BASE_URL ?>/admin/packages.php" style="color: var(--admin-text-muted); font-size: 0.875rem;">&larr; Kembali ke Daftar Paket</a>
</div>

<?php if (!empty($success)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($success) ?>
  </div>
<?php endif; ?>

<form method="POST" action="" class="admin-form-grid">
  <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

  <!-- Kolom Kiri: Pengaturan Tarif & Durasi -->
  <div class="admin-card">
    <h3 style="font-size: 1rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
      Tarif & Ketentuan
    </h3>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Identifier (Code) *</label>
      <input type="text" name="identifier" required placeholder="jogja-heritage-1d" value="<?= Security::e($package['identifier']) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Harga / Orang (IDR)</label>
      <input type="number" name="price_per_person" value="<?= (float)$package['price_per_person'] ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Durasi Paket</label>
      <input type="text" name="duration_text" placeholder="1 Hari / Full Day" value="<?= Security::e($package['duration_text']) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1.25rem;">
      <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Minimal Peserta (Pax)</label>
      <input type="number" name="min_pax" value="<?= (int)$package['min_pax'] ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
    </div>

    <div style="margin-bottom: 1.5rem;">
      <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" <?= (int)$package['is_active'] === 1 ? 'checked' : '' ?>>
        Publikasikan Paket Tour
      </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
      Simpan Paket Tour
    </button>
  </div>

  <!-- Kolom Kanan: Narasi Destinasi & Itinerary Multibahasa -->
  <div class="admin-card">
    <div class="tab-headers-scroll">
      <?php foreach ($languages as $index => $lang): ?>
        <button type="button" class="tab-btn btn <?= $index === 0 ? 'btn-primary' : 'btn-secondary' ?>" onclick="switchPkgTab('<?= $lang['code'] ?>', this)" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
          <?= Security::e($lang['name']) ?> (<?= strtoupper($lang['code']) ?>)
        </button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($languages as $index => $lang): 
      $code = $lang['code'];
      $t = $translations[$code] ?? [];
      $itinText = !empty($t['itinerary_json']) ? implode("\n", json_decode($t['itinerary_json'], true) ?: []) : '';
      $incText = !empty($t['includes_json']) ? implode("\n", json_decode($t['includes_json'], true) ?: []) : '';
    ?>
      <div id="tab-pkg-<?= $code ?>" class="pkg-tab-pane" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
        <div class="form-group-row">
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">
              Nama Paket (<?= strtoupper($code) ?>) <?= $code === 'id' ? '*' : '' ?>
            </label>
            <input type="text" name="trans[<?= $code ?>][title]" value="<?= Security::e($t['title'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
          </div>
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Clean URL Slug</label>
            <input type="text" name="trans[<?= $code ?>][slug]" value="<?= Security::e($t['slug'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
          </div>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Deskripsi Destinasi & Pengalaman Wisata</label>
          <textarea name="trans[<?= $code ?>][short_description]" rows="3" placeholder="Jelaskan daya tarik destinasi yang akan dikunjungi..." style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;"><?= Security::e($t['short_description'] ?? '') ?></textarea>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Rencana Perjalanan / Itinerary (1 per Baris)</label>
          <textarea name="trans[<?= $code ?>][itinerary]" rows="6" placeholder="08:00 - Penjemputan di Hotel&#10;09:30 - Eksplorasi Candi Prambanan&#10;12:00 - Makan Siang Kuliner Khas&#10;14:00 - Tour Keraton Jogja&#10;17:30 - Drop Off Hotel" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;"><?= Security::e($itinText) ?></textarea>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Fasilitas Termasuk (1 per Baris)</label>
          <textarea name="trans[<?= $code ?>][includes]" rows="3" placeholder="Mobil Ber-AC & Driver Profesional&#10;BBM & Parkir Seluruh Lokasi&#10;Air Mineral" style="width: 100%; padding: 0.7rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;"><?= Security::e($incText) ?></textarea>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</form>

<script>
function switchPkgTab(langCode, btn) {
  document.querySelectorAll('.pkg-tab-pane').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  const target = document.getElementById('tab-pkg-' + langCode);
  if (target) target.style.display = 'block';
  btn.classList.add('btn-primary');
  btn.classList.remove('btn-secondary');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>