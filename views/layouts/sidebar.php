<?php
/**
 * ==============================================================================
 * NIXGARU – Layout: Encabezado y Barra Lateral Administrativa
 * views/layouts/sidebar.php
 * ==============================================================================
 */

// Iniciar sesión para recuperar datos del administrador si existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detección dinámica de la página actual para aplicar la clase .active en la navegación
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);

// Datos del usuario administrador en sesión
$nombreUsuario = $_SESSION['nombre'] ?? ($nombreUsuario ?? 'María López');
$rolUsuario = $_SESSION['rol'] ?? ($rolUsuario ?? 'Administradora');

// Generar iniciales para el avatar
if (!isset($avatarUsuario)) {
    $palabras = preg_split('/\s+/', trim($nombreUsuario));
    $avatarUsuario = '';
    if (count($palabras) >= 2) {
        $avatarUsuario = mb_strtoupper(mb_substr($palabras[0], 0, 1) . mb_substr($palabras[1], 0, 1));
    } else {
        $avatarUsuario = mb_strtoupper(mb_substr($nombreUsuario, 0, 2));
    }
}

// Comprobar estado previo colapsado de la barra lateral desde cookie
$sidebarCollapsed = isset($_COOKIE['admin_sidebar_collapsed']) && $_COOKIE['admin_sidebar_collapsed'] === 'true';

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
  <link rel="stylesheet" href="/NIXGARU/public/css/admin.css?v=<?php echo file_exists(__DIR__ . '/../../public/css/admin.css') ? filemtime(__DIR__ . '/../../public/css/admin.css') : time(); ?>">
  <script>
    // Aplicar estado colapsado inmediatamente antes del renderizado para evitar saltos de layout (FOUC)
    (function() {
      try {
        var isMobile = window.innerWidth <= 1024;
        var savedState = localStorage.getItem('admin_sidebar_collapsed');
        if (!isMobile && (savedState === 'true' || (savedState === null && <?php echo $sidebarCollapsed ? 'true' : 'false'; ?>))) {
          document.documentElement.classList.add('admin-sidebar-is-collapsed');
        }
      } catch (e) {}
    })();
  </script>
</head>
<body class="admin-body">

  <div class="admin-layout" id="adminLayout">

    <!-- HEADER SUPERIOR MÓVIL (Visible solo en pantallas móviles <= 1024px) -->
    <header class="admin-mobile-header" id="adminMobileHeader">
      <div class="admin-brand" id="adminMobileBrand" onclick="window.toggleAdminSidebar && window.toggleAdminSidebar(event)">
        <button class="admin-brand-icon btn-toggle-admin-mobile" id="btnToggleAdminMobile" type="button" onclick="window.toggleAdminSidebar && window.toggleAdminSidebar(event)" title="Abrir o cerrar menú" aria-label="Abrir o cerrar menú lateral de administración">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </button>
        <div class="admin-brand-info">
          <span class="admin-brand-title">NIXGARU</span>
          <span class="admin-brand-sub">ADMINISTRACION</span>
        </div>
      </div>
      <!-- Menú Desplegable del Perfil del Administrador (Header Móvil) -->
      <div class="admin-header-profile-wrapper">
        <div class="admin-header-profile" id="adminHeaderProfile" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" title="Perfil y opciones de sesión">
          <div class="admin-profile-avatar"><?php echo htmlspecialchars($avatarUsuario); ?></div>
        </div>
        <!-- Burbuja Desplegable de Usuario (Header Móvil) -->
        <div class="user-dropdown-menu admin-user-dropdown" id="adminHeaderDropdownMenu" aria-hidden="true">
          <div class="dropdown-header-info">
            <span class="dropdown-user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
            <span class="dropdown-user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
          </div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item btn-logout btn-admin-logout" id="btnAdminLogoutMobile" title="Cerrar Sesión" type="button">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </div>
    </header>

    <!-- TELÓN DE FONDO (BACKDROP) OSCURO DIFUMINADO PARA MÓVIL -->
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

    <!-- BARRA LATERAL (SIDEBAR ADMIN OSCURA #222629 / DRAWER EN MÓVIL) -->
    <aside class="admin-sidebar no-transition <?php echo $sidebarCollapsed ? 'collapsed' : ''; ?>" id="adminSidebar">
      <script>
        (function() {
          try {
            if (window.innerWidth > 1024) {
              var s = localStorage.getItem('admin_sidebar_collapsed');
              if (s === 'true') {
                document.getElementById('adminSidebar').classList.add('collapsed');
              } else if (s === 'false') {
                document.getElementById('adminSidebar').classList.remove('collapsed');
              }
            }
          } catch(e) {}
        })();
      </script>
      
      <!-- Logotipo Admin - Clic en la taza para alternar entre vista detallada y reducida en escritorio, o cerrar menú en móvil -->
      <div class="admin-brand" id="adminBrand">
        <button class="admin-brand-icon btn-toggle-admin-sidebar" id="btnToggleAdminSidebar" type="button" onclick="window.toggleAdminSidebar && window.toggleAdminSidebar(event)" title="Colapsar o cerrar menú" aria-label="Colapsar o cerrar menú lateral">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </button>
        <div class="admin-brand-info">
          <span class="admin-brand-title">NIXGARU</span>
          <span class="admin-brand-sub">ADMINISTRACION</span>
        </div>
      </div>

      <!-- Menú de Navegación del Administrador Reorganizado -->
      <nav class="admin-nav">
        <!-- 1. Análisis y Reportes (Enlace a admin_dashboard.php) -->
        <a href="admin_dashboard.php" class="admin-nav-item <?php echo ($currentPage === 'admin_dashboard.php') ? 'active' : ''; ?>" data-tooltip="Análisis y Reportes" title="Análisis y Reportes">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          <span>Análisis y Reportes</span>
        </a>

        <!-- 2. Catálogo y Menú (Enlace a admin_catalogo.php) -->
        <a href="admin_catalogo.php" class="admin-nav-item <?php echo ($currentPage === 'admin_catalogo.php') ? 'active' : ''; ?>" data-tooltip="Catálogo y Menú" title="Catálogo y Menú">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span>Catálogo y Menú</span>
        </a>

        <!-- 3. Gestión de Mesas (Enlace a admin_mesas.php) -->
        <a href="admin_mesas.php" class="admin-nav-item <?php echo ($currentPage === 'admin_mesas.php') ? 'active' : ''; ?>" data-tooltip="Gestión de Mesas" title="Gestión de Mesas">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3h18v18H3zM3 9h18M9 21V9"></path>
          </svg>
          <span>Gestión de Mesas</span>
        </a>

        <!-- 4. Control de Inventarios (Enlace a admin_inventario.php) -->
        <a href="admin_inventario.php" class="admin-nav-item <?php echo ($currentPage === 'admin_inventario.php') ? 'active' : ''; ?>" data-tooltip="Control de Inventarios" title="Control de Inventarios">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          <span>Control de Inventarios</span>
        </a>

        <!-- 5. Cuentas de Personal (Enlace a admin_personal.php) -->
        <a href="admin_personal.php" class="admin-nav-item <?php echo ($currentPage === 'admin_personal.php') ? 'active' : ''; ?>" data-tooltip="Cuentas de Personal" title="Cuentas de Personal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span>Cuentas de Personal</span>
        </a>
      </nav>

      <!-- Perfil de Administrador y Menú Desplegable en el pie de la barra lateral -->
      <div class="admin-sidebar-profile-wrapper">
        <div class="admin-sidebar-profile" id="adminSidebarProfile" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" title="Perfil y opciones de sesión">
          <div class="admin-profile-avatar"><?php echo htmlspecialchars($avatarUsuario); ?></div>
          <div class="admin-profile-meta">
            <span class="admin-profile-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
            <span class="admin-profile-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
          </div>
        </div>
        <!-- Burbuja Desplegable de Usuario (Barra Lateral) -->
        <div class="user-dropdown-menu admin-user-dropdown admin-dropdown-sidebar" id="adminSidebarDropdownMenu" aria-hidden="true">
          <div class="dropdown-header-info">
            <span class="dropdown-user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
            <span class="dropdown-user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
          </div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item btn-logout btn-admin-logout" id="btnAdminLogoutSidebar" title="Cerrar Sesión" type="button">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </div>

    </aside>

