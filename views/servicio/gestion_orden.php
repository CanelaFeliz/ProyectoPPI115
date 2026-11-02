<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio y Cajero
 * Pantalla: Gestión de Orden y Catálogo (gestion_orden.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db
 * Requerimientos: RNF04, RF06, RF07, RF09
 * ==============================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    }
}

if (empty($_SESSION['id_usuario'])) {
    header('Location: /NIXGARU/index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Categoria.php';
require_once __DIR__ . '/../../models/Mesa.php';
require_once __DIR__ . '/../../models/Pedido.php';

// Detección de la mesa seleccionada mediante parámetro GET (?mesa_id=1 por defecto o ?mesa=1)
$idMesaSeleccionada = isset($_GET['mesa_id']) ? $_GET['mesa_id'] : (isset($_GET['mesa']) ? $_GET['mesa'] : 1);

try {
    $mesaModel = new Mesa($pdo);
    $mesaInfo = $mesaModel->obtenerPorId($idMesaSeleccionada);
    $idMesaReal = $mesaInfo ? (int)$mesaInfo['id_mesa'] : (int)$idMesaSeleccionada;
    $numeroMesa = $mesaInfo ? (string)$mesaInfo['numero_mesa'] : (string)$idMesaSeleccionada;

    $productoModel = new Producto($pdo);
    $productos = $productoModel->obtenerActivos();

    $categoriaModel = new Categoria($pdo);
    $categorias = $categoriaModel->obtenerTodas();

    // Precarga de la cuenta activa real de la mesa desde MySQL (RF07, RF09)
    $pedidoModel = new Pedido($pdo);
    $detallesOrdenActiva = $pedidoModel->obtenerCuentaActivaPorMesa($idMesaReal);

    $subtotalOrden = 0.00;
    $montoAbonadoOrden = 0.00;
    $totalItemsOrden = 0;
    $idPedidoActivo = 0;
    $tipoOrdenActiva = 'Consumo en local';
    $cantidadesPorProducto = [];

    foreach ($detallesOrdenActiva as $det) {
        $cantLinea = (int)$det['cantidad'];
        $subLinea = (float)$det['subtotal'];
        $idProdDet = (int)$det['id_producto'];

        $subtotalOrden += $subLinea;
        $totalItemsOrden += $cantLinea;
        $cantidadesPorProducto[$idProdDet] = ($cantidadesPorProducto[$idProdDet] ?? 0) + $cantLinea;

        if ($idPedidoActivo === 0 && !empty($det['id_pedido'])) {
            $idPedidoActivo = (int)$det['id_pedido'];
            $montoAbonadoOrden = round((float)($det['monto_abonado'] ?? 0.00), 2);
            $tipoOrdenActiva = (string)($det['tipo_orden'] ?? 'Consumo en local');
        }
    }

    $subtotalOrden = round($subtotalOrden, 2);
    $propinaOrden = round($subtotalOrden * 0.10, 2);
    $totalOrden = round($subtotalOrden + $propinaOrden, 2);
    $saldoPendienteOrden = max(0.00, round($totalOrden - $montoAbonadoOrden, 2));
} catch (PDOException $e) {
    die("Error al consultar el catálogo y la cuenta activa: " . $e->getMessage());
}

// Variables dinámicas para el layout del encabezado (sesión real resuelta en header.php)
$currentPage = 'gestion_orden.php';
$nombreUsuario = $_SESSION['nombre'] ?? 'Carlos Méndez';
$rolUsuario = $_SESSION['rol'] ?? 'Personal de servicio';
$pageTitle = 'NIXGARU – Gestión de Orden | Personal de Servicio';
$headerTitle = 'Orden Activa - Mesa ' . $numeroMesa;

require_once __DIR__ . '/../layouts/header.php';
?>

  <!-- Layout Principal: 70% Catálogo / 30% Cuenta Activa -->
  <main class="order-layout" id="orderLayout">

    <!-- COLUMNA IZQUIERDA (70%): Catálogo de Productos -->
    <section class="catalog-panel">
      
      <!-- Fila Superior de Filtros Píldora -->
      <div class="order-categories-bar">
        <button type="button" class="order-category-pill active" data-category="todos">Todos</button>
        <?php if (!empty($categorias)): ?>
          <?php foreach ($categorias as $cat): ?>
            <?php
              $catSlugBar = match ((int)$cat['id_categoria']) {
                  1 => 'bebidas-calientes',
                  2 => 'bebidas-frias',
                  3 => 'postres',
                  4 => 'platos',
                  default => 'cat-' . (int)$cat['id_categoria'],
              };
            ?>
            <button type="button"
                    class="order-category-pill"
                    data-category="<?php echo htmlspecialchars($catSlugBar); ?>"
                    data-category-id="<?php echo (int)$cat['id_categoria']; ?>">
              <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
            </button>
          <?php endforeach; ?>
        <?php else: ?>
          <button type="button" class="order-category-pill" data-category="bebidas-calientes">Bebidas calientes</button>
          <button type="button" class="order-category-pill" data-category="bebidas-frias">Bebidas frías</button>
          <button type="button" class="order-category-pill" data-category="postres">Postres</button>
          <button type="button" class="order-category-pill" data-category="platos">Platos</button>
        <?php endif; ?>
      </div>

      <!-- Cuadrícula de Productos Generada Dinámicamente con Foreach (Productos reales desde BD disponibles) -->
      <div class="order-products-grid">

        <?php foreach ($productos as $producto): ?>
          <?php
            $idProdCard = (int)$producto['id_producto'];
            $catIdCard = (int)($producto['id_categoria'] ?? 0);
            $catSlug = match ($catIdCard) {
                1 => 'bebidas-calientes',
                2 => 'bebidas-frias',
                3 => 'postres',
                4 => 'platos',
                default => 'cat-' . $catIdCard,
            };
            $cantInicialProd = $cantidadesPorProducto[$idProdCard] ?? 0;
            $fotoProd = trim((string)($producto['fotografia'] ?? ''));
            $rutaFoto = '';
            if ($fotoProd !== '') {
                if (str_starts_with($fotoProd, 'http://') || str_starts_with($fotoProd, 'https://') || str_starts_with($fotoProd, '/')) {
                    $rutaFoto = $fotoProd;
                } else {
                    $rutaFoto = '../../public/img/' . $fotoProd;
                }
            }
          ?>
          <article class="order-product-card" 
                   data-id="<?php echo $idProdCard; ?>"
                   data-codigo="<?php echo $idProdCard; ?>" 
                   data-nombre="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
                   data-name="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
                   data-precio="<?php echo htmlspecialchars((string)$producto['precio']); ?>"
                   data-price="<?php echo htmlspecialchars((string)$producto['precio']); ?>"
                   data-category="<?php echo htmlspecialchars($catSlug); ?>"
                   data-category-id="<?php echo $catIdCard; ?>">
            
            <div class="order-product-img-box">
              <?php if ($rutaFoto !== ''): ?>
                <img src="<?php echo htmlspecialchars($rutaFoto); ?>"
                     alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>"
                     onerror="this.style.display='none';">
              <?php endif; ?>
            </div>

            <div class="order-product-info">
              <h3 class="order-product-name"><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
              <div class="order-product-bottom">
                <span class="order-product-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                <div class="card-quantity-control" data-id="<?php echo $idProdCard; ?>" data-codigo="<?php echo $idProdCard; ?>">
                  <button class="card-qty-btn card-qty-minus" type="button" aria-label="Disminuir cantidad">-</button>
                  <span class="card-qty-value"><?php echo (int)$cantInicialProd; ?></span>
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
          <div class="active-order-header-left" style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 class="active-order-title" data-mesa="<?php echo (int)$idMesaReal; ?>" data-numero-mesa="<?php echo htmlspecialchars($numeroMesa); ?>">
              Cuenta - Mesa <?php echo htmlspecialchars($numeroMesa); ?>
            </h2>
            <span class="order-count-badge" id="orderItemsBadge"><?php echo (int)$totalItemsOrden; ?> Items</span>
          </div>

          <!-- Botón de cierre con 'X' visible en vista móvil/drawer -->
          <button class="cuenta-close-btn" id="activeOrderCloseBtn" type="button" aria-label="Cerrar detalle de cuenta" title="Cerrar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <?php if ($montoAbonadoOrden > 0): ?>
          <div style="background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 10px; padding: 8px 12px; font-size: 0.76rem; font-weight: 600; margin-bottom: 12px; flex-shrink: 0;">
            Orden Web vinculada (<?php echo htmlspecialchars($tipoOrdenActiva); ?>) · Abono registrado: $<?php echo number_format($montoAbonadoOrden, 2); ?>
          </div>
        <?php endif; ?>

        <!-- Lista de Productos Seleccionados (Precargada desde MySQL + editable en JS) -->
        <div class="active-order-items"
             id="activeOrderList"
             data-mesa="<?php echo (int)$idMesaReal; ?>"
             data-numero-mesa="<?php echo htmlspecialchars($numeroMesa); ?>"
             data-id-pedido="<?php echo (int)$idPedidoActivo; ?>"
             data-monto-abonado="<?php echo number_format($montoAbonadoOrden, 2, '.', ''); ?>"
             data-server-preloaded="<?php echo !empty($detallesOrdenActiva) ? '1' : '0'; ?>">
          <?php foreach ($detallesOrdenActiva as $itemActivo): ?>
            <?php
              $idProdItem = (int)$itemActivo['id_producto'];
              $cantItem = (int)$itemActivo['cantidad'];
              $precioUnitItem = round((float)$itemActivo['precio'], 2);
              $subItem = round((float)$itemActivo['subtotal'], 2);
            ?>
            <div class="active-order-item"
                 data-id="<?php echo $idProdItem; ?>"
                 data-unit-price="<?php echo number_format($precioUnitItem, 2, '.', ''); ?>">
              <div class="active-item-info">
                <h4 class="active-item-title"><?php echo htmlspecialchars($itemActivo['nombre_producto']); ?></h4>
                <span class="active-item-price">$<?php echo number_format($subItem, 2); ?></span>
              </div>
              <div class="active-item-controls">
                <button class="qty-circle-btn" type="button" data-action="decrease" aria-label="Disminuir">-</button>
                <span class="qty-circle-value"><?php echo $cantItem; ?></span>
                <button class="qty-circle-btn" type="button" data-action="increase" aria-label="Aumentar">+</button>
                <button class="btn-remove-item" type="button" data-action="delete" aria-label="Eliminar producto">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Resumen de Costos: Subtotal, Propina (10%), Total, Monto Abonado (Web) y Saldo Pendiente (RF07, RF09) -->
        <div class="active-order-summary">
          <div class="summary-cost-line">
            <span><?php echo $montoAbonadoOrden > 0 ? 'Nuevo Subtotal' : 'Subtotal'; ?></span>
            <span id="orderSubtotal">$<?php echo number_format($subtotalOrden, 2); ?> USD</span>
          </div>
          <div class="summary-cost-line">
            <span>Propina (10%)</span>
            <span id="orderTip">$<?php echo number_format($propinaOrden, 2); ?> USD</span>
          </div>
          <div class="summary-cost-line total">
            <span class="total-label">Total</span>
            <span class="total-val" id="orderTotal">$<?php echo number_format($totalOrden, 2); ?> USD</span>
          </div>

          <div class="summary-cost-line" id="filaOrderAbono" style="<?php echo $montoAbonadoOrden > 0 ? 'display: flex;' : 'display: none;'; ?> color: #059669; font-weight: 600;">
            <span>Monto Abonado (Web)</span>
            <span id="orderAbono">-$<?php echo number_format($montoAbonadoOrden, 2); ?> USD</span>
          </div>
          <div class="summary-cost-line" id="filaOrderSaldo" style="<?php echo $montoAbonadoOrden > 0 ? 'display: flex;' : 'display: none;'; ?> font-weight: 800; color: var(--color-rojo-frambuesa); border-top: 1px dashed var(--color-borde-suave); padding-top: 8px; margin-top: 2px;">
            <span>Saldo Pendiente</span>
            <span id="orderSaldo">$<?php echo number_format($saldoPendienteOrden, 2); ?> USD</span>
          </div>
        </div>

        <!-- Botones de Acción de Bloque Inferior -->
        <div class="active-order-actions">
          <button class="btn-order-update"
                  id="btnActualizarOrden"
                  type="button"
                  data-mesa="<?php echo (int)$idMesaReal; ?>"
                  data-numero-mesa="<?php echo htmlspecialchars($numeroMesa); ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>Actualizar Orden</span>
          </button>

          <button class="btn-order-send-kitchen"
                  id="btnEnviarCocina"
                  type="button"
                  data-mesa="<?php echo (int)$idMesaReal; ?>"
                  data-numero-mesa="<?php echo htmlspecialchars($numeroMesa); ?>">
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
