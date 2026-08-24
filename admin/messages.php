<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();
$messageNotice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgId = (int)($_POST['message_id'] ?? 0);
        $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->bind_param('i', $msgId);
        $stmt->execute();
        $messageNotice = 'Status pesan diperbarui.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgId = (int)($_POST['message_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param('i', $msgId);
        $stmt->execute();
        $messageNotice = 'Pesan berhasil dihapus.';
    }
}

$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$activePage = 'messages';
$pageTitle = 'Pesan Masuk';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($messageNotice)): ?>
  <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.875rem;">
    <?= Security::e($messageNotice) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <h2 style="font-size: 1.125rem; margin-bottom: 1.25rem;">Kotak Masuk Form Kontak</h2>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 130px;">Waktu</th>
          <th style="width: 180px;">Pengirim</th>
          <th style="width: 180px;">Subjek</th>
          <th>Isi Pesan</th>
          <th style="width: 120px; text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($messages)): ?>
          <?php foreach ($messages as $msg): ?>
            <tr style="<?= !(int)$msg['is_read'] ? 'background-color: #f8fafc; font-weight: 500;' : '' ?>">
              <td style="font-size: 0.8rem; color: var(--color-text-muted);">
                <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
              </td>
              <td>
                <strong><?= Security::e($msg['name']) ?></strong><br>
                <small><a href="mailto:<?= Security::e($msg['email']) ?>" style="color: var(--color-accent);"><?= Security::e($msg['email']) ?></a></small><br>
                <?php if (!empty($msg['phone'])): ?>
                  <small><a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $msg['phone']) ?>" target="_blank" style="color: var(--color-success);">WA: <?= Security::e($msg['phone']) ?></a></small>
                <?php endif; ?>
              </td>
              <td>
                <?= !(int)$msg['is_read'] ? '<span style="color: #b45309; font-size: 0.75rem; font-weight: bold;">[BARU]</span> ' : '' ?>
                <?= Security::e($msg['subject']) ?>
              </td>
              <td style="font-size: 0.85rem; line-height: 1.5;">
                <?= nl2br(Security::e($msg['message'])) ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.35rem;">
                  <?php if (!(int)$msg['is_read']): ?>
                    <form method="POST" action="" style="display: inline;">
                      <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                      <input type="hidden" name="action" value="mark_read">
                      <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                      <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.5rem; font-size: 0.75rem;">
                        ✓
                      </button>
                    </form>
                  <?php endif; ?>

                  <form method="POST" action="" onsubmit="return confirm('Hapus pesan ini?');" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; color: #dc2626; border-color: #fca5a5;">
                      Hapus
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align: center; color: var(--color-text-muted);">Belum ada pesan masuk.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>