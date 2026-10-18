<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio
 * Pantalla: Plano de Mesas y Consulta Dinámica de Pedidos (mesas.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db mediante modelos MVC
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Mesa.php';
require_once __DIR__ . '/../../models/Pedido.php';

// 1. Detección de la mesa seleccionada mediante parámetro GET (?mesa_id=1 por defecto o ?mesa=1)
$idMesaSeleccionada = isset($_GET['mesa_id']) ? $_GET['mesa_id'] : (isset($_GET['mesa']) ? $_GET['mesa'] : 1);

try {
    // 2. Consulta de todas las mesas registradas en la base de datos mediante el modelo Mesa
    $mesaModel = new Mesa($pdo);
    $mesas = $mesaModel->obtenerTodas();

    // Fallback de seguridad en caso de que la tabla aún no contenga registros
    if (empty($mesas)) {
        $mesas = [
            ['id_mesa' => 1, 'numero_mesa' => 'M-01', 'capacidad' => 2, 'estado' => 'Disponible'],
            ['id_mesa' => 2, 'numero_mesa' => 'M-02', 'capacidad' => 2, 'estado' => 'Ocupada'],
            ['id_mesa' => 3, 'numero_mesa' => 'M-03', 'capacidad' => 4, 'estado' => 'Disponible'],
            ['id_mesa' => 4, 'numero_mesa' => 'M-04', 'capacidad' => 4, 'estado' => 'Disponible'],
            ['id_mesa' => 5, 'numero_mesa' => 'M-05', 'capacidad' => 4, 'estado' => 'Disponible'],
            ['id_mesa' => 6, 'numero_mesa' => 'M-06', 'capacidad' => 2, 'estado' => 'Disponible'],
            ['id_mesa' => 7, 'numero_mesa' => 'M-07', 'capacidad' => 2, 'estado' => 'Disponible'],
            ['id_mesa' => 8, 'numero_mesa' => 'M-08', 'capacidad' => 4, 'estado' => 'Disponible']
        ];
    }

    // Identificar la información de la mesa seleccionada de manera robusta
    $mesaActivaInfo = null;
    foreach ($mesas as $m) {
        if ((string)$m['id_mesa'] === (string)$idMesaSeleccionada || 
            (string)$m['numero_mesa'] === (string)$idMesaSeleccionada ||
            (is_numeric($idMesaSeleccionada) && (int)$m['id_mesa'] === (int)$idMesaSeleccionada)) {
            $mesaActivaInfo = $m;
            break;
        }
    }
    if (!$mesaActivaInfo && !empty($mesas)) {
        $mesaActivaInfo = $mesas[0];
    }
    $idMesaActiva = $mesaActivaInfo ? $mesaActivaInfo['id_mesa'] : 1;
    $numeroMesaActiva = $mesaActivaInfo ? $mesaActivaInfo['numero_mesa'] : 'M-01';

    // 3. Consulta de consumos activos por mesa mediante el modelo Pedido
    $pedidoModel = new Pedido($pdo);
    $consumosPorMesa = $pedidoModel->obtenerConsumosActivosPorMesa();

    // 4. Consulta de platillos reales de la mesa seleccionada que no han sido pagados
    $detallesOrden = $pedidoModel->obtenerCuentaActivaPorMesa($idMesaActiva);

    // 5. Cálculo dinámico de Subtotal, Propina (10%) y Total general
    $subtotal = 0.00;
    foreach ($detallesOrden as $item) {
        $subtotal += (float)$item['subtotal_linea'];
    }
    $propina = round($subtotal * 0.10, 2);
    $total = round($subtotal + $propina, 2);

    // Configuración de la píldora de estado de la mesa activa
    $estadoRaw = strtolower(trim($mesaActivaInfo['estado'] ?? 'Disponible'));
    if (str_contains($estadoRaw, 'ocupad')) {
        $badgeClase = 'badge-ocupada';
        $badgeDot = 'dot-ocupada';
        $badgeTexto = 'Ocupada';
    } elseif (str_contains($estadoRaw, 'proces')) {
        $badgeClase = 'badge-proceso';
        $badgeDot = 'dot-proceso';
        $badgeTexto = 'En proceso';
    } elseif (str_contains($estadoRaw, 'sin')) {
        $badgeClase = 'badge-sin-servicio';
        $badgeDot = 'dot-sin-servicio';
        $badgeTexto = 'Sin servicio';
    } else {
        $badgeClase = 'badge-disponible';
        $badgeDot = 'dot-disponible';
        $badgeTexto = 'Disponible';
    }

} catch (PDOException $e) {
    die("Error al consultar el plano de mesas: " . $e->getMessage());
}

// Variables dinámicas para el layout del encabezado
$nombreUsuario = 'Carlos Méndez';
$rolUsuario = 'Personal de servicio';
$pageTitle = 'NIXGARU – Plano de Mesas | Personal de Servicio';

require_once '../layouts/header.php';
?>

  <!-- Layout Principal: 70% Plano de Mesas / 30% Cuenta -->
  <main class="mesas-layout">

    <!-- COLUMNA IZQUIERDA (70%): Plano de Mesas -->
    <section class="mesas-panel">
      
      <!-- Cabecera del Panel con Leyenda de Estados -->
      <div class="panel-header">
        <div class="panel-title-group">
          <h1 class="panel-title">Plano de Mesas</h1>
          <p class="panel-subtitle">Vista en tiempo real del salón principal</p>
        </div>

        <!-- Leyenda de Estados -->
        <div class="status-legend">
          <span class="legend-badge badge-disponible">
            <span class="status-dot dot-disponible"></span>
            Disponible
          </span>
          <span class="legend-badge badge-ocupada">
            <span class="status-dot dot-ocupada"></span>
            Ocupada
          </span>
          <span class="legend-badge badge-proceso">
            <span class="status-dot dot-proceso"></span>
            En proceso
          </span>
          <span class="legend-badge badge-sin-servicio">
            <span class="status-dot dot-sin-servicio"></span>
            Sin servicio
          </span>
        </div>
      </div>

      <!-- Cuadrícula de Mesas Generadas Dinámicamente con Foreach -->
      <div class="tables-grid">

        <?php foreach ($mesas as $mesa): ?>
          <?php 
            $numMesaDisplay = htmlspecialchars($mesa['numero_mesa']);
            $idM = (int)$mesa['id_mesa'];
            $esActiva = ((string)$mesa['id_mesa'] === (string)$idMesaActiva || (string)$mesa['numero_mesa'] === (string)$numeroMesaActiva);
            $consumoSub = $consumosPorMesa[$idM] ?? 0.00;
            $consumoMesa = $consumoSub > 0 ? ($consumoSub * 1.10) : 0.00;
            
            $st = strtolower(trim($mesa['estado']));
            if (str_contains($st, 'ocupad')) {
                $estadoSlug = 'ocupada';
            } elseif (str_contains($st, 'proces')) {
                $estadoSlug = 'proceso';
            } elseif (str_contains($st, 'sin')) {
                $estadoSlug = 'sin-servicio';
            } else {
                $estadoSlug = 'disponible';
            }
          ?>
          <article class="table-card <?php echo $esActiva ? 'active' : ''; ?>" 
                   data-table-number="<?php echo $numMesaDisplay; ?>" 
                   data-table-id="<?php echo $idM; ?>"
                   data-table-cap="<?php echo (int)$mesa['capacidad']; ?>"
                   onclick="if (!event.target.closest('.table-status-select')) { window.location.href='mesas.php?mesa_id=<?php echo urlencode($mesa['id_mesa']); ?>'; }">
            
            <div class="table-card-header">
              <h3 class="table-name">Mesa <?php echo $numMesaDisplay; ?></h3>
              <span class="table-capacity">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Cap. <?php echo (int)$mesa['capacidad']; ?>
              </span>
            </div>

            <div class="table-card-body">
              <div class="table-data-row">
                <span class="data-label">Consumo:</span>
                <span class="data-value">$<?php echo number_format($consumoMesa, 2); ?></span>
              </div>
              <div class="table-data-row">
                <span class="data-label">Tiempo:</span>
                <span class="data-value"><?php echo $consumoMesa > 0 ? '15 min' : '0 min'; ?></span>
              </div>
            </div>

            <div class="table-card-footer">
              <div class="status-select-wrapper">
                <select class="table-status-select status-<?php echo $estadoSlug; ?>" aria-label="Estado de la Mesa <?php echo $numMesaDisplay; ?>" data-table-id="<?php echo $idM; ?>">
                  <option value="disponible" <?php echo $estadoSlug === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                  <option value="ocupada" <?php echo $estadoSlug === 'ocupada' ? 'selected' : ''; ?>>Ocupado</option>
                  <option value="proceso" <?php echo $estadoSlug === 'proceso' ? 'selected' : ''; ?>>En proceso</option>
                  <option value="sin-servicio" <?php echo $estadoSlug === 'sin-servicio' ? 'selected' : ''; ?>>Sin servicio</option>
                </select>
              </div>
            </div>

          </article>
        <?php endforeach; ?>

      </div>

      <!-- Barra de Resumen en la Base del Panel -->
      <?php
        $cantDisponibles = 0;
        $cantOcupadas = 0;
        $cantProceso = 0;
        $cantSinServicio = 0;
        foreach ($mesas as $m) {
            $st = strtolower(trim($m['estado']));
            if (str_contains($st, 'ocupad')) $cantOcupadas++;
            elseif (str_contains($st, 'proces')) $cantProceso++;
            elseif (str_contains($st, 'sin')) $cantSinServicio++;
            else $cantDisponibles++;
        }
      ?>
      <footer class="tables-summary-bar">
        <div class="summary-counts">
          Resumen actual: 
          <strong><?php echo $cantDisponibles; ?> Disponibles</strong> | 
          <strong><?php echo $cantOcupadas; ?> Ocupadas</strong> | 
          <strong><?php echo $cantProceso; ?> En proceso</strong> | 
          <strong><?php echo $cantSinServicio; ?> Sin servicio</strong>
        </div>
        <div class="summary-sync">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
          <span>ACTUALIZADO EN TIEMPO REAL</span>
        </div>
      </footer>

    </section>

    <!-- COLUMNA DERECHA (30%): Cuenta de la Mesa -->
    <aside class="cuenta-panel">
      <div class="cuenta-card">
        
        <!-- Cabecera de la Cuenta -->
        <div class="cuenta-header">
          <h2 class="cuenta-title" id="cuentaMesaTitulo">Cuenta - Mesa <?php echo htmlspecialchars($numeroMesaActiva); ?></h2>
          <span class="legend-badge <?php echo $badgeClase; ?>" id="cuentaMesaEstado">
            <span class="status-dot <?php echo $badgeDot; ?>"></span>
            <?php echo htmlspecialchars($badgeTexto); ?>
          </span>
        </div>

        <!-- Sección de Productos -->
        <div class="cuenta-section-label">PRODUCTOS</div>

        <div class="cuenta-items-list" id="listaProductosCuenta">
          
          <?php if (!empty($detallesOrden)): ?>
            <?php foreach ($detallesOrden as $producto): ?>
              <?php 
                $cant = (int)$producto['cantidadPed'];
                $subtotalLinea = (float)$producto['subtotal_linea'];
              ?>
              <div class="cuenta-item">
                <span class="item-qty-badge"><?php echo $cant; ?></span>
                <span class="item-name"><?php echo htmlspecialchars($producto['nombre_producto']); ?></span>
                <span class="item-price">$<?php echo number_format($subtotalLinea, 2); ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-cuenta-msg" style="text-align: center; color: #6C757D; font-size: 13.5px; padding: 36px 12px;">
              <p style="margin: 0 0 6px 0; font-weight: 600; color: #343A40;">Mesa disponible sin consumos activos</p>
              <span style="font-size: 12.5px; opacity: 0.85;">No hay pedidos pendientes de pago para esta mesa.</span>
            </div>
          <?php endif; ?>

        </div>

        <!-- Totales de la Cuenta -->
        <div class="cuenta-totals">
          <div class="totals-line">
            <span>Subtotal</span>
            <span id="cuentaSubtotal">$<?php echo number_format($subtotal, 2); ?></span>
          </div>
          <div class="totals-line">
            <span>Propina (10%)</span>
            <span id="cuentaPropina">$<?php echo number_format($propina, 2); ?></span>
          </div>
          <div class="totals-line total-highlight">
            <span class="total-text">Total</span>
            <span class="total-amount" id="cuentaTotal">$<?php echo number_format($total, 2); ?></span>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="cuenta-actions">
          <a href="gestion_orden.php?mesa_id=<?php echo urlencode($idMesaActiva); ?>" class="btn-account-add" id="btnAgregarProducto" data-mesa="<?php echo htmlspecialchars($idMesaActiva); ?>" data-numero-mesa="<?php echo htmlspecialchars($numeroMesaActiva); ?>" style="text-decoration: none;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="16"></line>
              <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
            <span>Agregar Producto</span>
          </a>

          <a href="cierre_cuenta.php?mesa_id=<?php echo urlencode($idMesaActiva); ?>" class="btn-account-close" id="btnCerrarCuenta" data-mesa="<?php echo htmlspecialchars($idMesaActiva); ?>" data-numero-mesa="<?php echo htmlspecialchars($numeroMesaActiva); ?>" style="text-decoration: none;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span>Cerrar Cuenta</span>
          </a>
        </div>

      </div>
    </aside>

  </main>

  <!-- Script Principal con la lógica requerida -->
  <script src="../../public/js/main.js"></script>
</body>
</html>
