<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Gestión de Catálogo y Menú (admin_catalogo.php)
 * Conexión dinámica con PDO a base de datos nixgaru_db (RF04)
 * ==============================================================================
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (empty($_SESSION['id_usuario'])) {
    header('Location: /NIXGARU/index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Categoria.php';

try {
    $productoModel = new Producto($pdo);
    $productos = $productoModel->obtenerTodos();
    $totalProductos = count($productos);

    $categoriaModel = new Categoria($pdo);
    $categorias = $categoriaModel->obtenerTodas();
    if (empty($categorias)) {
        $categorias = [
            ['id_categoria' => 1, 'nombre_categoria' => 'Bebidas calientes'],
            ['id_categoria' => 2, 'nombre_categoria' => 'Bebidas frías'],
            ['id_categoria' => 3, 'nombre_categoria' => 'Postres'],
            ['id_categoria' => 4, 'nombre_categoria' => 'Platos'],
        ];
    }
} catch (PDOException $e) {
    die("Error al consultar el catálogo de productos: " . $e->getMessage());
}

$pageTitle = 'NIXGARU – Gestión de Catálogo y Menú';
require_once __DIR__ . '/../layouts/sidebar.php';
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
                    $idProd = (int)$producto['id_producto'];
                    $idCatProd = (int)$producto['id_categoria'];
                    $nombreProd = (string)$producto['nombre_producto'];
                    $descProd = (string)($producto['descripcion'] ?? '');
                    $precioProd = number_format((float)$producto['precio'], 2, '.', '');
                    $dispProd = (string)$producto['estado_disponibilidad'];
                    $fotoProd = (string)($producto['fotografia'] ?? '');

                    // Mapeo amigable de categoría para los filtros
                    $catSlug = match ($idCatProd) {
                        1 => 'calientes',
                        2 => 'frias',
                        3 => 'postres',
                        4 => 'platos',
                        default => 'otros'
                    };
                    $activo = (strtolower(trim($dispProd)) === 'disponible');
                  ?>
                  <tr
                    data-category="<?php echo $catSlug; ?>"
                    data-id="<?php echo $idProd; ?>"
                    data-nombre="<?php echo htmlspecialchars($nombreProd); ?>"
                    data-descripcion="<?php echo htmlspecialchars($descProd); ?>"
                    data-precio="<?php echo htmlspecialchars($precioProd); ?>"
                    data-id-categoria="<?php echo $idCatProd; ?>"
                    data-disponibilidad="<?php echo htmlspecialchars($dispProd); ?>"
                    data-fotografia="<?php echo htmlspecialchars($fotoProd); ?>"
                  >
                    <td class="cell-cat-photo">
                      <div class="product-thumb-placeholder" title="Fotografía de <?php echo htmlspecialchars($nombreProd); ?>">
                        <?php if ($fotoProd !== ''): ?>
                          <img 
                            src="../../public/img/<?php echo htmlspecialchars($fotoProd); ?>" 
                            alt="<?php echo htmlspecialchars($nombreProd); ?>"
                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 9px;"
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
                    <td class="cell-name"><?php echo htmlspecialchars($nombreProd); ?></td>
                    <td class="cell-cat-desc"><?php echo htmlspecialchars($descProd !== '' ? $descProd : 'Sin descripción'); ?></td>
                    <td class="cell-cat-price">
                      <span class="catalog-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                    </td>
                    <td class="cell-cat-status">
                      <div class="toggle-switch-wrapper <?php echo $activo ? 'active' : ''; ?>" data-product="<?php echo htmlspecialchars($nombreProd); ?>" data-id="<?php echo $idProd; ?>" data-codigo="<?php echo $idProd; ?>">
                        <button type="button" class="toggle-switch" aria-label="Cambiar disponibilidad" aria-checked="<?php echo $activo ? 'true' : 'false'; ?>">
                          <span class="toggle-knob"></span>
                        </button>
                        <span class="toggle-status-label"><?php echo htmlspecialchars($dispProd); ?></span>
                      </div>
                    </td>
                    <td class="cell-acciones">
                      <button
                        class="btn-action-edit"
                        title="Editar producto"
                        type="button"
                        data-id="<?php echo $idProd; ?>"
                        data-nombre="<?php echo htmlspecialchars($nombreProd); ?>"
                        data-descripcion="<?php echo htmlspecialchars($descProd); ?>"
                        data-precio="<?php echo htmlspecialchars($precioProd); ?>"
                        data-id-categoria="<?php echo $idCatProd; ?>"
                        data-disponibilidad="<?php echo htmlspecialchars($dispProd); ?>"
                        data-fotografia="<?php echo htmlspecialchars($fotoProd); ?>"
                      >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </button>
                      <button
                        class="btn-action-delete"
                        title="Eliminar producto"
                        type="button"
                        data-id="<?php echo $idProd; ?>"
                        data-nombre="<?php echo htmlspecialchars($nombreProd); ?>"
                        data-descripcion="<?php echo htmlspecialchars($descProd); ?>"
                        data-precio="<?php echo htmlspecialchars($precioProd); ?>"
                        data-id-categoria="<?php echo $idCatProd; ?>"
                        data-disponibilidad="<?php echo htmlspecialchars($dispProd); ?>"
                        data-fotografia="<?php echo htmlspecialchars($fotoProd); ?>"
                      >
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
            <span id="catalogFooterCountText">Mostrando <?php echo $totalProductos; ?> de <?php echo $totalProductos; ?> productos registrados en el menú actual</span>
          </div>

        </div>

      </div>
    </main>

  </div>

  <!-- MODAL REUTILIZABLE: AGREGAR / EDITAR PRODUCTO DEL CATÁLOGO (RF04) -->
  <div id="modalProductoCatalogo" class="modal" aria-hidden="true">
    <div class="modal-card" style="max-width: 540px; max-height: 92vh; overflow-y: auto;">
      <div class="modal-header">
        <h3 class="modal-title" id="tituloModalProductoCatalogo">Agregar Nuevo Producto</h3>
        <p class="modal-subtitle" id="subtituloModalProductoCatalogo">Configura los detalles comerciales, categoría y disponibilidad en el menú.</p>
      </div>

      <div id="errorModalProductoCatalogo" role="alert" style="display: none; background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; font-weight: 600; margin-bottom: 16px;"></div>

      <form id="formProductoCatalogo" class="modal-form" enctype="multipart/form-data">
        <input type="hidden" id="inputIdProductoCatalogo" name="id_producto" value="">

        <!-- Nombre del Producto -->
        <div class="form-group">
          <label for="inputNombreProductoCatalogo" class="form-label">Nombre del producto</label>
          <input type="text" id="inputNombreProductoCatalogo" name="nombre_producto" class="form-input" placeholder="Ej. Cappuccino Artesanal" required>
        </div>

        <!-- Categoría y Precio en fila doble -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label for="selectCategoriaProductoCatalogo" class="form-label">Categoría</label>
            <div class="select-wrapper">
              <select id="selectCategoriaProductoCatalogo" name="id_categoria" class="form-select" required>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?php echo (int)$cat['id_categoria']; ?>">
                    <?php echo htmlspecialchars((string)$cat['nombre_categoria']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="inputPrecioProductoCatalogo" class="form-label">Precio (USD)</label>
            <input type="number" id="inputPrecioProductoCatalogo" name="precio" class="form-input" step="0.01" min="0.01" placeholder="3.50" required>
          </div>
        </div>

        <!-- Descripción -->
        <div class="form-group">
          <label for="inputDescripcionProductoCatalogo" class="form-label">Descripción</label>
          <textarea id="inputDescripcionProductoCatalogo" name="descripcion" class="form-input" rows="3" style="resize: vertical;" placeholder="Describe los ingredientes o preparación destacada..." required></textarea>
        </div>

        <!-- Fotografía (Subida de archivo o referencia en public/img/) -->
        <div class="form-group">
          <label for="inputArchivoFotoCatalogo" class="form-label">Fotografía (subir imagen opcional)</label>
          <input type="file" id="inputArchivoFotoCatalogo" name="imagen" class="form-input" accept=".jpg,.jpeg,.png,.webp,.gif" style="padding: 9px 12px;">
          <input type="text" id="inputNombreFotoCatalogo" name="fotografia" class="form-input" placeholder="O nombre de archivo existente en public/img/ (ej. latte.jpg)" style="margin-top: 6px; font-size: 0.82rem; padding: 9px 12px;">
        </div>

        <!-- Disponibilidad -->
        <div class="form-group">
          <label for="selectDisponibilidadProductoCatalogo" class="form-label">Estado de disponibilidad</label>
          <div class="select-wrapper">
            <select id="selectDisponibilidadProductoCatalogo" name="estado_disponibilidad" class="form-select">
              <option value="Disponible">Disponible</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" id="btnCancelarModalProducto">Cancelar</button>
          <button type="submit" class="btn-modal-save" id="btnGuardarProductoCatalogo">Guardar Producto</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
