<?php
/**
 * ==============================================================================
 * NIXGARU – Layout: Encabezado y Barra Lateral Administrativa
 * views/layouts/sidebar.php
 * ==============================================================================
 */

// Detección dinámica de la página actual para aplicar la clase .active en la navegación
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);

// Título dinámico por defecto según la página si no fue especificado previamente
if (!isset($pageTitle)) {
    switch ($currentPage) {
        case 'admin_catalogo.php':
            $pageTitle = 'NIXGARU – Gestión de Catálogo y Menú';
            break;
        case 'admin_mesas.php':
            $pageTitle = 'NIXGARU – Administración de Mesas';
            break;
        case 'admin_inventario.php':
            $pageTitle = 'NIXGARU – Control de Inventarios';
            break;
        case 'admin_personal.php':
            $pageTitle = 'NIXGARU – Gestión de Cuentas de Personal';
            break;
        case 'admin_dashboard.php':
            $pageTitle = 'NIXGARU – Resumen Analítico';
            break;
        default:
            $pageTitle = 'NIXGARU – Administración';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NIXGARU/public/css/global.css">
  <!-- Hoja de estilos exclusiva para el módulo administrativo -->
  <link rel="stylesheet" href="/NIXGARU/public/css/admin.css">
</head>
<body class="admin-body">

  <div class="admin-layout">

    <!-- BARRA LATERAL (SIDEBAR ADMIN OSCURA #382823) -->
    <aside class="admin-sidebar">
      
      <!-- Logotipo Admin -->
      <div class="admin-brand">
        <div class="admin-brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </div>
        <div class="admin-brand-info">
          <span class="admin-brand-title">NIXGARU</span>
          <span class="admin-brand-sub">ADMINISTRACION</span>
        </div>
      </div>

      <!-- Menú de Navegación del Administrador -->
      <nav class="admin-nav">
        <!-- Catálogo y Menú (Enlace a admin_catalogo.php) -->
        <a href="admin_catalogo.php" class="admin-nav-item <?php echo ($currentPage === 'admin_catalogo.php') ? 'active' : ''; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span>Catálogo y Menú</span>
        </a>

        <!-- Gestión de Mesas (Enlace a admin_mesas.php) -->
        <a href="admin_mesas.php" class="admin-nav-item <?php echo ($currentPage === 'admin_mesas.php') ? 'active' : ''; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3h18v18H3zM3 9h18M9 21V9"></path>
          </svg>
          <span>Gestión de Mesas</span>
        </a>

        <!-- Control de Inventarios (Enlace a admin_inventario.php) -->
        <a href="admin_inventario.php" class="admin-nav-item <?php echo ($currentPage === 'admin_inventario.php') ? 'active' : ''; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          <span>Control de Inventarios</span>
        </a>

        <!-- Cuentas de Personal (Enlace a admin_personal.php) -->
        <a href="admin_personal.php" class="admin-nav-item <?php echo ($currentPage === 'admin_personal.php') ? 'active' : ''; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span>Cuentas de Personal</span>
        </a>

        <!-- Análisis y Reportes (Enlace a admin_dashboard.php) -->
        <a href="admin_dashboard.php" class="admin-nav-item <?php echo ($currentPage === 'admin_dashboard.php') ? 'active' : ''; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          <span>Análisis y Reportes</span>
        </a>
      </nav>

      <!-- Perfil de Administrador en el pie de la barra lateral -->
      <div class="admin-sidebar-profile">
        <div class="admin-profile-avatar">ML</div>
        <div class="admin-profile-meta">
          <span class="admin-profile-name">María López</span>
          <span class="admin-profile-role">Administradora</span>
        </div>
      </div>

    </aside>

