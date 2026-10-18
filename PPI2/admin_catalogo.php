<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Gestión de Catálogo y Menú (admin_catalogo.php)
 * Conexión dinámica con PDO a base de datos nixgaru_db
 * ==============================================================================
 */
require_once 'config/database.php';

try {
    // Consulta con JOIN para traer todos los productos junto con su categoría
    $stmt = $pdo->prepare("
        SELECT 
            p.codigo_producto,
            p.nombre,
            p.descripcion,
            p.precio,
            p.fotografia,
            p.disponibilidad,
            p.id_categoria,
            c.nombre_categoria
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        ORDER BY p.id_categoria ASC, p.codigo_producto ASC
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll();
    $totalProductos = count($productos);
} catch (PDOException $e) {
    die("Error al consultar el catálogo de productos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NIXGARU – Gestión de Catálogo y Menú</title>
  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="admin-body">

  <div class="admin-layout">

    <!-- BARRA LATERAL (SIDEBAR ADMIN OSCURA #382823) -->
    <aside class="admin-sidebar">
      
      <!-- Logotipo Admin -->
      <div class="admin-brand">
        <div class="admin-brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </div>
        <div class="admin-brand-info">
          <span class="admin-brand-title">NIXGARU</span>
          <span class="admin-brand-sub">ADMINISTRACION</span>
        </div>
      </div>

      <!-- Menú de Navegación del Administrador -->
      <nav class="admin-nav">
        <!-- Catálogo y Menú (ACTIVO) -->
        <a href="admin_catalogo.php" class="admin-nav-item active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span>Catálogo y Menú</span>
        </a>

        <!-- Gestión de Mesas -->
        <a href="admin_mesas.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3h18v18H3zM3 9h18M9 21V9"></path>
          </svg>
          <span>Gestión de Mesas</span>
        </a>

        <!-- Control de Inventarios -->
        <a href="admin_inventario.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          <span>Control de Inventarios</span>
        </a>

        <!-- Cuentas de Personal -->
        <a href="admin_personal.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span>Cuentas de Personal</span>
        </a>

        <!-- Análisis y Reportes -->
        <a href="admin_dashboard.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          <span>Análisis y Reportes</span>
        </a>
      </nav>

      <!-- Perfil de Administrador en el pie de la barra lateral -->
      <div class="admin-sidebar-profile">
        <div class="admin-profile-avatar">ML</div>
        <div class="admin-profile-meta">
          <span class="admin-profile-name">María López</span>
          <span class="admin-profile-role">Administradora</span>
        </div>
      </div>

    </aside>

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
                    $activo = ((int)$producto['disponibilidad'] === 1);
                  ?>
                  <tr data-category="<?php echo $catSlug; ?>">
                    <td class="cell-cat-photo">
                      <div class="product-thumb-placeholder" title="Fotografía de <?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php if (!empty($producto['fotografia'])): ?>
                          <img 
                            src="IMG/<?php echo htmlspecialchars($producto['fotografia']); ?>" 
                            alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
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
                    <td class="cell-name"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td class="cell-cat-desc"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></td>
                    <td class="cell-cat-price">
                      <span class="catalog-price">$<?php echo number_format((float)$producto['precio'], 2); ?></span>
                    </td>
                    <td class="cell-cat-status">
                      <div class="toggle-switch-wrapper <?php echo $activo ? 'active' : ''; ?>" data-product="<?php echo htmlspecialchars($producto['nombre']); ?>" data-codigo="<?php echo htmlspecialchars($producto['codigo_producto']); ?>">
                        <button type="button" class="toggle-switch" aria-label="Cambiar disponibilidad" aria-checked="<?php echo $activo ? 'true' : 'false'; ?>">
                          <span class="toggle-knob"></span>
                        </button>
                        <span class="toggle-status-label"><?php echo $activo ? 'Activo' : 'Inactivo'; ?></span>
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

  <script src="JS/main.js"></script>
</body>
</html>
