<?php
declare(strict_types=1);

$user = Auth::user();
$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= isset($pageTitle) ? Security::e($pageTitle) . ' — ' : '' ?>Tiranda Jogja Backoffice</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    :root {
      --admin-bg: #f4f6f8;
      --admin-surface: #ffffff;
      --admin-sidebar: #0b132b;
      --admin-sidebar-hover: #1c2541;
      --admin-accent: #c5a880;
      --admin-accent-dark: #a9885e;
      --admin-border: #e2e8f0;
      --admin-text-main: #1e293b;
      --admin-text-muted: #64748b;
      --admin-radius: 12px;
      --admin-shadow: 0 4px 20px -2px rgba(11, 19, 43, 0.05);
    }

    body {
      background-color: var(--admin-bg);
      color: var(--admin-text-main);
      font-family: var(--font-sans);
    }

    .admin-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      min-height: 100vh;
    }

    /* Sidebar Executive */
    .admin-sidebar {
      background: var(--admin-sidebar);
      color: #ffffff;
      padding: 2rem 1.25rem;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .admin-brand {
      font-size: 1.3rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #ffffff;
      padding: 0 0.5rem;
      margin-bottom: 2rem;
    }

    .admin-brand span {
      color: var(--admin-accent);
    }

    .admin-nav {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      color: #94a3b8;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.875rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .admin-nav a:hover, .admin-nav a.active {
      background: var(--admin-sidebar-hover);
      color: #ffffff;
    }

    .admin-nav a.active {
      background: linear-gradient(90deg, var(--admin-sidebar-hover) 0%, rgba(197, 168, 128, 0.15) 100%);
      color: var(--admin-accent);
      font-weight: 600;
      border-left: 3px solid var(--admin-accent);
    }

    .admin-main {
      padding: 2.5rem;
      overflow-y: auto;
      min-width: 0;
    }

    /* Topbar Mobile */
    .admin-mobile-topbar {
      display: none;
      background: var(--admin-sidebar);
      color: #ffffff;
      padding: 1rem 1.25rem;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 990;
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(11, 19, 43, 0.6);
      backdrop-filter: blur(4px);
      z-index: 995;
    }

    /* Cards & Form Components */
    .admin-card {
      background: var(--admin-surface);
      border-radius: var(--admin-radius);
      border: 1px solid var(--admin-border);
      padding: 1.75rem;
      box-shadow: var(--admin-shadow);
      margin-bottom: 1.75rem;
    }

    .admin-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .admin-form-grid {
      display: grid;
      grid-template-columns: 360px 1fr;
      gap: 2rem;
      align-items: start;
    }

    .form-group-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }

    /* Dual Image Upload Widget Box */
    .image-source-box {
      background: #f8fafc;
      border: 1px dashed var(--admin-border);
      border-radius: 8px;
      padding: 1.25rem;
      margin-bottom: 1.25rem;
    }

    .image-preview-wrapper {
      width: 100%;
      height: 160px;
      border-radius: 6px;
      overflow: hidden;
      background: #e2e8f0;
      margin-top: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .image-preview-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Table Component */
    .table-responsive {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      border-radius: 8px;
      border: 1px solid var(--admin-border);
      background: #ffffff;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 680px;
    }

    .data-table th, .data-table td {
      padding: 0.95rem 1.25rem;
      text-align: left;
      font-size: 0.875rem;
      border-bottom: 1px solid var(--admin-border);
    }

    .data-table th {
      background: #f8fafc;
      font-weight: 600;
      color: var(--admin-sidebar);
      white-space: nowrap;
    }

    .tab-headers-scroll {
      display: flex;
      gap: 0.5rem;
      border-bottom: 1px solid var(--admin-border);
      padding-bottom: 0.75rem;
      margin-bottom: 1.5rem;
      overflow-x: auto;
    }

    .tab-headers-scroll button {
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* Responsive Breakdown */
    @media (max-width: 992px) {
      .admin-layout { grid-template-columns: 1fr; }
      .admin-mobile-topbar { display: flex; }
      .admin-sidebar {
        position: fixed;
        top: 0; bottom: 0; left: 0;
        width: 280px;
        transform: translateX(-100%);
        overflow-y: auto;
      }
      .admin-sidebar.is-open { transform: translateX(0); }
      .sidebar-overlay.is-open { display: block; }
      .admin-main { padding: 1.25rem 1rem; }
      .admin-form-grid { grid-template-columns: 1fr !important; }
      .admin-card-header { flex-direction: column; align-items: stretch; }
    }
  </style>
</head>
<body>

<div class="admin-mobile-topbar">
  <div class="admin-brand" style="margin: 0; font-size: 1.15rem;">
    TIRANDA<span>ADMIN</span>
  </div>
  <button id="adminSidebarToggle" style="background: var(--admin-sidebar-hover); border: 1px solid var(--admin-border); color: #ffffff; padding: 0.45rem 0.85rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
    ☰ Menu
  </button>
</div>

<div class="sidebar-overlay" id="adminSidebarOverlay"></div>

<div class="admin-layout">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand">
      TIRANDA<span>ADMIN</span>
    </div>

    <ul class="admin-nav">
      <li><a href="<?= BASE_URL ?>/admin/index.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a></li>
      <li><a href="<?= BASE_URL ?>/admin/reservations.php" class="<?= $activePage === 'reservations' ? 'active' : '' ?>">📅 Reservasi Masuk</a></li>
      <li><a href="<?= BASE_URL ?>/admin/services.php" class="<?= $activePage === 'services' ? 'active' : '' ?>">🚙 Kelola Layanan</a></li>
      <li><a href="<?= BASE_URL ?>/admin/packages.php" class="<?= $activePage === 'packages' ? 'active' : '' ?>">🧭 Paket Tour (Itinerary)</a></li>
      <li><a href="<?= BASE_URL ?>/admin/vehicles.php" class="<?= $activePage === 'vehicles' ? 'active' : '' ?>">🚘 Kelola Armada</a></li>
      <li><a href="<?= BASE_URL ?>/admin/articles.php" class="<?= $activePage === 'articles' ? 'active' : '' ?>">📰 Kelola Artikel</a></li>
      <li><a href="<?= BASE_URL ?>/admin/gallery.php" class="<?= $activePage === 'gallery' ? 'active' : '' ?>">🖼️ Kelola Galeri</a></li>
      <li><a href="<?= BASE_URL ?>/admin/languages.php" class="<?= $activePage === 'languages' ? 'active' : '' ?>">🌐 Kelola Bahasa</a></li>
      <li><a href="<?= BASE_URL ?>/admin/messages.php" class="<?= $activePage === 'messages' ? 'active' : '' ?>">💬 Pesan Masuk</a></li>
      <li><a href="<?= BASE_URL ?>/admin/settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">⚙️ Pengaturan Sistem</a></li>
      <li><a href="<?= BASE_URL ?>/admin/logout.php" style="color: #f87171; margin-top: 1.5rem;">🚪 Keluar (Logout)</a></li>
    </ul>
  </aside>

  <main class="admin-main">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 0.5rem;">
      <h1 style="font-size: 1.45rem; font-weight: 800; color: var(--admin-sidebar);"><?= isset($pageTitle) ? Security::e($pageTitle) : 'Dashboard' ?></h1>
      <div style="font-size: 0.85rem; color: var(--admin-text-muted); background: #ffffff; padding: 0.4rem 0.85rem; border-radius: 6px; border: 1px solid var(--admin-border);">
        Administrator: <strong style="color: var(--admin-sidebar);"><?= Security::e($user['full_name']) ?></strong>
      </div>
    </div>