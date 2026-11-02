<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Vista del Cliente
 * Pantalla: Menú del Cliente y Pedidos en Línea (menu_cliente.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db (RF01, RF02, RF10, RF15, RNF03)
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Categoria.php';

try {
    $productoModel = new Producto($pdo);
    $productos = $productoModel->obtenerActivos();

    // Consulta para los botones de filtros de categorías mediante el modelo Categoria
    $categoriaModel = new Categoria($pdo);
    $categorias = $categoriaModel->obtenerTodas();
} catch (PDOException $e) {
    die("Error al consultar el menú de productos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NIXGARU – Cafetería | Inicio y Menú en Línea</title>
  <!-- Preconexión y carga de Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NIXGARU/public/css/global.css?v=<?php echo filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
  <link rel="stylesheet" href="/NIXGARU/public/css/cliente.css?v=<?php echo filemtime(__DIR__ . '/../../public/css/cliente.css'); ?>">
  <!-- Librería html2pdf.js para exportación del comprobante PDF del cliente (RF10, RF15) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    /* Inversión garantizada de línea gráfica en vista cliente (Rojo Frambuesa y Terracota) */
    .logo-icon {
      background-color: var(--color-rojo-frambuesa);
      color: var(--color-blanco) !important;
    }
    .brand-logo:active .logo-icon,
    .logo-icon:active {
      background-color: var(--color-terracota) !important;
    }
    .btn-cart,
    .btn-confirm-order {
      background-color: var(--color-rojo-frambuesa) !important;
    }
    .category-pill.active {
      background-color: var(--color-rojo-frambuesa) !important;
      border-color: var(--color-rojo-frambuesa) !important;
      color: var(--color-blanco) !important;
    }
    .product-price,
    .total-amount {
      color: var(--color-rojo-frambuesa) !important;
    }

    /* ==========================================================================
       Modal de Checkout y Datos del Cliente (#modal-checkout-cliente)
       ========================================================================== */
    .checkout-modal-dialog {
      max-width: 680px;
      padding: 32px 34px;
      border-radius: 24px;
    }
    .checkout-header {
      margin-bottom: 20px;
      padding-right: 36px;
      border-bottom: 1px solid var(--color-borde-suave);
      padding-bottom: 14px;
    }
    .checkout-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--color-rojo-frambuesa);
      background-color: rgba(197, 46, 69, 0.1);
      padding: 4px 10px;
      border-radius: var(--radio-pill);
      margin-bottom: 8px;
    }
    .checkout-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--color-negro-suave);
      line-height: 1.25;
      margin-bottom: 4px;
    }
    .checkout-subtitle {
      font-size: 0.86rem;
      color: var(--color-texto-muted);
    }

    .checkout-error-alert {
      display: none;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 14px;
      margin-bottom: 18px;
      border-radius: 12px;
      background-color: rgba(197, 46, 69, 0.09);
      border: 1px solid rgba(197, 46, 69, 0.35);
      color: #9B1C31;
      font-size: 0.85rem;
      font-weight: 500;
      line-height: 1.4;
    }
    .checkout-error-alert.visible {
      display: flex;
    }

    .checkout-section {
      margin-bottom: 20px;
    }
    .checkout-section-title {
      font-size: 0.88rem;
      font-weight: 700;
      color: var(--color-negro-suave);
      text-transform: uppercase;
      letter-spacing: 0.45px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .checkout-section-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: var(--color-rojo-frambuesa);
      display: inline-block;
    }

    .checkout-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    .checkout-field {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .checkout-field.full-width {
      grid-column: 1 / -1;
    }
    .checkout-label {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--color-negro-suave);
    }
    .checkout-label .req {
      color: var(--color-rojo-frambuesa);
    }
    .checkout-input,
    .checkout-select {
      width: 100%;
      padding: 10px 13px;
      font-family: var(--fuente-principal);
      font-size: 0.88rem;
      color: var(--color-negro-suave);
      background-color: var(--color-blanco);
      border: 1.5px solid #DCDFE4;
      border-radius: 11px;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .checkout-input:focus,
    .checkout-select:focus {
      border-color: var(--color-rojo-frambuesa);
      box-shadow: 0 0 0 3px rgba(197, 46, 69, 0.14);
    }
    .checkout-select[readonly],
    .checkout-select.locked-select {
      background-color: #FAF6F5;
      border-color: rgba(198, 111, 80, 0.45);
      font-weight: 600;
    }

    /* Píldoras interactivas para Tipo de Orden y Método de Pago */
    .option-pills-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .option-pill-card {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 13px 15px;
      border: 1.8px solid #E2E5E9;
      border-radius: 14px;
      background-color: var(--color-blanco);
      cursor: pointer;
      transition: all 0.2s ease;
      user-select: none;
    }
    .option-pill-card:hover {
      border-color: var(--color-terracota);
      background-color: rgba(198, 111, 80, 0.04);
    }
    .option-pill-card input[type="radio"] {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }
    .option-pill-card.active {
      border-color: var(--color-rojo-frambuesa);
      background-color: rgba(197, 46, 69, 0.06);
      box-shadow: 0 4px 12px rgba(197, 46, 69, 0.1);
    }
    .option-pill-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background-color: var(--color-gris-superficie);
      color: var(--color-negro-suave);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: background-color 0.2s ease, color 0.2s ease;
    }
    .option-pill-card.active .option-pill-icon {
      background-color: var(--color-rojo-frambuesa);
      color: var(--color-blanco);
    }
    .option-pill-text {
      display: flex;
      flex-direction: column;
      line-height: 1.25;
    }
    .option-pill-title {
      font-size: 0.88rem;
      font-weight: 700;
      color: var(--color-negro-suave);
    }
    .option-pill-desc {
      font-size: 0.74rem;
      color: var(--color-texto-muted);
      margin-top: 2px;
    }

    /* Bloque condicional logístico (RF15) */
    .logistics-conditional-box {
      padding: 16px;
      border-radius: 14px;
      background-color: #FBF8F7;
      border: 1px dashed rgba(198, 111, 80, 0.55);
      margin-bottom: 20px;
    }
    .logistics-conditional-box.is-hidden {
      display: none;
    }

    /* Resumen financiero dentro de Checkout */
    .checkout-charge-summary {
      background-color: var(--color-gris-superficie);
      border-radius: 14px;
      padding: 14px 18px;
      margin-bottom: 16px;
      border: 1px solid var(--color-borde-suave);
    }
    .checkout-summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.86rem;
      color: #52525B;
      margin-bottom: 6px;
    }
    .checkout-summary-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 8px;
      margin-top: 8px;
      border-top: 1px solid #DCDFE4;
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--color-negro-suave);
    }
    .checkout-summary-total .amount {
      color: var(--color-rojo-frambuesa);
      font-size: 1.18rem;
    }

    /* Bloque Sandbox Simulado de Pago con Tarjeta (RNF03) */
    .card-sandbox-box {
      margin-top: 14px;
      padding: 16px;
      border-radius: 14px;
      background: linear-gradient(180deg, #FFF9F8 0%, #FDF3F1 100%);
      border: 1px solid rgba(197, 46, 69, 0.28);
    }
    .card-sandbox-box.is-hidden {
      display: none;
    }
    .sandbox-header-tag {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--color-terracota);
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .sandbox-abono-notice {
      margin-top: 12px;
      padding: 10px 12px;
      border-radius: 10px;
      background-color: rgba(197, 46, 69, 0.08);
      color: var(--color-negro-suave);
      font-size: 0.8rem;
      line-height: 1.4;
    }
    .sandbox-abono-notice strong {
      color: var(--color-rojo-frambuesa);
    }

    /* Botones inferiores del Checkout */
    .checkout-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 12px;
      margin-top: 22px;
      padding-top: 16px;
      border-top: 1px solid var(--color-borde-suave);
    }
    .btn-checkout-cancel {
      padding: 11px 22px;
      border-radius: 12px;
      border: 1.5px solid #D4D7DC;
      background-color: var(--color-blanco);
      color: var(--color-negro-suave);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .btn-checkout-cancel:hover {
      background-color: var(--color-gris-superficie);
      border-color: #B8BDC5;
    }
    .btn-checkout-submit {
      padding: 12px 26px;
      border-radius: 12px;
      border: none;
      background-color: var(--color-rojo-frambuesa);
      color: var(--color-blanco);
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 6px 16px rgba(197, 46, 69, 0.25);
      transition: background-color 0.2s ease, transform 0.15s ease;
    }
    .btn-checkout-submit:hover {
      background-color: #AD253A;
      transform: translateY(-1px);
    }
    .btn-checkout-submit:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    /* ==========================================================================
       Modal de Éxito y Comprobante PDF (#modal-pedido-exito / #clientTicketContainer)
       ========================================================================== */
    .exito-modal-dialog {
      max-width: 520px;
      padding: 28px 28px 26px;
      border-radius: 24px;
    }
    .exito-top-banner {
      text-align: center;
      margin-bottom: 18px;
    }
    .exito-check-circle {
      width: 58px;
      height: 58px;
      background: rgba(25, 135, 84, 0.12);
      color: #198754;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
    }
    .exito-heading {
      font-size: 1.32rem;
      font-weight: 800;
      color: var(--color-negro-suave);
      margin-bottom: 4px;
    }
    .exito-subheading {
      font-size: 0.84rem;
      color: var(--color-texto-muted);
    }

    /* Contenedor estructurado del Comprobante NIXGARU exportable a PDF */
    .client-ticket-paper {
      background-color: #FFFFFF;
      color: #222629;
      border: 1.5px solid #E4E4E7;
      border-top: 5px solid var(--color-rojo-frambuesa);
      border-radius: 14px;
      padding: 22px 22px 18px;
      text-align: left;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
      font-family: var(--fuente-principal);
    }
    .ct-header {
      text-align: center;
      padding-bottom: 12px;
      border-bottom: 1.5px dashed #D4D4D8;
      margin-bottom: 12px;
    }
    .ct-brand {
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: 1.5px;
      color: var(--color-rojo-frambuesa);
      margin: 0;
    }
    .ct-slogan {
      font-size: 0.82rem;
      font-style: italic;
      color: var(--color-terracota);
      font-weight: 600;
      margin: 2px 0;
    }
    .ct-location {
      font-size: 0.76rem;
      color: #71717A;
      margin: 0;
    }

    .ct-meta-block {
      font-size: 0.8rem;
      padding-bottom: 10px;
      margin-bottom: 10px;
      border-bottom: 1.5px dashed #D4D4D8;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .ct-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 10px;
    }
    .ct-meta-label {
      color: #71717A;
      font-weight: 500;
    }
    .ct-meta-value {
      color: #222629;
      font-weight: 600;
      text-align: right;
    }
    .ct-folio-badge {
      font-family: var(--fuente-mono);
      font-weight: 700;
      color: var(--color-rojo-frambuesa);
      font-size: 0.86rem;
    }
    .ct-delivery-box {
      margin-top: 6px;
      padding: 8px 10px;
      background-color: #FAF7F6;
      border-radius: 8px;
      border-left: 3px solid var(--color-terracota);
      font-size: 0.77rem;
      line-height: 1.4;
    }
    .ct-delivery-box.is-hidden {
      display: none;
    }

    .ct-items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
      font-size: 0.8rem;
    }
    .ct-items-table thead th {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: #71717A;
      font-weight: 700;
      padding-bottom: 6px;
      border-bottom: 1px solid #E4E4E7;
    }
    .ct-items-table tbody td {
      padding: 6px 0;
      border-bottom: 1px dotted #ECECEC;
      color: #222629;
      vertical-align: top;
    }
    .ct-col-qty {
      width: 36px;
      font-weight: 700;
      color: var(--color-rojo-frambuesa);
    }
    .ct-col-unit {
      text-align: right;
      white-space: nowrap;
      color: #52525B;
      padding-left: 8px !important;
    }
    .ct-col-sub {
      text-align: right;
      white-space: nowrap;
      font-weight: 700;
      padding-left: 10px !important;
    }

    .ct-totals {
      padding-top: 8px;
      border-top: 1.5px dashed #D4D4D8;
      display: flex;
      flex-direction: column;
      gap: 5px;
      font-size: 0.82rem;
    }
    .ct-total-line {
      display: flex;
      justify-content: space-between;
      color: #3F3F46;
    }
    .ct-grand-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 6px 0;
      padding: 8px 10px;
      border-radius: 8px;
      background-color: rgba(197, 46, 69, 0.08);
      font-size: 0.96rem;
      font-weight: 800;
      color: var(--color-rojo-frambuesa);
    }
    .ct-payment-breakdown {
      margin-top: 4px;
      padding-top: 8px;
      border-top: 1px solid #ECECEC;
      display: flex;
      flex-direction: column;
      gap: 4px;
      font-size: 0.79rem;
    }
    .ct-footer {
      text-align: center;
      margin-top: 14px;
      padding-top: 10px;
      border-top: 1.5px dashed #D4D4D8;
      font-size: 0.75rem;
      color: #71717A;
    }

    .exito-actions-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 18px;
    }
    .btn-download-ticket {
      flex: 1.4;
      min-width: 210px;
      padding: 12px 18px;
      border-radius: 12px;
      border: none;
      background-color: var(--color-rojo-frambuesa);
      color: var(--color-blanco);
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background-color 0.2s ease, transform 0.15s ease;
    }
    .btn-download-ticket:hover {
      background-color: #AD253A;
      transform: translateY(-1px);
    }
    .btn-download-ticket:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }
    .btn-accept-ticket {
      flex: 1;
      min-width: 120px;
      padding: 12px 18px;
      border-radius: 12px;
      border: 1.5px solid var(--color-negro-suave);
      background-color: var(--color-negro-suave);
      color: var(--color-blanco);
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.2s ease;
    }
    .btn-accept-ticket:hover {
      opacity: 0.9;
    }

    @media (max-width: 640px) {
      .checkout-modal-dialog,
      .exito-modal-dialog {
        padding: 22px 18px;
      }
      .checkout-grid-2,
      .option-pills-grid {
        grid-template-columns: 1fr;
      }
      .checkout-actions {
        flex-direction: column-reverse;
      }
      .btn-checkout-cancel,
      .btn-checkout-submit {
        width: 100%;
        justify-content: center;
      }
    }
  
    /* Prevención de parpadeo azul nativo de Android/Chrome al presionar productos o botones */
    *, *::before, *::after {
      -webkit-tap-highlight-color: transparent !important;
    }
    .product-card,
    .card-quantity-control,
    .qty-btn,
    .card-qty-btn {
      -webkit-tap-highlight-color: transparent !important;
      -webkit-tap-highlight-color: rgba(0, 0, 0, 0) !important;
      user-select: none !important;
      -webkit-user-select: none !important;
      touch-action: manipulation !important;
      outline: none !important;
    }
    .product-card::selection,
    .product-card *::selection {
      background: transparent !important;
    }
  </style>
</head>
<body>

  <!-- Navegación Principal / Header -->
  <header class="main-header" id="inicio">
    <div class="header-container">
      
      <!-- Logotipo -->
      <a href="menu_cliente.php" class="brand-logo" id="brandLogo" title="NIXGARU">
        <div class="logo-icon" id="logoIcon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </div>
        <span class="logo-text">NIXGARU</span>
      </a>

      <!-- Menú de Enlaces -->
      <nav class="nav-links">
        <a href="#inicio" class="nav-link active">Inicio</a>
        <a href="#menu" class="nav-link">Menú</a>
        <a href="#nosotros" class="nav-link">Sobre nosotros</a>
      </nav>

      <!-- Acciones de Cabecera -->
      <div class="header-actions">
        <!-- Buscador Desplegable en Tiempo Real -->
        <div class="header-search-box" id="headerSearchBox" role="search">
          <svg class="search-box-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" id="inputBuscarCliente" class="header-search-input" placeholder="Buscar café, postre, platillo..." autocomplete="off" aria-label="Buscar productos en el menú">
          <button type="button" id="btnClearSearch" class="btn-clear-search" aria-label="Limpiar búsqueda" title="Limpiar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <button class="btn-icon btn-search-toggle" id="btnToggleSearch" aria-label="Buscar productos" type="button" title="Buscar productos" aria-expanded="false" aria-controls="headerSearchBox">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </button>

        <button id="cart-toggle-btn" class="btn-cart" aria-expanded="false" aria-controls="cart-sidebar" type="button" style="background-color: var(--color-rojo-frambuesa);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
          </svg>
          <span>Ver carrito</span>
        </button>
      </div>

    </div>
  </header>

  <!-- Contenedor Principal Adaptable (Layout con Barra Lateral de Carrito) -->
  <div class="app-layout">
    
    <!-- Contenido Principal -->
    <main class="main-content">

      <!-- Banner de Promociones del día -->
      <section class="promo-banner" id="promociones">
        <div class="promo-content">
          <h1 class="promo-title">Promociones del día</h1>
          <p class="promo-description">
            Consulta nuestros productos de temporada con descuentos especiales.
          </p>
          <a href="#menu" class="btn-explore">Explorar menú</a>
        </div>
      </section>

      <!-- Filtros de Categorías (Píldoras con atributo data-filter) -->
      <section class="categories-section" id="menu">
        <div class="categories-list">
          <button type="button" class="category-pill filtro-btn active" data-filter="all">Todos</button>
          <?php foreach ($categorias as $cat): ?>
            <button type="button" class="category-pill filtro-btn" data-filter="<?php echo $cat['id_categoria']; ?>">
              <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
            </button>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Cuadrícula de Productos con atributos para modal y filtrado -->
      <section class="products-grid">
        
        <?php foreach ($productos as $producto): ?>
          <div class="product-card producto-card" 
               data-category="<?php echo (int)$producto['id_categoria']; ?>"
               data-id="<?php echo (int)$producto['id_producto']; ?>"
               data-codigo="<?php echo (int)$producto['id_producto']; ?>"
               data-name="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
               data-price="<?php echo htmlspecialchars((string)$producto['precio']); ?>" 
               data-desc="<?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?>"
               data-foto="<?php echo htmlspecialchars($producto['fotografia'] ?? ''); ?>"
               data-ingredients="<?php echo htmlspecialchars($producto['ingredientes']); ?>">
            
            <div class="product-image-placeholder">
              <?php if (!empty($producto['fotografia'])): ?>
                <img 
                  src="../../public/img/<?php echo htmlspecialchars($producto['fotografia']); ?>" 
                  alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>"
                  onerror="this.style.display='none';"
                >
              <?php endif; ?>
            </div>

            <div class="product-info">
              <h3 class="product-name"><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
              <p class="product-description"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Delicioso producto recién preparado.'); ?></p>
              <div class="product-footer">
                <span class="product-price" style="color: var(--color-rojo-frambuesa);">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                <div class="card-quantity-control quantity-control" data-id="<?php echo (int)$producto['id_producto']; ?>" data-codigo="<?php echo (int)$producto['id_producto']; ?>">
                  <button class="qty-btn card-qty-btn card-qty-minus" aria-label="Disminuir cantidad" type="button" title="Disminuir cantidad">-</button>
                  <span class="qty-value card-qty-value">0</span>
                  <button class="qty-btn card-qty-btn card-qty-plus" aria-label="Aumentar cantidad" type="button" title="Aumentar cantidad">+</button>
                </div>
              </div>
            </div>

          </div>
        <?php endforeach; ?>

        <!-- Mensaje cuando la búsqueda no arroja coincidencias -->
        <div id="noSearchResults" class="no-search-results" style="display: none;">
          <div class="no-search-icon">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              <line x1="8" y1="11" x2="14" y2="11"></line>
            </svg>
          </div>
          <h3 class="no-search-title">No se encontraron productos</h3>
          <p class="no-search-desc">No hay productos que coincidan con "<strong id="noSearchTerm"></strong>".</p>
          <button type="button" class="btn-clear-search-all" id="btnResetSearch">Ver todos los productos</button>
        </div>

      </section>

    </main>

    <!-- Overlay oscuro difuminado (Backdrop) para dispositivos móviles -->
    <div id="cart-backdrop" class="cart-backdrop" aria-hidden="true"></div>

    <!-- Barra Lateral del Carrito ("Tu carrito" - Pedido en línea RF15) -->
    <aside id="cart-sidebar" class="cart-sidebar" aria-hidden="true">
      <div class="cart-card">
        
        <!-- Cabecera del Carrito -->
        <div class="cart-header">
          <div class="cart-title-wrapper">
            <h2 class="cart-title">Tu carrito</h2>
            <span class="cart-badge" id="cart-badge">0 Items</span>
          </div>
          <button id="cart-close-btn" class="cart-close-btn" aria-label="Cerrar carrito" type="button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Lista de Artículos en el Carrito (Inyección dinámica con JS) -->
        <div class="cart-items" id="cart-items-container">
          <!-- Se agregan dinámicamente con JS -->
        </div>

        <!-- Resumen y Totales -->
        <div class="cart-summary">
          <div class="summary-line">
            <span>Subtotal</span>
            <span id="cart-subtotal">$0.00</span>
          </div>
          <div class="summary-line">
            <span>Propina (10%)</span>
            <span id="cart-tip">$0.00</span>
          </div>
          <div class="summary-total">
            <span class="total-label">Total</span>
            <span class="total-amount" id="cart-total" style="color: var(--color-rojo-frambuesa);">$0.00</span>
          </div>
          <button class="btn-confirm-order" id="btnConfirmarPedido" type="button" style="background-color: var(--color-rojo-frambuesa);">Confirmar Pedido</button>
        </div>

      </div>
    </aside>

  </div>

  <!-- Footer del Cliente -->
  <footer class="client-footer" id="nosotros">
    <div class="footer-content">
      <div class="footer-brand">
        <h3 class="footer-logo">NIXGARU</h3>
        <p class="footer-slogan">"Más que café, momentos."</p>
      </div>
      <div class="footer-info">
        <p><strong>Ubicación:</strong> San Salvador, El Salvador</p>
        <p><strong>Horario:</strong> Lunes a sábado, de 7:00 a. m. a 7:00 p. m.</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 NIXGARU Cafetería. Todos los derechos reservados.</p>
    </div>
  </footer>

  <!-- Modal: Vista Detallada de Producto (Fondo Empañado) -->
  <div id="product-modal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-product-title">
      
      <!-- Botón de Cerrar (X) -->
      <button id="modal-close-btn" class="modal-close-btn" aria-label="Cerrar ventana" type="button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <!-- Cuerpo del Modal -->
      <div class="modal-body">
        
        <!-- Columna Izquierda: Imagen del Producto -->
        <div class="modal-image-col">
          <div class="modal-product-image" id="modal-product-image"></div>
        </div>

        <!-- Columna Derecha: Información Detallada -->
        <div class="modal-info-col">
          <h2 id="modal-product-title" class="modal-title">Nombre del producto</h2>
          <span id="modal-product-price" class="modal-price">$0.00</span>
          <p id="modal-product-desc" class="modal-description">Descripción del producto</p>

          <div class="modal-ingredients-section">
            <h4 class="ingredients-label">Ingredientes:</h4>
            <p id="modal-product-ingredients" class="ingredients-text">Cargando ingredientes...</p>
          </div>

          <div class="modal-actions-row">
            <div class="quantity-control modal-qty">
              <button class="qty-btn" id="modal-qty-minus" aria-label="Disminuir cantidad" type="button">-</button>
              <span class="qty-value" id="modal-qty-val">1</span>
              <button class="qty-btn" id="modal-qty-plus" aria-label="Aumentar cantidad" type="button">+</button>
            </div>
            <button id="modal-add-btn" class="btn-modal-add" type="button">Agregar</button>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Modal: Checkout y Datos del Cliente (RF15, RNF03) -->
  <div id="modal-checkout-cliente" class="modal-backdrop" aria-hidden="true">
    <div class="modal-dialog checkout-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="checkoutModalTitle">
      
      <button id="btnCerrarModalCheckout" class="modal-close-btn" aria-label="Cerrar formulario de pedido" type="button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <div class="checkout-header">
        <span class="checkout-badge">Pedido en línea • NIXGARU</span>
        <h2 id="checkoutModalTitle" class="checkout-title">Finalizar tu Pedido</h2>
        <p class="checkout-subtitle">Completa tus datos de contacto, elige la modalidad de entrega y confirma tu método de pago.</p>
      </div>

      <!-- Alerta para mensajes de validación o errores HTTP del servidor -->
      <div id="checkoutErrorAlert" class="checkout-error-alert" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span id="checkoutErrorText">Por favor verifica los datos ingresados.</span>
      </div>

      <form id="formCheckoutCliente" novalidate>
        
        <!-- 1. Datos personales del cliente (obligatorios) -->
        <div class="checkout-section">
          <h3 class="checkout-section-title">
            <span class="checkout-section-dot"></span>
            1. Datos Personales del Cliente
          </h3>
          <div class="checkout-grid-2">
            <div class="checkout-field">
              <label class="checkout-label" for="inputNombreCliente">Nombre <span class="req">*</span></label>
              <input type="text" id="inputNombreCliente" name="nombre_cliente" class="checkout-input" placeholder="Ej. María" required maxlength="100" autocomplete="given-name">
            </div>
            <div class="checkout-field">
              <label class="checkout-label" for="inputApellidoCliente">Apellido <span class="req">*</span></label>
              <input type="text" id="inputApellidoCliente" name="apellido_cliente" class="checkout-input" placeholder="Ej. Hernández" required maxlength="100" autocomplete="family-name">
            </div>
            <div class="checkout-field">
              <label class="checkout-label" for="inputTelefonoCliente">Teléfono <span class="req">*</span></label>
              <input type="tel" id="inputTelefonoCliente" name="telefono_cliente" class="checkout-input" placeholder="7000-0000" required maxlength="9" pattern="^[267][0-9]{3}-?[0-9]{4}$" title="Ingrese un número de teléfono de 8 dígitos de El Salvador (ej. 7000-0000 o 2222-0000)" inputmode="numeric" autocomplete="tel">
            </div>
            <div class="checkout-field">
              <label class="checkout-label" for="inputCorreoCliente">Correo electrónico <span class="req">*</span></label>
              <input type="email" id="inputCorreoCliente" name="correo_cliente" class="checkout-input" placeholder="cliente@correo.com" required maxlength="120" autocomplete="email">
            </div>
          </div>
        </div>

        <!-- 2. Selección de Modalidad de la Orden (tipo_orden) -->
        <div class="checkout-section">
          <h3 class="checkout-section-title">
            <span class="checkout-section-dot"></span>
            2. Modalidad de la Orden
          </h3>
          <div class="option-pills-grid" role="radiogroup" aria-label="Modalidad de la orden">
            <label class="option-pill-card active" id="pillTipoDomicilio" for="radioTipoDomicilio">
              <input type="radio" id="radioTipoDomicilio" name="tipo_orden" value="Envío a domicilio" checked>
              <div class="option-pill-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="3" width="15" height="13"></rect>
                  <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                  <circle cx="5.5" cy="18.5" r="2.5"></circle>
                  <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
              </div>
              <div class="option-pill-text">
                <span class="option-pill-title">Envío a domicilio</span>
                <span class="option-pill-desc">Entrega en San Salvador</span>
              </div>
            </label>

            <label class="option-pill-card" id="pillTipoRetiro" for="radioTipoRetiro">
              <input type="radio" id="radioTipoRetiro" name="tipo_orden" value="Retiro en el local">
              <div class="option-pill-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                  <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
              </div>
              <div class="option-pill-text">
                <span class="option-pill-title">Retiro en el local</span>
                <span class="option-pill-desc">Recoge o consume en sucursal</span>
              </div>
            </label>
          </div>
        </div>

        <!-- 3. Bloque condicional de Datos Logísticos (SOLO para 'Envío a domicilio') -->
        <div id="bloqueDatosLogisticos" class="logistics-conditional-box">
          <h3 class="checkout-section-title" style="margin-bottom: 10px;">
            <span class="checkout-section-dot" style="background-color: var(--color-terracota);"></span>
            Datos para la Entrega
          </h3>
          <div class="checkout-grid-2">
            <div class="checkout-field full-width">
              <label class="checkout-label" for="selectMunicipioCliente">Municipio de cobertura <span class="req">*</span></label>
              <select id="selectMunicipioCliente" name="municipio" class="checkout-select locked-select" required>
                <option value="San Salvador" selected>San Salvador</option>
              </select>
            </div>
            <div class="checkout-field full-width">
              <label class="checkout-label" for="inputDireccionCliente">Dirección exacta de entrega <span class="req">*</span></label>
              <input type="text" id="inputDireccionCliente" name="direccion" class="checkout-input" placeholder="Ej. Col. Escalón, Paseo General Escalón, Pasaje 3, Casa #14" required maxlength="255">
            </div>
            <div class="checkout-field full-width">
              <label class="checkout-label" for="inputReferenciaCliente">Punto de referencia <span class="req">*</span></label>
              <input type="text" id="inputReferenciaCliente" name="punto_referencia" class="checkout-input" placeholder="Ej. Frente a Plaza Futura, portón gris" required maxlength="255">
            </div>
          </div>
        </div>

        <!-- 4. Resumen de Cobro y Método de Pago -->
        <div class="checkout-section" style="margin-bottom: 0;">
          <h3 class="checkout-section-title">
            <span class="checkout-section-dot"></span>
            3. Resumen de Cobro y Método de Pago
          </h3>

          <div class="checkout-charge-summary">
            <div class="checkout-summary-row">
              <span>Subtotal de productos</span>
              <strong id="checkoutSubtotal">$0.00</strong>
            </div>
            <div class="checkout-summary-row">
              <span>Propina sugerida (10%)</span>
              <strong id="checkoutPropina">$0.00</strong>
            </div>
            <div class="checkout-summary-total">
              <span>Total a Pagar</span>
              <span class="amount" id="checkoutTotal">$0.00</span>
            </div>
          </div>

          <div class="option-pills-grid" role="radiogroup" aria-label="Método de pago">
            <label class="option-pill-card active" id="pillMetodoEfectivo" for="radioMetodoEfectivo">
              <input type="radio" id="radioMetodoEfectivo" name="metodo_pago" value="Efectivo" checked>
              <div class="option-pill-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                  <circle cx="12" cy="12" r="2"></circle>
                  <path d="M6 12h.01M18 12h.01"></path>
                </svg>
              </div>
              <div class="option-pill-text">
                <span class="option-pill-title">Efectivo</span>
                <span class="option-pill-desc">Pago contra entrega / en caja</span>
              </div>
            </label>

            <label class="option-pill-card" id="pillMetodoTarjeta" for="radioMetodoTarjeta">
              <input type="radio" id="radioMetodoTarjeta" name="metodo_pago" value="Tarjeta">
              <div class="option-pill-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
              </div>
              <div class="option-pill-text">
                <span class="option-pill-title">Tarjeta</span>
                <span class="option-pill-desc">Pago en linea</span>
              </div>
            </label>
          </div>

          <!-- Bloque Simulado de Pago con Tarjeta (Sandbox no real - RNF03) -->
          <div id="bloquePagoTarjetaSimulado" class="card-sandbox-box is-hidden">
            <div class="sandbox-header-tag">
              <span>Ingrese sus datos de pago</span>
            </div>
            <div class="checkout-grid-2">
              <div class="checkout-field full-width">
                <label class="checkout-label" for="inputTarjetaNumero">Número de tarjeta <span class="req">*</span></label>
                <input type="text" id="inputTarjetaNumero" class="checkout-input" placeholder="4532 1111 2222 8890" maxlength="19" inputmode="numeric" autocomplete="off">
              </div>
              <div class="checkout-field">
                <label class="checkout-label" for="inputTarjetaVencimiento">Vencimiento (MM/AA) <span class="req">*</span></label>
                <input type="text" id="inputTarjetaVencimiento" class="checkout-input" placeholder="12/28" maxlength="5" inputmode="numeric" autocomplete="off">
              </div>
              <div class="checkout-field">
                <label class="checkout-label" for="inputTarjetaCvv">Código CVV <span class="req">*</span></label>
                <input type="password" id="inputTarjetaCvv" class="checkout-input" placeholder="123" maxlength="4" inputmode="numeric" autocomplete="off">
              </div>
            </div>
            <div class="sandbox-abono-notice">
              Al confirmar con tarjeta, el importe total de <strong id="checkoutMontoAbonadoInfo">$0.00</strong> se registrará automáticamente como <strong>Monto Abonado</strong> (Saldo pendiente: <strong>$0.00</strong>).
            </div>
          </div>
        </div>

        <!-- Botones de acción del modal -->
        <div class="checkout-actions">
          <button type="button" id="btnCancelarCheckout" class="btn-checkout-cancel">Cancelar</button>
          <button type="submit" id="btnSubmitCheckout" class="btn-checkout-submit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>Confirmar y Pagar Pedido</span>
          </button>
        </div>

      </form>

    </div>
  </div>

  <!-- Modal: Confirmación Exitosa de Pedido y Comprobante PDF (RF10, RF15) -->
  <div id="modal-pedido-exito" class="modal-backdrop" aria-hidden="true">
    <div class="modal-dialog exito-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalExitoHeading">
      
      <div class="exito-top-banner">
        <div class="exito-check-circle" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h3 id="modalExitoHeading" class="exito-heading">¡Pedido confirmado con éxito!</h3>
        <p class="exito-subheading">Guarda o descarga tu comprobante oficial en formato PDF.</p>
      </div>

      <!-- Contenedor estructurado del Comprobante NIXGARU exportable a PDF -->
      <div id="clientTicketContainer" class="client-ticket-paper">
        
        <div class="ct-header">
          <h4 class="ct-brand">NIXGARU</h4>
          <p class="ct-slogan">"Más que café, momentos."</p>
          <p class="ct-location">San Salvador, El Salvador</p>
        </div>

        <div class="ct-meta-block">
          <div class="ct-meta-row">
            <span class="ct-meta-label">Folio de Pedido:</span>
            <span class="ct-meta-value ct-folio-badge" id="ticketFolioCliente">#NIX-00000</span>
          </div>
          <div class="ct-meta-row">
            <span class="ct-meta-label">Fecha y Hora:</span>
            <span class="ct-meta-value" id="ticketFechaHoraCliente">--/--/---- --:--</span>
          </div>
          <div class="ct-meta-row">
            <span class="ct-meta-label">Modalidad:</span>
            <span class="ct-meta-value" id="ticketModalidadCliente">Envío a domicilio</span>
          </div>
          <div class="ct-meta-row">
            <span class="ct-meta-label">Cliente:</span>
            <span class="ct-meta-value" id="ticketNombreCliente">---</span>
          </div>
          <div class="ct-meta-row">
            <span class="ct-meta-label">Teléfono:</span>
            <span class="ct-meta-value" id="ticketTelefonoCliente">---</span>
          </div>

          <!-- Datos de entrega condicionales cuando es Envío a domicilio -->
          <div id="ticketBloqueEntrega" class="ct-delivery-box">
            <div><strong>Municipio:</strong> <span id="ticketMunicipioCliente">San Salvador</span></div>
            <div><strong>Dirección:</strong> <span id="ticketDireccionCliente">---</span></div>
            <div><strong>Referencia:</strong> <span id="ticketReferenciaCliente">---</span></div>
          </div>
        </div>

        <table class="ct-items-table" aria-label="Detalle de productos del pedido">
          <thead>
            <tr>
              <th>Cant.</th>
              <th>Producto</th>
              <th style="text-align: right;">P. Unit.</th>
              <th style="text-align: right;">Subtotal</th>
            </tr>
          </thead>
          <tbody id="ticketItemsCliente">
            <!-- Filas inyectadas dinámicamente por main.js -->
          </tbody>
        </table>

        <div class="ct-totals">
          <div class="ct-total-line">
            <span>Subtotal</span>
            <span id="ticketSubtotalCliente">$0.00</span>
          </div>
          <div class="ct-total-line">
            <span>Propina (10%)</span>
            <span id="ticketPropinaCliente">$0.00</span>
          </div>
          <div class="ct-grand-total">
            <span>TOTAL GENERAL</span>
            <span id="ticketTotalCliente">$0.00</span>
          </div>
          <div class="ct-payment-breakdown">
            <div class="ct-total-line">
              <span>Método de pago:</span>
              <strong id="ticketMetodoPagoCliente">Efectivo</strong>
            </div>
            <div class="ct-total-line">
              <span>Monto abonado vía web:</span>
              <strong id="ticketMontoAbonadoCliente" style="color: #198754;">$0.00</strong>
            </div>
            <div class="ct-total-line">
              <span>Saldo pendiente:</span>
              <strong id="ticketSaldoPendienteCliente">$0.00</strong>
            </div>
          </div>
        </div>

        <div class="ct-footer">
          <p style="margin: 0; font-weight: 600;">*** Gracias por preferir NIXGARU ***</p>
          <p style="margin: 2px 0 0 0;">Presenta este comprobante al recibir o retirar tu orden.</p>
        </div>

      </div>

      <!-- Acciones del modal de éxito -->
      <div class="exito-actions-row">
        <button type="button" id="btnDescargarTicketCliente" class="btn-download-ticket">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Descargar Comprobante PDF</span>
        </button>
        <button type="button" id="btnAceptarPedidoExito" class="btn-accept-ticket">Aceptar</button>
      </div>

    </div>
  </div>

  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
