<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio
 * Pantalla: Cierre de Cuenta y Facturación (cierre_cuenta.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db
 * ==============================================================================
 */
require_once '../../config/database.php';
require_once '../../models/Pedido.php';

// 1. Detección de la mesa seleccionada mediante parámetro GET (?mesa_id=1 por defecto o ?mesa=1)
$idMesaSeleccionada = isset($_GET['mesa_id']) ? (int)$_GET['mesa_id'] : (isset($_GET['mesa']) ? (int)$_GET['mesa'] : 1);
if ($idMesaSeleccionada <= 0) {
    $idMesaSeleccionada = 1;
}

try {
    // 2. Consulta de información de la mesa seleccionada
    $stmtMesa = $pdo->prepare("SELECT id_mesa, numero_mesa, capacidad, estado FROM mesas WHERE id_mesa = ? OR numero_mesa = ? LIMIT 1");
    $stmtMesa->execute([$idMesaSeleccionada, (string)$idMesaSeleccionada]);
    $mesaInfo = $stmtMesa->fetch();

    $numeroMesa = $mesaInfo ? $mesaInfo['numero_mesa'] : (string)$idMesaSeleccionada;
    $idMesaReal = $mesaInfo ? (int)$mesaInfo['id_mesa'] : $idMesaSeleccionada;

    // 3. Consulta de la orden activa mediante el modelo Pedido
    $pedidoModel = new Pedido($pdo);
    $detallesOrden = $pedidoModel->obtenerCuentaActivaPorMesa($idMesaReal);

    // 4. Cálculo dinámico de Subtotal, Propina (10%) y Total general
    $subtotal = 0.00;
    $idPedido = 0;
    $fechaHoraPedido = null;
    $nombreAtendio = 'Carlos Méndez';

    foreach ($detallesOrden as $item) {
        $subtotal += (float)$item['subtotal_linea'];
        if (!$idPedido && !empty($item['id_pedido'])) {
            $idPedido = (int)$item['id_pedido'];
        }
        if (!$fechaHoraPedido && !empty($item['fecha_hora'])) {
            $fechaHoraPedido = $item['fecha_hora'];
        }
        if (!empty($item['nombre_mesero'])) {
            $nombreAtendio = $item['nombre_mesero'];
        }
    }

    $propina = $subtotal * 0.10;
    $total = $subtotal + $propina;

    // Formatos de fecha y hora
    $fechaTexto = $fechaHoraPedido ? date('d M Y', strtotime($fechaHoraPedido)) : date('d M Y');
    $horaTexto = $fechaHoraPedido ? date('H:i', strtotime($fechaHoraPedido)) : date('H:i');
    $folioTexto = '#NIX-' . str_pad($idPedido > 0 ? $idPedido : 1, 5, '0', STR_PAD_LEFT);

} catch (PDOException $e) {
    die("Error al consultar cierre de cuenta: " . $e->getMessage());
}

// Variables dinámicas para el layout del encabezado
$nombreUsuario = $nombreAtendio;
$rolUsuario = 'Personal de servicio';
$pageTitle = 'NIXGARU – Cierre de Cuenta | Personal de Servicio';

require_once '../layouts/header.php';
?>

  <!-- Layout Principal de Cierre de Cuenta -->
  <main class="cierre-layout">

    <!-- COLUMNA IZQUIERDA: Vista del Ticket / Factura -->
    <section class="cierre-ticket-panel">
      
      <!-- Contenedor del Ticket que se convertirá a PDF -->
      <div class="ticket-card" id="ticketContainer">
        
        <!-- Cabecera del Ticket -->
        <div class="ticket-header">
          <h2 class="ticket-brand">NIXGARU</h2>
          <p class="ticket-tagline">Venta de café, bebidas y productos de repostería</p>
          <p class="ticket-location">San Salvador, El Salvador</p>
        </div>

        <!-- Metadatos de la Factura -->
        <div class="ticket-meta">
          <div class="ticket-meta-row">
            <span>Fecha: <?php echo htmlspecialchars($fechaTexto); ?></span>
            <span>Hora: <?php echo htmlspecialchars($horaTexto); ?></span>
          </div>
          <div class="ticket-meta-row">
            <span>Mesa: <?php echo str_pad($numeroMesa, 2, '0', STR_PAD_LEFT); ?> (Servicio)</span>
            <span>Folio: <?php echo htmlspecialchars($folioTexto); ?></span>
          </div>
          <div class="ticket-meta-row">
            <span>Atendió: <?php echo htmlspecialchars($nombreAtendio); ?></span>
          </div>
        </div>

        <!-- Lista de Productos Facturados -->
        <div class="ticket-items">
          <?php if (!empty($detallesOrden)): ?>
            <?php foreach ($detallesOrden as $prod): ?>
              <div class="ticket-item-row">
                <span class="t-name"><strong><?php echo (int)$prod['cantidadPed']; ?>x</strong> <?php echo htmlspecialchars($prod['nombre_producto']); ?></span>
                <span class="t-price">$<?php echo number_format((float)$prod['subtotal_linea'], 2); ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="ticket-item-row" style="color: #6C757D; font-style: italic; justify-content: center;">
              <span>No hay productos activos pendientes de pago.</span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Separador de Guiones -->
        <div class="ticket-separator">------------------------------------------------</div>

        <!-- Subtotales y Propina -->
        <div class="ticket-totals-section">
          <div class="ticket-subtotal-row">
            <span>Subtotal</span>
            <span id="ticketSubtotalVal">$<?php echo number_format($subtotal, 2); ?></span>
          </div>
          <div class="ticket-subtotal-row">
            <span>Propina sugerida (10%)</span>
            <span id="ticketPropinaVal">$<?php echo number_format($propina, 2); ?></span>
          </div>
        </div>

        <!-- Total Destacado -->
        <div class="ticket-grand-total">
          <div class="grand-total-row">
            <span class="grand-total-label">TOTAL A PAGAR</span>
            <span class="grand-total-amount" id="ticketTotalVal">$<?php echo number_format($total, 2); ?></span>
          </div>
          <span class="grand-total-currency">Dólar de los Estados Unidos (USD)</span>
        </div>

        <!-- Pie del Ticket -->
        <div class="ticket-footer">
          <p class="ticket-thanks">*** Gracias por su visita ***</p>
          <p class="ticket-fiscal">Sujeto a facturación dentro del mes corriente.</p>
        </div>

      </div>

    </section>

    <!-- COLUMNA DERECHA: Procesamiento y Método de Pago -->
    <section class="cierre-payment-panel">
      
      <!-- Tarjeta Superior: Monto Total de Operación -->
      <div class="payment-total-card">
        <div class="payment-total-info">
          <h3 class="payment-total-title">Monto Total de Operación</h3>
          <p class="payment-total-subtitle">Mesa <?php echo htmlspecialchars($numeroMesa); ?> · Incluye propuesta de propina</p>
        </div>
        <div class="payment-total-display">
          <span class="total-big-number" id="displayTotalOperacion">$<?php echo number_format($total, 2); ?></span>
          <span class="total-big-currency">USD</span>
        </div>
      </div>

      <!-- Selección de Método de Pago -->
      <div class="payment-method-section">
        <h3 class="section-title">Método de Pago</h3>
        
        <div class="payment-method-card active">
          <div class="payment-method-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="6" width="20" height="12" rx="2"></rect>
              <circle cx="12" cy="12" r="2"></circle>
              <path d="M6 12h.01M18 12h.01"></path>
            </svg>
          </div>
          <span class="payment-method-text">Efectivo</span>
        </div>
      </div>

      <!-- Tarjeta: Detalle de Efectivo -->
      <div class="cash-detail-card">
        <div class="cash-detail-header">
          <h3 class="cash-detail-title">Detalle de Efectivo</h3>
          <span class="exact-badge" id="sugeridoExactoBadge">Sugerido Exacto: $<?php echo number_format($total, 2); ?></span>
        </div>

        <div class="cash-inputs-row">
          <!-- Monto Recibido -->
          <div class="cash-input-group">
            <label for="inputMontoRecibido" class="cash-label">Monto Recibido</label>
            <div class="cash-input-box active-focus">
              <span class="currency-symbol">$</span>
              <input type="number" step="0.01" id="inputMontoRecibido" class="cash-input-field" value="<?php echo number_format($total, 2, '.', ''); ?>" data-total="<?php echo number_format($total, 2, '.', ''); ?>" aria-label="Monto Recibido">
            </div>
          </div>

          <!-- Cambio a Entregar -->
          <div class="cash-input-group">
            <label class="cash-label">Cambio a Entregar</label>
            <div class="cash-change-box">
              <span class="currency-symbol-green">$</span>
              <span class="cash-change-value" id="cashChangeValue">0.00</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmación de Mesa Desocupada -->
      <div class="table-confirm-box">
        <label class="checkbox-container">
          <input type="checkbox" id="checkMesaDesocupada" checked>
          <span class="checkbox-checkmark"></span>
          <span class="checkbox-label-text">Confirmar que la mesa ha desocupado la estación antes del cierre de cuenta.</span>
        </label>
      </div>

      <!-- Botones de Acción Finales -->
      <div class="cierre-actions">
        <!-- 1. Botón Generar Ticket PDF -->
        <button class="btn-generate-pdf" id="btnGenerarPDF" type="button">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          <span>Generar Ticket PDF</span>
        </button>

        <!-- 2. Botón Procesar Pago & Liberar Mesa -->
        <button class="btn-process-payment" id="btnProcesarPago" type="button" data-mesa="<?php echo htmlspecialchars($numeroMesa); ?>" data-total="<?php echo number_format($total, 2, '.', ''); ?>" data-idpedido="<?php echo $idPedido; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
          </svg>
          <span>Procesar Pago &amp; Liberar Mesa</span>
        </button>
      </div>

    </section>

  </main>

  <!-- Script Principal con la lógica requerida -->
  <script src="../../public/js/main.js"></script>
</body>
</html>
