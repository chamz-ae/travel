<?php
declare(strict_types=1);

require_once CONFIG_PATH . '/constants.php';

class MediaUploader {
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif'
    ];
    private const MAX_FILE_SIZE = 6 * 1024 * 1024; // 6 MB

    public static function upload(array $file): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Parameter berkas tidak valid.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'Tidak ada berkas yang diunggah.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'Ukuran berkas melebihi batas maksimal (6MB).'];
            default:
                return ['success' => false, 'error' => 'Terjadi kesalahan sistem saat mengunggah.'];
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'Ukuran berkas maksimal 6MB.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            return ['success' => false, 'error' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.'];
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $filename = sprintf('tiranda_%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(6)), $extension);
        
        if (!is_dir(UPLOADS_PATH)) {
            mkdir(UPLOADS_PATH, 0755, true);
        }

        $targetPath = UPLOADS_PATH . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Gagal menyimpan berkas ke folder uploads.'];
        }

        return [
            'success'   => true,
            'file_path' => BASE_URL . '/uploads/' . $filename,
            'file_name' => $filename
        ];
    }
}