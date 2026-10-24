<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Inventario.php';

try {
    $inventarioModel = new Inventario($pdo);
    $ingredientes = $inventarioModel->obtenerTodos();
    if (empty($ingredientes)) {
        $ingredientes = [
            ['codigo_ingrediente' => 'I01', 'nombre' => 'Café molido', 'unidad' => 'lb', 'cantidad' => 15.50, 'stock_minimo' => 20.00, 'estado' => 'Bajo'],
            ['codigo_ingrediente' => 'I03', 'nombre' => 'Leche', 'unidad' => 'L', 'cantidad' => 22.00, 'stock_minimo' => 10.00, 'estado' => 'Suficiente'],
            ['codigo_ingrediente' => 'I13', 'nombre' => 'Huevo', 'unidad' => 'unidad', 'cantidad' => 30.00, 'stock_minimo' => 12.00, 'estado' => 'Suficiente'],
            ['codigo_ingrediente' => 'I24', 'nombre' => 'Croissant', 'unidad' => 'unidad', 'cantidad' => 12.00, 'stock_minimo' => 5.00, 'estado' => 'Suficiente']
        ];
    }
} catch (PDOException $e) {
    die("Error al consultar el inventario: " . $e->getMessage());
}

$pageTitle = 'NIXGARU – Control de Inventarios';
require_once '../layouts/sidebar.php';
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
                    $esBajo = (isset($ing['stock_minimo']) && (float)$ing['cantidad'] <= (float)$ing['stock_minimo']) 
                              || (isset($ing['estado']) && strtolower(trim($ing['estado'])) === 'bajo');
                  ?>
                  <tr<?php echo $esBajo ? ' class="row-stock-alert"' : ''; ?>>
                    <td class="cell-code<?php echo $esBajo ? ' text-danger' : ''; ?>"><?php echo htmlspecialchars($ing['codigo_ingrediente']); ?></td>
                    <td class="cell-ingredient">
                      <span class="ingredient-name"><?php echo htmlspecialchars($ing['nombre']); ?></span>
                      <?php if ($esBajo): ?>
                        <span class="badge-stock-low">STOCK BAJO</span>
                      <?php endif; ?>
                    </td>
                    <td class="cell-unit"><?php echo htmlspecialchars($ing['unidad']); ?></td>
                    <td class="cell-qty">
                      <strong class="<?php echo $esBajo ? 'qty-val-danger' : 'qty-val-normal'; ?>"><?php echo number_format((float)$ing['cantidad'], 2); ?></strong>
                      <span class="unit-text"><?php echo htmlspecialchars($ing['unidad']); ?></span>
                    </td>
                    <td class="cell-adjust">
                      <div class="adjust-control-group">
                        <input type="number" step="0.01" class="input-adjust" value="0.00" aria-label="Ajuste manual para <?php echo htmlspecialchars($ing['nombre']); ?>">
                        <button type="button" class="btn-adjust-save">Guardar</button>
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

          <!-- Pie de la Tarjeta: Enlace Centrado -->
          <div class="inventory-footer-link">
            <a href="#" class="link-detail-report">
              <span>Ver reporte de inventario detallado</span>
              <span class="link-arrow">&rarr;</span>
            </a>
          </div>

        </div>

      </div>
    </main>

  </div>

  <script src="../../public/js/main.js"></script>
</body>
</html>

