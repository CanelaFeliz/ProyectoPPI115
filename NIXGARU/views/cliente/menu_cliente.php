<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Vista del Cliente
 * Pantalla: Menú del Cliente (menu_cliente.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db
 * Consulta cruzada con recetas e ingredientes reales
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Mesa.php';

// Detección de la mesa del cliente mediante parámetro GET (?mesa=1 o ?mesa_id=1 por defecto)
$idMesaCliente = isset($_GET['mesa_id']) ? (int)$_GET['mesa_id'] : (isset($_GET['mesa']) ? (int)$_GET['mesa'] : 1);
if ($idMesaCliente <= 0) {
    $idMesaCliente = 1;
}

try {
    $productoModel = new Producto($pdo);
    $productos = $productoModel->obtenerActivos();

    // Consulta para los botones de filtros de categorías
    $stmtCat = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY id_categoria ASC");
    $categorias = $stmtCat->fetchAll();

    // Consulta para el selector dinámico de mesas en el carrito mediante el modelo Mesa
    $mesaModel = new Mesa($pdo);
    $mesasDisponibles = $mesaModel->obtenerTodas();
    if (empty($mesasDisponibles)) {
        for ($i = 1; $i <= 8; $i++) {
            $mesasDisponibles[] = ['id_mesa' => $i, 'numero_mesa' => (string)$i, 'estado' => 'Disponible'];
        }
    }
} catch (PDOException $e) {
    die("Error al consultar el menú de productos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NIXGARU – Cafetería | Inicio y Menú</title>
  <!-- Preconexión y carga de Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>

  <!-- Navegación Principal / Header -->
  <header class="main-header">
    <div class="header-container">
      
      <!-- Logotipo -->
      <a href="menu_cliente.php" class="brand-logo">
        <div class="logo-icon">
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
        <a href="#promociones" class="nav-link">Promociones</a>
        <a href="#nosotros" class="nav-link">Sobre nosotros</a>
      </nav>

      <!-- Acciones de Cabecera -->
      <div class="header-actions">
        <button class="btn-icon" aria-label="Buscar productos" type="button">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </button>

        <button id="cart-toggle-btn" class="btn-cart" aria-expanded="false" aria-controls="cart-sidebar" type="button">
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
               data-category="<?php echo $producto['id_categoria']; ?>"
               data-codigo="<?php echo htmlspecialchars($producto['codigo_producto']); ?>"
               data-name="<?php echo htmlspecialchars($producto['nombre']); ?>" 
               data-price="<?php echo htmlspecialchars($producto['precio']); ?>" 
               data-desc="<?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?>"
               data-foto="<?php echo htmlspecialchars($producto['fotografia'] ?? ''); ?>"
               data-ingredients="<?php echo htmlspecialchars($producto['ingredientes']); ?>">
            
            <div class="product-image-placeholder">
              <?php if (!empty($producto['fotografia'])): ?>
                <img 
                  src="../../public/img/<?php echo htmlspecialchars($producto['fotografia']); ?>" 
                  alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                  onerror="this.style.display='none';"
                >
              <?php endif; ?>
            </div>

            <div class="product-info">
              <h3 class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
              <p class="product-description"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Delicioso producto recién preparado.'); ?></p>
              <div class="product-footer">
                <span class="product-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                <button class="btn-select" type="button">Seleccionar</button>
              </div>
            </div>

          </div>
        <?php endforeach; ?>

      </section>

    </main>

    <!-- Barra Lateral del Carrito ("Tu carrito") -->
    <aside id="cart-sidebar" class="cart-sidebar hidden" aria-hidden="true">
      <div class="cart-card">
        
        <!-- Cabecera del Carrito -->
        <div class="cart-header">
          <div class="cart-title-wrapper">
            <h2 class="cart-title">Tu carrito <span class="cart-table-indicator" id="cartTableIndicator" style="font-size: 13px; font-weight: 500; color: #6C757D;">(Mesa <?php echo $idMesaCliente; ?>)</span></h2>
            <span class="cart-badge" id="cart-badge">0 Items</span>
          </div>
          <button id="cart-close-btn" class="cart-close-btn" aria-label="Cerrar carrito" type="button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Selector Rápido de Mesa -->
        <div class="cart-table-selection" style="padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
          <label for="selectMesaCliente" style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 600; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            <span>Mesa para tu orden:</span>
            <span style="font-size: 11px; font-weight: normal; color: #6c757d;">Selecciona o cambia</span>
          </label>
          <div style="position: relative;">
            <select id="selectMesaCliente" style="width: 100%; padding: 8px 32px 8px 12px; font-size: 14px; font-weight: 600; color: #212529; background-color: #ffffff; border: 1.5px solid #dee2e6; border-radius: 8px; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none; -moz-appearance: none; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: border-color 0.2s, box-shadow 0.2s;">
              <?php foreach ($mesasDisponibles as $m): ?>
                <option value="<?php echo htmlspecialchars($m['numero_mesa']); ?>" <?php echo ((int)$m['numero_mesa'] === (int)$idMesaCliente) ? 'selected' : ''; ?>>
                  Mesa <?php echo htmlspecialchars($m['numero_mesa']); ?> <?php echo (!empty($m['estado']) && $m['estado'] !== 'Disponible') ? ' (' . htmlspecialchars($m['estado']) . ')' : ''; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #6c757d;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
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
            <span class="total-amount" id="cart-total">$0.00</span>
          </div>
          <button class="btn-confirm-order" id="btnConfirmarPedido" type="button" data-mesa="<?php echo $idMesaCliente; ?>">Confirmar Pedido</button>
        </div>

      </div>
    </aside>

    <!-- Overlay oscuro para dispositivos móviles cuando el carrito está abierto -->
    <div id="cart-backdrop" class="cart-backdrop"></div>

  </div>

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

  <!-- Modal: Confirmación Exitosa de Pedido -->
  <div id="modal-pedido-exito" class="modal-backdrop" aria-hidden="true">
    <div class="modal-dialog modal-dialog-success" role="dialog" aria-modal="true" style="max-width: 440px; text-align: center; padding: 36px 28px; background: #ffffff; border-radius: 20px; box-shadow: 0 20px 45px rgba(0,0,0,0.25);">
      <div style="width: 72px; height: 72px; background: rgba(25, 135, 84, 0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: #198754;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      </div>
      <h3 style="font-size: 22px; font-weight: 700; color: #212529; margin: 0 0 10px 0;">¡Pedido recibido con éxito!</h3>
      <p style="font-size: 15px; color: #6C757D; line-height: 1.5; margin: 0 0 24px 0;">
        Su orden ha sido enviada a cocina.<br>
        <span id="modalExitoMesaText" style="font-weight: 600; color: #198754;">Mesa <?php echo $idMesaCliente; ?></span>
      </p>
      <button type="button" id="btnAceptarPedidoExito" style="background-color: #0D6EFD; color: #ffffff; font-weight: 600; font-size: 15px; padding: 12px 36px; border: none; border-radius: 10px; cursor: pointer; transition: all 0.2s ease;">Aceptar</button>
    </div>
  </div>

  <script src="../../public/js/main.js"></script>
</body>
</html>
