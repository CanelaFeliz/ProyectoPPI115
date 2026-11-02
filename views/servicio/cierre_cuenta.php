<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio y Cajero
 * Pantalla: Cierre de Cuenta y Facturación (cierre_cuenta.php)
 * Conexión dinámica con PDO a la base de datos nixgaru_db
 * Requerimientos: RNF03, RNF04, RF09, RF10, RF14
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
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Mesa.php';

// 1. Detección de la mesa seleccionada mediante parámetro GET (?mesa_id=1 por defecto o ?mesa=1)
$idMesaSeleccionada = isset($_GET['mesa_id']) ? $_GET['mesa_id'] : (isset($_GET['mesa']) ? $_GET['mesa'] : 1);

try {
    // 2. Consulta de información de la mesa seleccionada mediante el modelo Mesa
    $mesaModel = new Mesa($pdo);
    $mesaInfo = $mesaModel->obtenerPorId($idMesaSeleccionada);

    $numeroMesa = $mesaInfo ? (string)$mesaInfo['numero_mesa'] : (string)$idMesaSeleccionada;
    $idMesaReal = $mesaInfo ? (int)$mesaInfo['id_mesa'] : (int)$idMesaSeleccionada;

    // 3. Consulta de la orden activa mediante el modelo Pedido
    $pedidoModel = new Pedido($pdo);
    $detallesOrden = $pedidoModel->obtenerCuentaActivaPorMesa($idMesaReal);

    // 4. Cálculo diferencial de Subtotal, Propina (10%), Total, Monto Abonado (Web) y Saldo Pendiente (RF09, RF10)
    $subtotal = 0.00;
    $idPedido = 0;
    $fechaHoraPedido = null;
    $nombreAtendio = $_SESSION['nombre'] ?? 'Personal de servicio';
    $montoAbonado = 0.00;
    $tipoOrden = 'Consumo en local';

    foreach ($detallesOrden as $item) {
        $subtotal += (float)$item['subtotal'];
        if (!$idPedido && !empty($item['id_pedido'])) {
            $idPedido = (int)$item['id_pedido'];
        }
        if (!$fechaHoraPedido && !empty($item['fecha_hora'])) {
            $fechaHoraPedido = $item['fecha_hora'];
        }
        if (!empty($item['nombre_completo'])) {
            $nombreAtendio = (string)$item['nombre_completo'];
        }
        if ($montoAbonado <= 0 && isset($item['monto_abonado'])) {
            $montoAbonado = (float)$item['monto_abonado'];
        }
        if (!empty($item['tipo_orden'])) {
            $tipoOrden = (string)$item['tipo_orden'];
        }
    }

    $subtotal = round($subtotal, 2);
    $montoAbonado = round($montoAbonado, 2);
    $propina = round($subtotal * 0.10, 2);
    $total = round($subtotal + $propina, 2);
    $saldoPendiente = max(0.00, round($total - $montoAbonado, 2));

    // Formatos de fecha, hora y folio
    $fechaTexto = $fechaHoraPedido ? date('d M Y', strtotime((string)$fechaHoraPedido)) : date('d M Y');
    $horaTexto = $fechaHoraPedido ? date('H:i', strtotime((string)$fechaHoraPedido)) : date('H:i');
    $folioTexto = '#NIX-' . str_pad((string)($idPedido > 0 ? $idPedido : 1), 5, '0', STR_PAD_LEFT);
} catch (PDOException $e) {
    die("Error al consultar cierre de cuenta: " . $e->getMessage());
}

// Variables dinámicas para el layout del encabezado (sesión real prioritaria RNF04)
$currentPage = 'cierre_cuenta.php';
$nombreUsuario = $_SESSION['nombre'] ?? $nombreAtendio;
$rolUsuario = $_SESSION['rol'] ?? 'Personal de servicio';
$pageTitle = 'NIXGARU – Cierre de Cuenta | Personal de Servicio';
$headerTitle = 'Cierre de Cuenta - Mesa ' . $numeroMesa;

require_once __DIR__ . '/../layouts/header.php';
?>

  <!-- Layout Principal de Cierre de Cuenta -->
  <main class="cierre-layout">

    <!-- COLUMNA PRINCIPAL (IZQUIERDA): Procesamiento y Método de Pago -->
    <section class="cierre-payment-panel">
      
      <!-- Tarjeta Superior: Monto Total de Operación / Saldo Pendiente a Pagar en Caja -->
      <div class="payment-total-card">
        <div class="payment-total-info">
          <h3 class="payment-total-title">
            <?php echo $montoAbonado > 0 ? 'Saldo Pendiente a Pagar en Caja' : 'Monto Total de Operación'; ?>
          </h3>
          <p class="payment-total-subtitle">
            Mesa <?php echo htmlspecialchars($numeroMesa); ?> · <?php echo htmlspecialchars($tipoOrden); ?> · Total de la Cuenta: $<?php echo number_format($total, 2); ?>
          </p>
        </div>
        <div class="payment-total-display">
          <span class="total-big-number" id="displayTotalOperacion">$<?php echo number_format($saldoPendiente, 2); ?></span>
          <span class="total-big-currency">USD</span>
        </div>
      </div>

      <!-- Desglose Diferencial de Cobro (Subtotal, Propina 10%, Total de la Cuenta, Abono Web y Saldo en Caja) -->
      <div class="cierre-differential-box" aria-label="Desglose financiero de la cuenta">
        <div class="cierre-diff-row">
          <span><?php echo $montoAbonado > 0 ? 'Nuevo Subtotal de Productos:' : 'Subtotal de Productos:'; ?></span>
          <strong>$<?php echo number_format($subtotal, 2); ?> USD</strong>
        </div>
        <div class="cierre-diff-row">
          <span>Propina sugerida (10%):</span>
          <strong>$<?php echo number_format($propina, 2); ?> USD</strong>
        </div>
        <div class="cierre-diff-row total-cuenta">
          <span>Total de la Cuenta:</span>
          <strong id="resumenTotalCuentaCaja">$<?php echo number_format($total, 2); ?> USD</strong>
        </div>
        <?php if ($montoAbonado > 0): ?>
          <div class="cierre-diff-row abono-web">
            <span>Monto Abonado (Web):</span>
            <strong id="resumenAbonoWebCaja">-$<?php echo number_format($montoAbonado, 2); ?> USD</strong>
          </div>
          <div class="cierre-diff-row saldo-caja">
            <span>Saldo Pendiente a Pagar en Caja:</span>
            <strong id="resumenSaldoPendienteCaja">$<?php echo number_format($saldoPendiente, 2); ?> USD</strong>
          </div>
        <?php endif; ?>
      </div>

      <!-- Selección de Método de Pago (Efectivo o Tarjeta habilitados - RNF03) -->
      <div class="payment-method-section">
        <h3 class="section-title">Método de Pago</h3>
        
        <div class="payment-methods-grid">
          <!-- Opción 1: Efectivo -->
          <div class="payment-method-card active" id="metodoEfectivoCard" data-metodo="Efectivo" role="button" tabindex="0">
            <div class="payment-method-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                <circle cx="12" cy="12" r="2"></circle>
                <path d="M6 12h.01M18 12h.01"></path>
              </svg>
            </div>
            <span class="payment-method-text">Efectivo</span>
          </div>

          <!-- Opción 2: Tarjeta (Habilitada para cobro simulado en terminal POS - RNF03) -->
          <div class="payment-method-card" id="metodoTarjetaCard" data-metodo="Tarjeta" role="button" tabindex="0" title="Cobrar con tarjeta en terminal POS">
            <div class="payment-method-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
              </svg>
            </div>
            <span class="payment-method-text">Tarjeta</span>
          </div>
        </div>
      </div>

      <!-- Tarjeta: Detalle de Efectivo (Base de validación: $saldoPendiente) -->
      <div class="cash-detail-card" id="cashDetailCard">
        <div class="cash-detail-header">
          <h3 class="cash-detail-title">Detalle de Efectivo</h3>
          <span class="exact-badge" id="sugeridoExactoBadge">Sugerido Exacto: $<?php echo number_format($saldoPendiente, 2); ?></span>
        </div>

        <div class="cash-inputs-row">
          <!-- Monto Recibido -->
          <div class="cash-input-group">
            <label for="inputMontoRecibido" class="cash-label">Monto Recibido en Caja</label>
            <div class="cash-input-box active-focus">
              <span class="currency-symbol">$</span>
              <input type="number"
                     step="0.01"
                     min="0"
                     id="inputMontoRecibido"
                     class="cash-input-field"
                     value="<?php echo number_format($saldoPendiente, 2, '.', ''); ?>"
                     data-total="<?php echo number_format($saldoPendiente, 2, '.', ''); ?>"
                     aria-label="Monto Recibido">
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

      <!-- Tarjeta informativa para cobro simulado con Tarjeta (RNF03) -->
      <div class="card-terminal-sim-box" id="cardTerminalSimBox">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <strong style="font-size: 0.9rem; color: var(--color-negro-suave);">Terminal POS</strong>
          <span style="font-size: 0.78rem; font-weight: 700; color: #059669; background: #ECFDF5; padding: 3px 10px; border-radius: 999px;">Cargo Exacto</span>
        </div>
        <p style="font-size: 0.82rem; color: var(--color-texto-muted); margin: 0;">
          Se procesará un cargo por exactamente <strong>$<?php echo number_format($saldoPendiente, 2); ?> USD</strong>.
        </p>
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
        <!-- Botón Procesar Pago & Liberar Mesa -->
        <button class="btn-process-payment"
                id="btnProcesarPago"
                type="button"
                data-mesa="<?php echo (int)$idMesaReal; ?>"
                data-numero-mesa="<?php echo htmlspecialchars($numeroMesa); ?>"
                data-total="<?php echo number_format($saldoPendiente, 2, '.', ''); ?>"
                data-total-cuenta="<?php echo number_format($total, 2, '.', ''); ?>"
                data-abono="<?php echo number_format($montoAbonado, 2, '.', ''); ?>"
                data-idpedido="<?php echo (int)$idPedido; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
          </svg>
          <span>Procesar Pago &amp; Liberar Mesa</span>
        </button>
      </div>

    </section>

    <!-- COLUMNA DERECHA / MODAL DESLIZANTE: Vista del Ticket / Factura -->
    <aside class="cierre-ticket-panel ticket-sidebar" id="ticketSidebar" aria-label="Detalle del comprobante de venta">
      
      <!-- Cabecera del Drawer -->
      <div class="ticket-sidebar-header">
        <h2 class="ticket-sidebar-title">Comprobante de Venta</h2>
        <button class="cuenta-close-btn ticket-close-btn" id="ticketCloseBtn" type="button" aria-label="Cerrar ticket" title="Cerrar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Contenedor del Ticket que se convertirá a PDF mediante html2pdf.js -->
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
            <span>Mesa: <?php echo htmlspecialchars(str_pad($numeroMesa, 2, '0', STR_PAD_LEFT)); ?> (Servicio)</span>
            <span>Folio: <span id="ticketFolioVal"><?php echo htmlspecialchars($folioTexto); ?></span></span>
          </div>
          <div class="ticket-meta-row">
            <span>Modalidad: <span id="ticketModalidadVal"><?php echo htmlspecialchars($tipoOrden); ?></span></span>
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
                <span class="t-name"><strong><?php echo (int)$prod['cantidad']; ?>x</strong> <?php echo htmlspecialchars($prod['nombre_producto']); ?></span>
                <span class="t-price">$<?php echo number_format((float)$prod['subtotal'], 2); ?></span>
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

        <!-- Subtotales, Propina, Total de la Cuenta y Abono Web -->
        <div class="ticket-totals-section">
          <div class="ticket-subtotal-row">
            <span><?php echo $montoAbonado > 0 ? 'Nuevo Subtotal' : 'Subtotal'; ?></span>
            <span id="ticketSubtotalVal">$<?php echo number_format($subtotal, 2); ?></span>
          </div>
          <div class="ticket-subtotal-row">
            <span>Propina sugerida (10%)</span>
            <span id="ticketPropinaVal">$<?php echo number_format($propina, 2); ?></span>
          </div>
          <div class="ticket-subtotal-row" style="font-weight: 700; color: #111827;">
            <span>TOTAL DE LA CUENTA</span>
            <span id="ticketTotalCuentaVal">$<?php echo number_format($total, 2); ?></span>
          </div>
          <div class="ticket-subtotal-row">
            <span>Monto abonado vía web</span>
            <span id="ticketAbonoWebVal"><?php echo $montoAbonado > 0 ? '-$' . number_format($montoAbonado, 2) : '$0.00'; ?></span>
          </div>
        </div>

        <!-- Saldo Pendiente Cobrado Destacado -->
        <div class="ticket-grand-total">
          <div class="grand-total-row">
            <span class="grand-total-label">SALDO PENDIENTE COBRADO</span>
            <span class="grand-total-amount" id="ticketTotalVal">$<?php echo number_format($saldoPendiente, 2); ?></span>
          </div>
          <span class="grand-total-currency">Dólar de los Estados Unidos (USD)</span>
        </div>

        <!-- Desglose Dinámico de Cobro en Caja (Método, Efectivo Recibido y Cambio) -->
        <div class="ticket-totals-section" style="border-top: 1px dashed #D1D5DB; padding-top: 10px; margin-bottom: 14px;">
          <div class="ticket-subtotal-row">
            <span>Método de pago</span>
            <span id="ticketMetodoPagoVal">Efectivo</span>
          </div>
          <div class="ticket-subtotal-row">
            <span>Efectivo recibido</span>
            <span id="ticketEfectivoRecibidoVal">$<?php echo number_format($saldoPendiente, 2); ?></span>
          </div>
          <div class="ticket-subtotal-row">
            <span>Cambio</span>
            <span id="ticketCambioVal">$0.00</span>
          </div>
        </div>

        <!-- Pie del Ticket -->
        <div class="ticket-footer">
          <p class="ticket-thanks">*** Gracias por su visita ***</p>
          <p class="ticket-fiscal">Sujeto a facturación dentro del mes corriente.</p>
        </div>

      </div>

      <!-- Acción Inferior: Imprimir Ticket -->
      <div class="ticket-panel-actions">
        <button class="btn-print-ticket"
                id="btnImprimirTicket"
                type="button"
                data-folio="<?php echo htmlspecialchars(ltrim($folioTexto, '#')); ?>"
                title="Imprimir comprobante de venta">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="6" height="4"></rect>
          </svg>
          <span>Imprimir</span>
        </button>
      </div>

    </aside>

    <!-- Overlay desenfocado para vista móvil -->
    <div class="cuenta-backdrop" id="cierreBackdrop" aria-hidden="true"></div>

  </main>

  <!-- Script Principal con la lógica requerida -->
  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
