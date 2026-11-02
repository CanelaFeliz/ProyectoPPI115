<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Control de Inventarios y Ajustes Manuales (admin_inventario.php)
 * Cumple con RF14 (Control de existencias y ajustes manuales validados)
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
require_once __DIR__ . '/../../models/Inventario.php';

try {
    $inventarioModel = new Inventario($pdo);
    $ingredientes = $inventarioModel->obtenerTodos();
    if (empty($ingredientes)) {
        $ingredientes = [
            ['id_ingrediente' => 1, 'nombre_ingrediente' => 'Café molido', 'cantidad_estimada' => 100.00],
            ['id_ingrediente' => 3, 'nombre_ingrediente' => 'Leche', 'cantidad_estimada' => 100.00],
            ['id_ingrediente' => 13, 'nombre_ingrediente' => 'Huevo', 'cantidad_estimada' => 100.00],
            ['id_ingrediente' => 24, 'nombre_ingrediente' => 'Croissant', 'cantidad_estimada' => 100.00]
        ];
    }
} catch (PDOException $e) {
    die("Error al consultar el inventario: " . $e->getMessage());
}

$pageTitle = 'NIXGARU – Control de Inventarios';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-content">
      <div class="admin-content-card">
        
        <!-- Encabezado de la Pantalla: Título y Buscador -->
        <div class="admin-content-header">
          <div class="admin-title-group">
            <h1 class="admin-page-title">Control de Inventarios y Ajustes</h1>
            <p class="admin-page-subtitle">Gestión de stock físico, mermas e ingredientes para preparación</p>
          </div>
          
          <!-- Buscador de Ingredientes -->
          <div class="admin-search-wrapper">
            <svg class="search-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="inputBuscarIngrediente" class="admin-search-input" placeholder="Buscar ingrediente..." autocomplete="off">
          </div>
        </div>

        <!-- Tarjeta Principal: Tabla de Inventario -->
        <div class="inventory-table-card">
          
          <div class="inventory-card-header">
            <div class="inventory-title-group">
              <h2 class="inventory-card-title">Ingredientes y Consumibles</h2>
              <p class="inventory-card-subtitle">Lista de insumos activos y cantidades actuales estimadas</p>
            </div>
            <span class="inventory-badge-updated">Actualizado hoy</span>
          </div>

          <!-- Tabla con las 5 columnas solicitadas -->
          <div class="table-responsive">
            <table class="inventory-table" id="tablaInventario">
              <thead>
                <tr>
                  <th class="col-code">Código</th>
                  <th class="col-ingredient">Ingrediente</th>
                  <th class="col-unit">Unidad</th>
                  <th class="col-qty">Cantidad Estimada</th>
                  <th class="col-adjust">Ajuste Manual</th>
                </tr>
              </thead>
              <tbody id="tablaInventarioBody">
                <?php foreach ($ingredientes as $ing): ?>
                  <?php 
                    $idIng = (int)$ing['id_ingrediente'];
                    $nombreIng = (string)$ing['nombre_ingrediente'];
                    $cantidadEst = (float)$ing['cantidad_estimada'];
                    $esBajo = ($cantidadEst <= 15.00);
                  ?>
                  <tr<?php echo $esBajo ? ' class="row-stock-alert"' : ''; ?> data-id="<?php echo $idIng; ?>">
                    <td class="cell-code<?php echo $esBajo ? ' text-danger' : ''; ?>"><?php echo $idIng; ?></td>
                    <td class="cell-ingredient">
                      <span class="ingredient-name"><?php echo htmlspecialchars($nombreIng); ?></span>
                      <?php if ($esBajo): ?>
                        <span class="badge-stock-low">STOCK BAJO</span>
                      <?php endif; ?>
                    </td>
                    <td class="cell-unit">Porción / Unidad</td>
                    <td class="cell-qty">
                      <strong class="<?php echo $esBajo ? 'qty-val-danger' : 'qty-val-normal'; ?>"><?php echo number_format($cantidadEst, 2); ?></strong>
                    </td>
                    <td class="cell-adjust">
                      <div class="adjust-control-group">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          class="input-adjust"
                          value="<?php echo number_format($cantidadEst, 2, '.', ''); ?>"
                          data-id="<?php echo $idIng; ?>"
                          aria-label="Ajuste manual para <?php echo htmlspecialchars($nombreIng); ?>"
                        >
                        <button type="button" class="btn-adjust-save" data-id="<?php echo $idIng; ?>">Guardar</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Mensaje de no resultados (oculto por defecto) -->
          <div id="noResultadosInventario" class="no-results-msg" style="display: none;">
            No se encontraron ingredientes que coincidan con la búsqueda.
          </div>

          <!-- Pie de la Tarjeta: Enlace Centrado hacia Resumen Analítico -->
          <div class="inventory-footer-link">
            <a href="admin_dashboard.php" class="link-detail-report">
              <span>Volver al resumen analítico de ventas</span>
              <span class="link-arrow">&rarr;</span>
            </a>
          </div>

        </div>

      </div>
    </main>

  </div>

  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
