<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio
 * Pantalla: Gestión de Orden y Catálogo (gestion_orden.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db
 * ==============================================================================
 */
require_once 'config/database.php';

try {
    // Consulta SQL mediante PDO para extraer todos los productos activos (disponibilidad = 1)
    $stmt = $pdo->prepare("
        SELECT 
            p.codigo_producto,
            p.nombre,
            p.fotografia,
            p.descripcion,
            p.precio,
            p.id_categoria,
            p.disponibilidad,
            c.nombre_categoria
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.disponibilidad = 1
        ORDER BY p.id_categoria ASC, p.codigo_producto ASC
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll();

    // Consulta para los botones de filtros de categorías
    $stmtCat = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY id_categoria ASC");
    $categorias = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar el catálogo de productos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NIXGARU – Gestión de Orden | Personal de Servicio</title>
  <!-- Preconexión y carga de Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

  <!-- Encabezado / Header Personal de Servicio -->
  <header class="service-header">
    <div class="service-header-container">
      
      <!-- Logotipo NIXGARU a la izquierda -->
      <div class="brand-logo-service">
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
          <span class="logo-brand-name">NIXGARU</span>
          <span class="logo-subtext">PERSONAL DE SERVICIO</span>
        </div>
      </div>

      <!-- Centro: Píldora de Orden Activa -->
      <div class="header-center-badge">
        <div class="active-order-pill">
          <span class="order-dot"></span>
          <span class="order-pill-text">Orden Activa – Mesa 1</span>
        </div>
      </div>

      <!-- Derecha: Perfil de usuario y botón de regreso al mapa -->
      <div class="service-user-actions">
        <div class="user-profile-badge">
          <div class="user-avatar" id="avatarUsuario">CM</div>
          <div class="user-meta">
            <span class="user-name" id="nombreUsuario">Carlos Méndez</span>
            <span class="user-role" id="rolUsuario">Personal de servicio</span>
          </div>
        </div>

        <!-- Botón para regresar al mapa de mesas -->
        <button class="btn-back-tables" id="btnMapaMesas" title="Regresar al mapa de mesas" type="button">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          <span>Mapa de mesas</span>
        </button>
      </div>

    </div>
  </header>

  <!-- Layout Principal: 70% Catálogo / 30% Cuenta Activa -->
  <main class="order-layout">

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
                   data-id="<?php echo htmlspecialchars($producto['codigo_producto']); ?>"
                   data-codigo="<?php echo htmlspecialchars($producto['codigo_producto']); ?>" 
                   data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                   data-name="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                   data-precio="<?php echo htmlspecialchars($producto['precio']); ?>"
                   data-price="<?php echo htmlspecialchars($producto['precio']); ?>"
                   data-category="<?php echo $catSlug; ?>">
            
            <div class="order-product-img-box">
              <?php if (!empty($producto['fotografia'])): ?>
                <img 
                  src="IMG/<?php echo htmlspecialchars($producto['fotografia']); ?>" 
                  alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                  onerror="this.style.display='none';"
                >
              <?php endif; ?>
            </div>

            <div class="order-product-footer">
              <div class="order-product-meta">
                <h3 class="order-product-name"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                <?php if (!empty($producto['descripcion'])): ?>
                  <p class="order-product-desc"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                <?php endif; ?>
                <span class="order-product-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
              </div>

              <!-- Botón Agregar con atributos data-id, data-nombre y data-precio -->
              <button 
                class="btn-add-item" 
                type="button"
                aria-label="Agregar <?php echo htmlspecialchars($producto['nombre']); ?>"
                data-id="<?php echo htmlspecialchars($producto['codigo_producto']); ?>"
                data-codigo="<?php echo htmlspecialchars($producto['codigo_producto']); ?>" 
                data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                data-precio="<?php echo htmlspecialchars($producto['precio']); ?>"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>

          </article>
        <?php endforeach; ?>

      </div>

    </section>

    <!-- COLUMNA DERECHA (30%): Cuenta Activa de la Mesa -->
    <aside class="active-order-sidebar">
      <div class="active-order-card">
        
        <!-- Encabezado de la Cuenta -->
        <div class="active-order-header">
          <h2 class="active-order-title">Cuenta - Mesa 1</h2>
          <span class="order-count-badge" id="orderItemsBadge">0 Items</span>
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
          <button class="btn-order-update" id="btnActualizarOrden" type="button">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>Actualizar Orden</span>
          </button>

          <button class="btn-order-send-kitchen" id="btnEnviarCocina" type="button">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"></path>
              <line x1="6" y1="17" x2="18" y2="17"></line>
            </svg>
            <span>Enviar a Cocina</span>
          </button>
        </div>

      </div>
    </aside>

  </main>

  <!-- Script Principal con la lógica interactiva -->
  <script src="JS/main.js"></script>
</body>
</html>
