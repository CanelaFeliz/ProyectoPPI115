<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Gestión de Catálogo y Menú (admin_catalogo.php)
 * Conexión dinámica con PDO a base de datos nixgaru_db
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';

try {
    $productoModel = new Producto($pdo);
    $productos = $productoModel->obtenerTodos();
    $totalProductos = count($productos);
} catch (PDOException $e) {
    die("Error al consultar el catálogo de productos: " . $e->getMessage());
}

$pageTitle = 'NIXGARU – Gestión de Catálogo y Menú';
require_once '../layouts/sidebar.php';
?>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-content">
      <div class="admin-content-card">
        
        <!-- Encabezado de la Pantalla: Título y Botón Agregar Producto -->
        <div class="admin-content-header">
          <div class="admin-title-group">
            <h1 class="admin-page-title">Gestión de Catálogo y Menú</h1>
            <p class="admin-page-subtitle">Administra la oferta de productos, precios y disponibilidad en tiempo real</p>
          </div>
          <button class="btn-add-table" id="btnAgregarProductoCatalogo" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Agregar Nuevo Producto</span>
          </button>
        </div>

        <!-- Filtros de Categorías Tipo Píldora -->
        <div class="catalog-filters-bar">
          <button type="button" class="catalog-filter-pill active" data-category="todos">Todos</button>
          <button type="button" class="catalog-filter-pill" data-category="calientes">Bebidas calientes</button>
          <button type="button" class="catalog-filter-pill" data-category="frias">Bebidas frías</button>
          <button type="button" class="catalog-filter-pill" data-category="postres">Postres</button>
          <button type="button" class="catalog-filter-pill" data-category="platos">Platos</button>
        </div>

        <!-- Tarjeta Principal: Tabla de Catálogo -->
        <div class="admin-table-container">
          
          <div class="admin-table-header">
            <div class="table-header-left">
              <h2 class="table-card-title">Catálogo de Productos</h2>
              <p class="table-card-subtitle">Listado completo de platos y bebidas configurados para la venta</p>
            </div>
            <span class="table-count-badge" id="catalogCountBadge">Total: <?php echo $totalProductos; ?> productos mostrados</span>
          </div>

          <!-- Tabla de Datos Generada Dinámicamente con PHP Foreach -->
          <div class="table-responsive">
            <table class="admin-data-table catalog-table">
              <thead>
                <tr>
                  <th class="col-cat-photo">Fotografía</th>
                  <th class="col-cat-name">Nombre del Producto</th>
                  <th class="col-cat-desc">Descripción</th>
                  <th class="col-cat-price">Precio</th>
                  <th class="col-cat-status">Disponibilidad</th>
                  <th class="col-acciones">Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaCatalogoBody">
                
                <?php foreach ($productos as $producto): ?>
                  <?php 
                    // Mapeo amigable de categoría para los filtros
                    $catSlug = match ((int)$producto['id_categoria']) {
                        1 => 'calientes',
                        2 => 'frias',
                        3 => 'postres',
                        4 => 'platos',
                        default => 'otros'
                    };
                    $activo = (strtolower(trim($producto['estado_disponibilidad'])) === 'disponible');
                  ?>
                  <tr data-category="<?php echo $catSlug; ?>">
                    <td class="cell-cat-photo">
                      <div class="product-thumb-placeholder" title="Fotografía de <?php echo htmlspecialchars($producto['nombre_producto']); ?>">
                        <?php if (!empty($producto['fotografia'])): ?>
                          <img 
                            src="../../public/img/<?php echo htmlspecialchars($producto['fotografia']); ?>" 
                            alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>"
                            style="width: 100%; height: 100%; object-fit: cover;"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                          >
                          <svg style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                          </svg>
                        <?php else: ?>
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                          </svg>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="cell-name"><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                    <td class="cell-cat-desc"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></td>
                    <td class="cell-cat-price">
                      <span class="catalog-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                    </td>
                    <td class="cell-cat-status">
                      <div class="toggle-switch-wrapper <?php echo $activo ? 'active' : ''; ?>" data-product="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" data-id="<?php echo (int)$producto['id_producto']; ?>" data-codigo="<?php echo (int)$producto['id_producto']; ?>">
                        <button type="button" class="toggle-switch" aria-label="Cambiar disponibilidad" aria-checked="<?php echo $activo ? 'true' : 'false'; ?>">
                          <span class="toggle-knob"></span>
                        </button>
                        <span class="toggle-status-label"><?php echo htmlspecialchars($producto['estado_disponibilidad']); ?></span>
                      </div>
                    </td>
                    <td class="cell-acciones">
                      <button class="btn-action-edit" title="Editar producto" type="button">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </button>
                      <button class="btn-action-delete" title="Eliminar producto" type="button">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="3 6 5 6 21 6"></polyline>
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>

              </tbody>
            </table>
          </div>

          <!-- Pie de la Tabla -->
          <div class="admin-table-footer">
            <span>Mostrando <?php echo $totalProductos; ?> de <?php echo $totalProductos; ?> productos registrados en el menú actual</span>
          </div>

        </div>

      </div>
    </main>

  </div>

  <script src="../../public/js/main.js"></script>
</body>
</html>
