<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();
$errors = [];
$successData = null;

// Ambil opsi layanan untuk dropdown
// Ambil opsi layanan untuk dropdown dengan fallback terjemahan
$stmtServices = $db->prepare("
    SELECT s.id, s.identifier, 
           COALESCE(st.title, st_def.title, s.identifier) AS title 
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = ?
    LEFT JOIN service_translations st_def ON s.id = st_def.service_id AND st_def.language_code = 'id'
    WHERE s.is_active = 1
    ORDER BY s.display_order ASC
");
$stmtServices->bind_param('s', $locale);
$stmtServices->execute();
$serviceOptions = $stmtServices->get_result()->fetch_all(MYSQLI_ASSOC);

// Pre-fill parameter jika datang dari tombol halaman detail
$preselectedService = Security::sanitizeString($_GET['service'] ?? '');

// PROSES POST RESERVASI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verifikasi CSRF Token
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Validasi keamanan gagal. Silakan muat ulang halaman.';
    }

    // 2. Sanitasi & Validasi Input
    $serviceIdentifier = Security::sanitizeString($_POST['service_identifier'] ?? '');
    $customerName      = Security::sanitizeString($_POST['customer_name'] ?? '');
    $customerEmail     = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $customerPhone     = Security::sanitizeString($_POST['customer_phone'] ?? '');
    $pickupDate        = Security::sanitizeString($_POST['pickup_date'] ?? '');
    $pickupTime        = Security::sanitizeString($_POST['pickup_time'] ?? '');
    $pickupLocation    = Security::sanitizeString($_POST['pickup_location'] ?? '');
    $dropoffLocation   = Security::sanitizeString($_POST['dropoff_location'] ?? '');
    $durationDays      = max(1, (int)($_POST['duration_days'] ?? 1));
    $passengers        = max(1, (int)($_POST['passengers'] ?? 1));
    $specialRequests   = Security::sanitizeString($_POST['special_requests'] ?? '');

    if (empty($customerName))   $errors[] = 'Nama lengkap wajib diisi.';
    if (!$customerEmail)        $errors[] = 'Format alamat email tidak valid.';
    if (empty($customerPhone))  $errors[] = 'Nomor WhatsApp/Telepon wajib diisi.';
    if (empty($pickupDate))     $errors[] = 'Tanggal penjemputan wajib dipilih.';
    if (empty($pickupTime))     $errors[] = 'Waktu penjemputan wajib dipilih.';
    if (empty($pickupLocation)) $errors[] = 'Lokasi penjemputan wajib diisi.';

    // Cari Service ID berdasarkan identifier
    $serviceId = null;
    if (!empty($serviceIdentifier)) {
        $stmtSvc = $db->prepare("SELECT id, identifier FROM services WHERE identifier = ? LIMIT 1");
        $stmtSvc->bind_param('s', $serviceIdentifier);
        $stmtSvc->execute();
        $svcRow = $stmtSvc->get_result()->fetch_assoc();
        if ($svcRow) {
            $serviceId = (int)$svcRow['id'];
        }
    }

    // 3. Simpan ke Database jika valid
    if (empty($errors)) {
        // Generate Unique Booking Code (Contoh: TRND-2026-AB12)
        $bookingCode = 'TRND-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        $insertStmt = $db->prepare("
            INSERT INTO reservations (
                booking_code, service_id, customer_name, customer_email, customer_phone,
                pickup_date, pickup_time, pickup_location, dropoff_location, duration_days,
                passengers, special_requests, language_used, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insertStmt->bind_param(
            'sissssssiissss',
            $bookingCode,
            $serviceId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $pickupDate,
            $pickupTime,
            $pickupLocation,
            $dropoffLocation,
            $durationDays,
            $passengers,
            $specialRequests,
            $locale,
            $ipAddress
        );

        if ($insertStmt->execute()) {
            $successData = [
                'booking_code'    => $bookingCode,
                'customer_name'   => $customerName,
                'pickup_date'     => $pickupDate,
                'service_name'    => $serviceIdentifier,
                'customer_phone'  => $customerPhone
            ];
        } else {
            $errors[] = 'Terjadi kesalahan sistem saat menyimpan reservasi. Silakan coba lagi.';
        }
    }
}

$pageTitle = 'Formulir Reservasi Perjalanan';
$pageDescription = 'Pesan layanan rental mobil dan paket tour Tiranda Jogja secara cepat dan transparan.';

require INCLUDES_PATH . '/views/header.php';
?>

<div class="container" style="max-width: 800px; padding: 4rem 1.5rem 6rem;">
  <?php if ($successData): ?>
    <!-- TAMPILAN SUKSES & HANDOFF WHATSAPP -->
    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 3rem 2rem; text-align: center; box-shadow: var(--shadow-md);">
      <div style="width: 60px; height: 60px; background: #dcfce7; color: var(--color-success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1.5rem;">✓</div>
      <h1 style="font-size: 1.75rem; margin-bottom: 0.75rem;">Reservasi Berhasil Dikirim!</h1>
      <p style="color: var(--color-text-muted); margin-bottom: 1.5rem;">
        Terima kasih, <strong><?= Security::e($successData['customer_name']) ?></strong>. Kode booking Anda adalah:
      </p>
      
      <div style="display: inline-block; background: var(--color-bg-light); border: 2px dashed var(--color-accent); padding: 0.75rem 1.75rem; font-size: 1.25rem; font-weight: 800; color: var(--color-primary); margin-bottom: 2rem; letter-spacing: 0.05em;">
        <?= Security::e($successData['booking_code']) ?>
      </div>

      <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 2rem;">
        Tim operasional kami akan segera memverifikasi ketersediaan unit dan menghubungi Anda melalui WhatsApp.
      </p>

      <?php
        $waMessage = urlencode("Halo Tiranda Jogja, saya telah melakukan reservasi dengan Kode Booking: " . $successData['booking_code'] . " atas nama " . $successData['customer_name'] . ".");
      ?>
      <a href="https://wa.me/6281200000000?text=<?= $waMessage ?>" class="btn btn-primary" target="_blank" rel="noopener">
        Konfirmasi Langsung ke WhatsApp
      </a>
    </div>

  <?php else: ?>
    <!-- FORMULIR INPUT RESERVASI -->
    <div style="margin-bottom: 2.5rem; text-align: center;">
      <span class="tagline">Reservasi Cepat & Aman</span>
      <h1>Formulir Pemesanan Perjalanan</h1>
      <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
        Isi detail perjalanan Anda di bawah ini. Tim kami akan merespons konfirmasi dalam hitungan menit.
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div style="background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 1rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 2rem; font-size: 0.9rem;">
        <ul style="margin-left: 1.25rem;">
          <?php foreach ($errors as $error): ?>
            <li><?= Security::e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2.5rem; box-shadow: var(--shadow-sm);">
      <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Nama Lengkap *</label>
          <input type="text" name="customer_name" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['customer_name'] ?? '') ?>">
        </div>

        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">WhatsApp / Telepon *</label>
          <input type="tel" name="customer_phone" required placeholder="08xxxxxxxxxx" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['customer_phone'] ?? '') ?>">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Email *</label>
          <input type="email" name="customer_email" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['customer_email'] ?? '') ?>">
        </div>

        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Pilihan Layanan</label>
          <select name="service_identifier" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: #ffffff;">
            <option value="">-- Pilih Layanan --</option>
            <?php foreach ($serviceOptions as $opt): ?>
              <option value="<?= Security::e($opt['identifier']) ?>" <?= ($preselectedService === $opt['identifier'] || ($_POST['service_identifier'] ?? '') === $opt['identifier']) ? 'selected' : '' ?>>
                <?= Security::e($opt['title']) ?>
              </option>
            <?php endforeach; ?>    
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Tanggal Mulai *</label>
          <input type="date" name="pickup_date" required min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['pickup_date'] ?? '') ?>">
        </div>

        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Waktu Penjemputan *</label>
          <input type="time" name="pickup_time" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['pickup_time'] ?? '') ?>">
        </div>

        <div>
          <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Durasi (Hari)</label>
          <input type="number" name="duration_days" min="1" max="30" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e((string)($_POST['duration_days'] ?? 1)) ?>">
        </div>
      </div>

      <div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Lokasi Penjemputan (Hotel / Bandara YIA / Stasiun) *</label>
        <textarea name="pickup_location" required rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);"><?= Security::e($_POST['pickup_location'] ?? '') ?></textarea>
      </div>

      <div style="margin-bottom: 2rem;">
        <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">Catatan Khusus / Destinasi yang Ingin Dikunjungi</label>
        <textarea name="special_requests" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);"><?= Security::e($_POST['special_requests'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1rem; padding: 1rem;">
        Kirim Permintaan Reservasi
      </button>
    </form>
  <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/views/footer.php'; ?>