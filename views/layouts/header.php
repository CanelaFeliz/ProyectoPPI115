<?php
/**
 * ==============================================================================
 * NIXGARU – Layout: Encabezado Personal de Servicio y Cajero
 * views/layouts/header.php (RNF04, RF16)
 * ==============================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    }
}

// Protección de ruta (RNF04): rechazar acceso directo si no existe una sesión activa y redirigir al login
if (empty($_SESSION['id_usuario'])) {
    header('Location: /NIXGARU/index.php');
    exit;
}

// Identificación dinámica de la vista actual
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);

// Variables dinámicas obtenidas exclusivamente de la sesión activa (RNF04)
$idMesaSeleccionada = $idMesaSeleccionada ?? 1;
$numeroMesa = $numeroMesa ?? (string)$idMesaSeleccionada;
$nombreUsuario = trim((string)($_SESSION['nombre'] ?? $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? ''));
$rolUsuario = trim((string)($_SESSION['rol'] ?? $_SESSION['nombre_rol'] ?? ''));

// Generar iniciales reales para el avatar del empleado autenticado
$palabras = preg_split('/\s+/', trim((string)$nombreUsuario)) ?: [];
if (count($palabras) >= 2) {
    $avatarUsuario = mb_strtoupper(mb_substr($palabras[0], 0, 1) . mb_substr($palabras[1], 0, 1));
} else {
    $avatarUsuario = mb_strtoupper(mb_substr((string)$nombreUsuario, 0, 2));
}
if ($avatarUsuario === '') {
    $avatarUsuario = 'US';
}

// Título de página por defecto
if (!isset($pageTitle)) {
    switch ($currentPage) {
        case 'mesas.php':
            $pageTitle = 'NIXGARU – Plano de Mesas | Personal de Servicio';
            break;
        case 'pedidos_web.php':
            $pageTitle = 'NIXGARU – Recepción de Pedidos Web | Personal de Servicio';
            break;
        case 'gestion_orden.php':
            $pageTitle = 'NIXGARU – Gestión de Orden | Personal de Servicio';
            break;
        case 'cierre_cuenta.php':
            $pageTitle = 'NIXGARU – Cierre de Cuenta | Personal de Servicio';
            break;
        default:
            $pageTitle = 'NIXGARU – Personal de Servicio';
            break;
    }
}

// Título centrado del encabezado por defecto si no viene predefinido desde la vista
if (!isset($headerTitle)) {
    switch ($currentPage) {
        case 'mesas.php':
            $headerTitle = 'Plano de Mesas';
            break;
        case 'pedidos_web.php':
            $headerTitle = 'Recepción de Pedidos Web';
            break;
        case 'gestion_orden.php':
            $headerTitle = 'Orden Activa - Mesa ' . ($numeroMesa ?? $idMesaSeleccionada ?? '1');
            break;
        case 'cierre_cuenta.php':
            $headerTitle = 'Cierre de Cuenta - Mesa ' . ($numeroMesa ?? $idMesaSeleccionada ?? '1');
            break;
        default:
            $headerTitle = 'Personal de Servicio';
            break;
    }
}

$ariaControlsSidebar = match ($currentPage) {
    'gestion_orden.php' => 'activeOrderSidebar',
    'cierre_cuenta.php' => 'ticketSidebar',
    'pedidos_web.php'   => 'webOrderSidebar',
    default             => 'cuentaPanel',
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <!-- Preconexión y carga de Google Fonts: Poppins y Courier Prime -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
  <?php if ($currentPage === 'cierre_cuenta.php' || !empty($incluirHtml2Pdf)): ?>
  <!-- Librería html2pdf.js para exportar el ticket a PDF en el cliente -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <?php endif; ?>
  <link rel="stylesheet" href="/NIXGARU/public/css/global.css">
  <?php if (in_array($currentPage, ['mesas.php', 'gestion_orden.php', 'cierre_cuenta.php', 'pedidos_web.php'], true) || (isset($rolUsuario) && $rolUsuario === 'Personal de servicio')): ?>
  <!-- Estilos modulares para el módulo de personal de servicio -->
  <link rel="stylesheet" href="/NIXGARU/public/css/servicio.css?v=<?php echo file_exists(__DIR__ . '/../../public/css/servicio.css') ? filemtime(__DIR__ . '/../../public/css/servicio.css') : time(); ?>">
  <?php endif; ?>
</head>
<body>

  <!-- Encabezado / Header Personal de Servicio -->
  <header class="service-header">
    <div class="service-header-container">
      
      <!-- Logotipo NIXGARU a la izquierda (Enlace a mesas.php) -->
      <a href="mesas.php" class="brand-logo-service" style="text-decoration: none; color: inherit;" title="Ir al Plano de Mesas">
        <div class="logo-icon-service">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </div>
        <div class="logo-text-group">
          <span class="logo-brand-name">INICIO</span>
          <span class="logo-subtext">NIXGARU</span>
        </div>
      </a>

      <!-- Centro: Título Centrado en todas las pantallas del módulo de servicio -->
      <div class="header-center-title">
        <h1 class="cierre-main-title"><?php echo htmlspecialchars($headerTitle); ?></h1>
      </div>

      <!-- Derecha: Navegación operativa, perfil de usuario y panel de detalle -->
      <div class="service-user-actions">

        <!-- Enlaces rápidos entre Salón (Plano de Mesas) y Bandeja de Pedidos Web (RF16) -->
        <nav class="service-nav-pills" aria-label="Navegación de servicio">
          <a href="mesas.php" class="btn-header-nav <?php echo $currentPage === 'mesas.php' ? 'active' : ''; ?>" title="Plano de Mesas">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7" rx="1"></rect>
              <rect x="14" y="3" width="7" height="7" rx="1"></rect>
              <rect x="14" y="14" width="7" height="7" rx="1"></rect>
              <rect x="3" y="14" width="7" height="7" rx="1"></rect>
            </svg>
            <span>Plano de Mesas</span>
          </a>

          <a href="pedidos_web.php" class="btn-header-nav <?php echo $currentPage === 'pedidos_web.php' ? 'active' : ''; ?>" id="navLinkPedidosWeb" title="Bandeja de Pedidos Web">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span>Pedidos Web</span>
          </a>
        </nav>

        <!-- Menú Desplegable del Perfil del Empleado -->
        <div class="user-profile-dropdown-wrapper">
          <div class="user-profile-badge" id="userProfileBadge" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" title="Perfil y opciones de sesión">
            <div class="user-avatar" id="avatarUsuario"><?php echo htmlspecialchars($avatarUsuario); ?></div>
            <div class="user-meta">
              <span class="user-name" id="nombreUsuario"><?php echo htmlspecialchars((string)$nombreUsuario); ?></span>
              <span class="user-role" id="rolUsuario"><?php echo htmlspecialchars((string)$rolUsuario); ?></span>
            </div>
            <!-- Ícono chevron para indicar menú desplegable -->
            <svg class="profile-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>

          <!-- Dropdown oculto por defecto con opción de Cerrar Sesión -->
          <div class="user-dropdown-menu" id="userDropdownMenu" aria-hidden="true">
            <div class="dropdown-header-info">
              <span class="dropdown-user-name"><?php echo htmlspecialchars((string)$nombreUsuario); ?></span>
              <span class="dropdown-user-role"><?php echo htmlspecialchars((string)$rolUsuario); ?></span>
            </div>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item btn-logout" id="btnCerrarSesion" title="Cerrar Sesión" type="button">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C53145" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              <span>Cerrar Sesión</span>
            </button>
          </div>
        </div>

        <?php if (in_array($currentPage, ['mesas.php', 'gestion_orden.php', 'cierre_cuenta.php', 'pedidos_web.php'], true)): ?>
        <!-- Botón 'Detalle' (Visible en las 4 vistas del módulo de servicio) -->
        <button class="btn-toggle-detail active" id="btnToggleDetalle" title="Mostrar u ocultar panel de detalle" type="button" aria-controls="<?php echo htmlspecialchars($ariaControlsSidebar); ?>" aria-expanded="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="15" y1="3" x2="15" y2="21"></line>
          </svg>
          <span>Detalle</span>
        </button>
        <?php endif; ?>
      </div>

    </div>
  </header>
