<?php
/**
 * ==============================================================================
 * NIXGARU – Layout: Encabezado Personal de Servicio
 * views/layouts/header.php
 * ==============================================================================
 */

// Identificación dinámica de la vista actual
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);

// Variables dinámicas con valores por defecto seguros
$idMesaSeleccionada = $idMesaSeleccionada ?? 1;
$numeroMesa = $numeroMesa ?? (string)$idMesaSeleccionada;
$nombreUsuario = $nombreUsuario ?? ($nombreAtendio ?? 'Carlos Méndez');
$rolUsuario = $rolUsuario ?? 'Personal de servicio';

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

// Título de página por defecto
if (!isset($pageTitle)) {
    switch ($currentPage) {
        case 'mesas.php':
            $pageTitle = 'NIXGARU – Plano de Mesas | Personal de Servicio';
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
  <?php if (in_array($currentPage, ['mesas.php', 'gestion_orden.php', 'cierre_cuenta.php']) || (isset($rolUsuario) && $rolUsuario === 'Personal de servicio')): ?>
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

      <!-- Derecha: Perfil de usuario con menú desplegable y botones de acción -->
      <div class="service-user-actions">
        <!-- Menú Desplegable del Perfil del Empleado -->
        <div class="user-profile-dropdown-wrapper">
          <div class="user-profile-badge" id="userProfileBadge" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" title="Perfil y opciones de sesión">
            <div class="user-avatar" id="avatarUsuario"><?php echo htmlspecialchars($avatarUsuario); ?></div>
            <div class="user-meta">
              <span class="user-name" id="nombreUsuario"><?php echo htmlspecialchars($nombreUsuario); ?></span>
              <span class="user-role" id="rolUsuario"><?php echo htmlspecialchars($rolUsuario); ?></span>
            </div>
            <!-- Ícono chevron para indicar menú desplegable -->
            <svg class="profile-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>

          <!-- Dropdown oculto por defecto con opción de Cerrar Sesión -->
          <div class="user-dropdown-menu" id="userDropdownMenu" aria-hidden="true">
            <div class="dropdown-header-info">
              <span class="dropdown-user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
              <span class="dropdown-user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
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

        <?php if ($currentPage === 'mesas.php' || $currentPage === 'gestion_orden.php' || $currentPage === 'cierre_cuenta.php'): ?>
        <!-- Botón 'Detalle' (Visible tanto en mesas como en gestión de orden y cierre de cuenta) -->
        <button class="btn-toggle-detail" id="btnToggleDetalle" title="Mostrar u ocultar panel de detalle" type="button" aria-controls="<?php echo $currentPage === 'gestion_orden.php' ? 'activeOrderSidebar' : ($currentPage === 'cierre_cuenta.php' ? 'ticketSidebar' : 'cuentaPanel'); ?>" aria-expanded="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="15" y1="3" x2="15" y2="21"></line>
          </svg>
          <span>Detalle</span>
        </button>
        <?php else: ?>
        <!-- Botón para regresar al mapa de mesas -->
        <button class="btn-back-tables" id="btnMapaMesas" title="Regresar al mapa de mesas" type="button" onclick="window.location.href='mesas.php?mesa_id=<?php echo htmlspecialchars($numeroMesa ?? $idMesaSeleccionada); ?>'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          <span>Mapa de mesas</span>
        </button>
        <?php endif; ?>
      </div>

    </div>
  </header>

