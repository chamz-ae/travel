<?php
declare(strict_types=1);

define('APP_ENV', 'development');
define('APP_NAME', 'Tiranda Jogja');

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('LANGUAGES_PATH', ROOT_PATH . '/languages');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$appRoot = str_replace('\\', '/', realpath(ROOT_PATH) ?: '');

$calculatedBasePath = '';
if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
    $calculatedBasePath = substr($appRoot, strlen($docRoot));
}
$calculatedBasePath = '/' . trim($calculatedBasePath, '/');
if ($calculatedBasePath === '/') {
    $calculatedBasePath = '';
}

define('BASE_PATH', $calculatedBasePath);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', rtrim($protocol . $host . BASE_PATH, '/'));

define('DEFAULT_LOCALE', 'id');
define('SUPPORTED_LOCALES', ['id', 'en', 'ms', 'zh', 'ja', 'de', 'fr', 'nl']);