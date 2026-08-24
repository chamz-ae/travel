<?php
declare(strict_types=1);

// Load Configurations & Core Modules
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/router.php';

// Inisialisasi & Dispatch Request
$router = new Router();
$router->dispatch();