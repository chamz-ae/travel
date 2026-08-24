<?php
declare(strict_types=1);

require_once CONFIG_PATH . '/constants.php';
require_once INCLUDES_PATH . '/i18n.php';

class Router {
    private string $locale;
    private string $path;
    private array $segments;

    public function __construct() {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Bersihkan BASE_PATH (subfolder XAMPP) dari URI
        if (BASE_PATH !== '' && str_starts_with($uri, BASE_PATH)) {
            $uri = substr($uri, strlen(BASE_PATH));
        }

        $uri = trim($uri, '/');
        $this->segments = $uri === '' ? [] : explode('/', $uri);
        $this->resolveLocale();
    }

    private function resolveLocale(): void {
        if (!empty($this->segments) && in_array($this->segments[0], SUPPORTED_LOCALES, true)) {
            $this->locale = array_shift($this->segments);
        } else {
            $this->locale = DEFAULT_LOCALE;
        }
        I18n::setLocale($this->locale);
        $this->path = implode('/', $this->segments);
    }

    public function dispatch(): void {
        $path = $this->path;

        // Redirect jika mengakses route admin via public router
        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        }

        if ($path === '' || $path === 'home') {
            require PAGES_PATH . '/home.php';
            return;
        }

        if ($path === 'about') {
            require PAGES_PATH . '/about.php';
            return;
        }

        if ($path === 'services') {
            require PAGES_PATH . '/services.php';
            return;
        }

        // Service Detail (/services/{slug})
        if (preg_match('#^services/([a-z0-9-]+)$#', $path, $matches)) {
            $serviceSlug = $matches[1];
            require PAGES_PATH . '/service-detail.php';
            return;
        }

        if ($path === 'reservation') {
            require PAGES_PATH . '/reservation.php';
            return;
        }

        if ($path === 'contact') {
            require PAGES_PATH . '/contact.php';
            return;
        }

        if ($path === 'fleet') {
    require PAGES_PATH . '/fleet.php';
    return;
}
        if ($path === 'sitemap.xml') {
    require PAGES_PATH . '/sitemap.php';
    return;
}
if ($path === 'articles') {
            require PAGES_PATH . '/articles.php';
            return;
        }

        // Article Detail (/articles/{slug})
        if (preg_match('#^articles/([a-z0-9-]+)$#', $path, $matches)) {
            $articleSlug = $matches[1];
            require PAGES_PATH . '/article-detail.php';
            return;
        }
        if ($path === 'packages') {
            require PAGES_PATH . '/packages.php';
            return;
        }

        // Package Detail (/packages/{slug})
        if (preg_match('#^packages/([a-z0-9-]+)$#', $path, $matches)) {
            $packageSlug = $matches[1];
            require PAGES_PATH . '/package-detail.php';
            return;
        }

        if ($path === 'gallery') {
            require PAGES_PATH . '/gallery.php';
            return;
        }

        // 404 Fallback
        http_response_code(404);
        require PAGES_PATH . '/404.php';
    }
}
