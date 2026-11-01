<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Resumen Analítico de Ventas y Demanda (admin_dashboard.php)
 * Cumple con RF11 (Consulta histórica de ventas) y RF12 (Productos más demandados)
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';

$fechaInicioFiltro = isset($_GET['fecha_inicio']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$_GET['fecha_inicio']))
    ? trim((string)$_GET['fecha_inicio'])
    : null;
$fechaFinFiltro = isset($_GET['fecha_fin']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$_GET['fecha_fin']))
    ? trim((string)$_GET['fecha_fin'])
    : null;

if ($fechaInicioFiltro !== null && $fechaFinFiltro !== null && $fechaInicioFiltro > $fechaFinFiltro) {
    [$fechaInicioFiltro, $fechaFinFiltro] = [$fechaFinFiltro, $fechaInicioFiltro];
}

try {
    $pedidoModel = new Pedido($pdo);
    $estadisticas = $pedidoModel->obtenerEstadisticasVentas($fechaInicioFiltro, $fechaFinFiltro);
    $productosMasVendidos = $pedidoModel->obtenerProductosMasVendidos(5, $fechaInicioFiltro, $fechaFinFiltro);
} catch (PDOException $e) {
    die("Error al consultar estadísticas de ventas: " . $e->getMessage());
}

$etiquetaRangoInicial = 'Histórico general';
if ($fechaInicioFiltro && $fechaFinFiltro) {
    $etiquetaRangoInicial = date('d/m/Y', strtotime($fechaInicioFiltro)) . ' – ' . date('d/m/Y', strtotime($fechaFinFiltro));
} elseif ($fechaInicioFiltro) {
    $etiquetaRangoInicial = 'Desde ' . date('d/m/Y', strtotime($fechaInicioFiltro));
} elseif ($fechaFinFiltro) {
    $etiquetaRangoInicial = 'Hasta ' . date('d/m/Y', strtotime($fechaFinFiltro));
}

$pageTitle = 'NIXGARU – Resumen Analítico';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-content">
      <div class="admin-content-card">
        
        <!-- Encabezado de la Pantalla: Título y Filtro de Fechas -->
        <div class="admin-content-header">
          <div class="admin-title-group">
            <h1 class="admin-page-title">Resumen Analítico</h1>
            <p class="admin-page-subtitle">Rendimiento general y productos destacados por período</p>
          </div>
          
          <!-- Botón Activador / Indicador de Rango de Fechas -->
          <button class="btn-date-range" id="btnDateFilter" type="button" title="Mostrar u ocultar selector de rango de fechas" aria-expanded="true" aria-controls="dateFilterBar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span id="labelRangoFechas"><?php echo htmlspecialchars($etiquetaRangoInicial); ?></span>
          </button>
        </div>

        <!-- Barra Compacta Interactiva de Filtro por Rango de Fechas (RF11, RF12) -->
        <form id="dateFilterBar" class="date-filter-toolbar" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; background-color: #FAFAFA; border: 1px solid var(--color-borde-suave); border-radius: 14px; padding: 14px 18px; margin-bottom: 24px;">
          <div style="display: flex; flex-direction: column; gap: 5px; flex: 1 1 170px; min-width: 155px;">
            <label for="inputFechaInicio" class="form-label" style="font-size: 0.76rem; color: var(--color-texto-muted); margin: 0;">Fecha de inicio</label>
            <input
              type="date"
              id="inputFechaInicio"
              name="fecha_inicio"
              class="form-input"
              style="padding: 9px 12px; background-color: var(--color-blanco); font-size: 0.85rem;"
              value="<?php echo htmlspecialchars($fechaInicioFiltro ?? ''); ?>"
            >
          </div>

          <div style="display: flex; flex-direction: column; gap: 5px; flex: 1 1 170px; min-width: 155px;">
            <label for="inputFechaFin" class="form-label" style="font-size: 0.76rem; color: var(--color-texto-muted); margin: 0;">Fecha de fin</label>
            <input
              type="date"
              id="inputFechaFin"
              name="fecha_fin"
              class="form-input"
              style="padding: 9px 12px; background-color: var(--color-blanco); font-size: 0.85rem;"
              value="<?php echo htmlspecialchars($fechaFinFiltro ?? ''); ?>"
            >
          </div>

          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="submit" class="btn-admin-primary" id="btnConsultarFechas" style="padding: 9px 20px; font-size: 0.85rem;">
              <span>Consultar</span>
            </button>
            <button type="button" class="btn-modal-cancel" id="btnVerTodoFechas" style="padding: 9px 18px; font-size: 0.85rem;">
              <span>Ver todo</span>
            </button>
          </div>
        </form>

        <!-- Tarjetas KPI Superiores (Grid 2 columnas) -->
        <div class="kpi-grid">
          
          <!-- Tarjeta 1: Cantidad de Ventas -->
          <div class="kpi-card">
            <div class="kpi-header">
              <span class="kpi-title">Cantidad de ventas</span>
              <div class="kpi-icon-badge" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                  <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
              </div>
            </div>
            <div class="kpi-value-group">
              <span class="kpi-value text-kpi-primary" id="kpiCantidadVentas"><?php echo number_format((int)($estadisticas['cantidad_ventas'] ?? 0)); ?></span>
              <p class="kpi-subtext">Órdenes completadas con éxito</p>
            </div>
          </div>

          <!-- Tarjeta 2: Total Vendido -->
          <div class="kpi-card">
            <div class="kpi-header">
              <span class="kpi-title">Total vendido</span>
              <div class="kpi-icon-badge" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                  <circle cx="12" cy="12" r="2.5"></circle>
                  <path d="M6 12h.01M18 12h.01"></path>
                </svg>
              </div>
            </div>
            <div class="kpi-value-group">
              <span class="kpi-value text-kpi-highlight" id="kpiTotalVendido">$<?php echo number_format((float)($estadisticas['total_vendido'] ?? 0), 2); ?> USD</span>
              <p class="kpi-subtext">Ingresos brutos acumulados</p>
            </div>
          </div>

        </div>

        <!-- Tabla: Productos con mayor demanda (Top 5) -->
        <div class="ranking-table-card">
          
          <div class="ranking-header">
            <div class="ranking-title-group">
              <h2 class="ranking-card-title">Productos con mayor demanda</h2>
              <p class="ranking-card-subtitle">Clasificación de productos más ordenados</p>
            </div>
            <span class="ranking-badge-top">Top 5</span>
          </div>

          <!-- Tabla con las 3 columnas solicitadas -->
          <div class="table-responsive">
            <table class="ranking-table">
              <thead>
                <tr>
                  <th class="col-rank">Posición</th>
                  <th class="col-product">Producto</th>
                  <th class="col-qty">Cantidad vendida</th>
                </tr>
              </thead>
              <tbody id="tablaTopProductosBody">
                <?php if (!empty($productosMasVendidos)): ?>
                  <?php foreach ($productosMasVendidos as $index => $prod): 
                    $posicion = $index + 1;
                    $badgeClass = match ($posicion) {
                      1 => 'rank-badge rank-gold',
                      2 => 'rank-badge rank-silver',
                      3 => 'rank-badge rank-bronze',
                      default => 'rank-plain',
                    };
                  ?>
                    <tr>
                      <td class="cell-rank">
                        <span class="<?php echo $badgeClass; ?>"><?php echo $posicion; ?></span>
                      </td>
                      <td class="cell-product">
                        <div class="rank-product-info">
                          <span class="rank-product-name"><?php echo htmlspecialchars((string)$prod['nombre_producto']); ?></span>
                          <span class="rank-product-price">$<?php echo number_format((float)$prod['precio'], 2); ?> USD</span>
                        </div>
                      </td>
                      <td class="cell-qty">
                        <span class="rank-qty-val"><?php echo (int)$prod['total_cantidad']; ?> uds.</span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="3" style="text-align: center; color: #6C757D; padding: 24px;">No se encontraron ventas registradas para el período consultado.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pie del Card: Enlace centrado a reporte detallado de inventario -->
          <div class="ranking-footer-link">
            <a href="admin_inventario.php" class="link-detail-report">
              <span>Ver reporte de inventario detallado</span>
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
