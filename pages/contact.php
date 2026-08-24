<?php
declare(strict_types=1);

$db = Database::getConnection();
$locale = I18n::getLocale();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $name    = Security::sanitizeString($_POST['name'] ?? '');
        $email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone   = Security::sanitizeString($_POST['phone'] ?? '');
        $subject = Security::sanitizeString($_POST['subject'] ?? '');
        $message = Security::sanitizeString($_POST['message'] ?? '');

        if (empty($name))    $errors[] = 'Nama lengkap wajib diisi.';
        if (!$email)         $errors[] = 'Alamat email tidak valid.';
        if (empty($subject)) $errors[] = 'Subjek pesan wajib diisi.';
        if (empty($message)) $errors[] = 'Isi pesan tidak boleh kosong.';

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
            
            if ($stmt->execute()) {
                $successMessage = 'Pesan Anda telah berhasil dikirimkan. Tim kami akan segera menghubungi Anda.';
            } else {
                $errors[] = 'Gagal mengirimkan pesan ke server. Silakan coba kembali.';
            }
        }
    }
}

$pageTitle = 'Kontak Kami — Tiranda Jogja';
$pageDescription = 'Hubungi operasional Tiranda Jogja untuk konsultasi rute, informasi armada, dan kerja sama pariwisata.';

require_once INCLUDES_PATH . '/views/header.php';
?>

<div class="container" style="padding: 3.5rem 1.5rem 5rem;">
  <div class="section-header">
    <span class="tagline">Layanan Bantuan</span>
    <h1>Hubungi Tiranda Jogja</h1>
    <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
      Ada pertanyaan seputar paket wisata atau rental mobil? Hubungi kami langsung melalui form di bawah atau via WhatsApp.
    </p>
  </div>

  <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 3.5rem; max-width: 1000px; margin: 0 auto; align-items: start;">
    <!-- Form Kontak -->
    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-sm);">
      <h2 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Kirim Pesan</h2>

      <?php if (!empty($successMessage)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
          <?= Security::e($successMessage) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
          <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
              <li><?= Security::e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

        <div style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Nama Lengkap *</label>
          <input type="text" name="name" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['name'] ?? '') ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Email *</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['email'] ?? '') ?>">
          </div>
          <div>
            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Nomor WhatsApp</label>
            <input type="tel" name="phone" placeholder="08xxxxxxxxxx" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['phone'] ?? '') ?>">
          </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Subjek Pesan *</label>
          <input type="text" name="subject" required placeholder="Contoh: Tanya Paket Tour 3D2N" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);" value="<?= Security::e($_POST['subject'] ?? '') ?>">
        </div>

        <div style="margin-bottom: 1.5rem;">
          <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem;">Pesan Anda *</label>
          <textarea name="message" rows="4" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-family: inherit;"><?= Security::e($_POST['message'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">
          Kirim Pesan
        </button>
      </form>
    </div>

    <!-- Informasi Kontak Langsung -->
    <div>
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.125rem; margin-bottom: 1rem;">Kantor Operasional</h3>
        <p style="color: var(--color-text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.25rem;">
          Daerah Istimewa Yogyakarta, Indonesia<br>
          Operasional Armada: 24 Jam Setiap Hari<br>
          Layanan CS: 07:00 - 22:00 WIB
        </p>

        <h3 style="font-size: 1.125rem; margin-bottom: 1rem;">Kontak Langsung</h3>
        <p style="color: var(--color-text-muted); font-size: 0.9rem; line-height: 1.8; margin-bottom: 1.5rem;">
          <strong>Email:</strong> info@tirandajogja.com<br>
          <strong>WhatsApp:</strong> +62 812-XXXX-XXXX
        </p>

        <a href="https://wa.me/6281200000000?text=Halo%20Tiranda%20Jogja,%20saya%20ingin%20berkonsultasi" target="_blank" rel="noopener" class="btn btn-secondary" style="width: 100%;">
          Chat WhatsApp Langsung
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/views/footer.php'; ?>