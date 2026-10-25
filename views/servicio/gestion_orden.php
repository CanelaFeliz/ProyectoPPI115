<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio
 * Pantalla: Gestión de Orden y Catálogo (gestion_orden.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Categoria.php';
require_once __DIR__ . '/../../models/Mesa.php';

// Detección de la mesa seleccionada mediante parámetro GET (?mesa_id=1 por defecto o ?mesa=1)
$idMesaSeleccionada = isset($_GET['mesa_id']) ? $_GET['mesa_id'] : (isset($_GET['mesa']) ? $_GET['mesa'] : 1);

try {
    $productoModel = new Producto($pdo);
    $productos = $productoModel->obtenerActivos();

    // Consulta para los botones de filtros de categorías mediante el modelo Categoria
    $categoriaModel = new Categoria($pdo);
    $categorias = $categoriaModel->obtenerTodas();
} catch (PDOException $e) {
    die("Error al consultar el catálogo de productos: " . $e->getMessage());
}

// Variables dinámicas para el layout del encabezado
$nombreUsuario = 'Carlos Méndez';
$rolUsuario = 'Personal de servicio';
$pageTitle = 'NIXGARU – Gestión de Orden | Personal de Servicio';
$headerTitle = 'Orden Activa - Mesa ' . $idMesaSeleccionada;

require_once '../layouts/header.php';
?>

  <!-- Layout Principal: 70% Catálogo / 30% Cuenta Activa -->
  <main class="order-layout" id="orderLayout">

    <!-- COLUMNA IZQUIERDA (70%): Catálogo de Productos -->
    <section class="catalog-panel">
      
      <!-- Fila Superior de Filtros Píldora -->
      <div class="order-categories-bar">
        <button type="button" class="order-category-pill active" data-category="todos">Todos</button>
        <button type="button" class="order-category-pill" data-category="bebidas-calientes">Bebidas calientes</button>
        <button type="button" class="order-category-pill" data-category="bebidas-frias">Bebidas frías</button>
        <button type="button" class="order-category-pill" data-category="postres">Postres</button>
        <button type="button" class="order-category-pill" data-category="platos">Platos</button>
      </div>

      <!-- Cuadrícula de Productos Generada Dinámicamente con Foreach (Productos reales desde BD con disponibilidad = 1) -->
      <div class="order-products-grid">

        <?php foreach ($productos as $producto): ?>
          <?php
            // Mapeo amigable de categoría para los filtros según id_categoria
            $catSlug = match ((int)$producto['id_categoria']) {
                1 => 'bebidas-calientes',
                2 => 'bebidas-frias',
                3 => 'postres',
                4 => 'platos',
                default => 'otros'
            };
          ?>
          <article class="order-product-card" 
                   data-id="<?php echo (int)$producto['id_producto']; ?>"
                   data-codigo="<?php echo (int)$producto['id_producto']; ?>" 
                   data-nombre="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
                   data-name="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
                   data-precio="<?php echo htmlspecialchars((string)$producto['precio']); ?>"
                   data-price="<?php echo htmlspecialchars((string)$producto['precio']); ?>"
                   data-category="<?php echo $catSlug; ?>">
            
            <div class="order-product-img-box"></div>

            <div class="order-product-info">
              <h3 class="order-product-name"><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
              <div class="order-product-bottom">
                <span class="order-product-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                <div class="card-quantity-control" data-id="<?php echo (int)$producto['id_producto']; ?>" data-codigo="<?php echo (int)$producto['id_producto']; ?>">
                  <button class="card-qty-btn card-qty-minus" type="button" aria-label="Disminuir cantidad">-</button>
                  <span class="card-qty-value">0</span>
                  <button class="card-qty-btn card-qty-plus" type="button" aria-label="Aumentar cantidad">+</button>
                </div>
              </div>
            </div>

          </article>
        <?php endforeach; ?>

      </div>

    </section>

    <!-- COLUMNA DERECHA (30%): Cuenta Activa de la Mesa -->
    <aside class="active-order-sidebar" id="activeOrderSidebar" aria-label="Detalle de cuenta de la mesa">
      <div class="active-order-card">
        
        <!-- Encabezado de la Cuenta -->
        <div class="active-order-header">
          <div class="active-order-header-left" style="display: inline-flex; align-items: center; gap: 10px;">
            <h2 class="active-order-title" data-mesa="<?php echo $idMesaSeleccionada; ?>">Cuenta - Mesa <?php echo $idMesaSeleccionada; ?></h2>
            <span class="order-count-badge" id="orderItemsBadge">0 Items</span>
          </div>

          <!-- Botón de cierre con 'X' visible en vista móvil/drawer -->
          <button class="cuenta-close-btn" id="activeOrderCloseBtn" type="button" aria-label="Cerrar detalle de cuenta" title="Cerrar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Lista de Productos Seleccionados (Llenado dinámico mediante JS al pulsar '+') -->
        <div class="active-order-items" id="activeOrderList">
          <!-- Las filas se agregan automáticamente con el botón '+' -->
        </div>

        <!-- Resumen de Costos: Subtotal, Propina (10%) y Total -->
        <div class="active-order-summary">
          <div class="summary-cost-line">
            <span>Subtotal</span>
            <span id="orderSubtotal">$0.00 USD</span>
          </div>
          <div class="summary-cost-line">
            <span>Propina (10%)</span>
            <span id="orderTip">$0.00 USD</span>
          </div>
          <div class="summary-cost-line total">
            <span class="total-label">Total</span>
            <span class="total-val" id="orderTotal">$0.00 USD</span>
          </div>
        </div>

        <!-- Botones de Acción de Bloque Inferior -->
        <div class="active-order-actions">
          <button class="btn-order-update" id="btnActualizarOrden" type="button" data-mesa="<?php echo $idMesaSeleccionada; ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>Actualizar Orden</span>
          </button>

          <button class="btn-order-send-kitchen" id="btnEnviarCocina" type="button" data-mesa="<?php echo $idMesaSeleccionada; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"></path>
              <line x1="6" y1="17" x2="18" y2="17"></line>
            </svg>
            <span>Enviar a Cocina</span>
          </button>
        </div>

      </div>
    </aside>

    <!-- Overlay / Fondo oscuro y difuminado para vista móvil/drawer -->
    <div class="cuenta-backdrop" id="activeOrderBackdrop" aria-hidden="true"></div>

  </main>

  <!-- Script Principal con la lógica interactiva -->
  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
