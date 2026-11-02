<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Personal de Servicio y Cajero
 * Pantalla: Bandeja de Recepción de Pedidos Web y Asignación de Mesas (pedidos_web.php)
 * Requerimientos: RF08, RF16, RF17, RNF04
 * ==============================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    }
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Mesa.php';

try {
    $pedidoModel = new Pedido($pdo);
    $pedidosWeb = $pedidoModel->obtenerPedidosWeb();

    $mesaModel = new Mesa($pdo);
    $mesasDisponibles = $mesaModel->obtenerDisponibles();
} catch (PDOException $e) {
    die("Error al consultar la bandeja de pedidos web: " . $e->getMessage());
}

$currentPage = 'pedidos_web.php';
$pageTitle = 'NIXGARU – Recepción de Pedidos Web | Personal de Servicio';
$headerTitle = 'Recepción de Pedidos Web';

require_once __DIR__ . '/../layouts/header.php';
?>

  <!-- Layout Principal de 2 Columnas (70% Bandeja / 30% Detalle Lateral) -->
  <main class="mesas-layout web-orders-layout" id="webOrdersLayout">

    <!-- COLUMNA IZQUIERDA (70%): Bandeja de Pedidos Web -->
    <section class="mesas-panel web-orders-panel">
      
      <div class="panel-header">
        <div class="panel-title-group">
          <h2 class="panel-title">Bandeja de Pedidos en Línea</h2>
          <p class="panel-subtitle">Gestión de órdenes de envío a domicilio y retiro en sucursal</p>
        </div>
        <span class="legend-badge badge-proceso" id="contadorPedidosWebBadge">
          <?php echo count($pedidosWeb); ?> <?php echo count($pedidosWeb) === 1 ? 'Pedido registrado' : 'Pedidos registrados'; ?>
        </span>
      </div>

      <!-- Barra de Filtros Dinámicos y Buscador Rápido -->
      <div class="web-orders-toolbar">
        <div class="web-filter-pills" role="tablist" aria-label="Filtrar pedidos web por modalidad">
          <button type="button" class="web-order-filter-pill active" data-filter-tipo="Todos">Todos</button>
          <button type="button" class="web-order-filter-pill" data-filter-tipo="Envío a domicilio">Envío a domicilio</button>
          <button type="button" class="web-order-filter-pill" data-filter-tipo="Retiro en el local">Retiro en el local</button>
        </div>

        <div class="web-search-group">
          <div class="web-search-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="search" id="inputBuscarPedidoWeb" class="web-search-input" placeholder="Buscar folio o cliente..." aria-label="Buscar por folio o cliente">
          </div>
          <button type="button" id="btnRefrescarPedidosWeb" class="btn-refresh-web" title="Actualizar bandeja en tiempo real">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Tabla Interactiva de Pedidos Web -->
      <div class="web-orders-table-wrapper">
        <table class="web-orders-table" aria-label="Listado de pedidos web">
          <thead>
            <tr>
              <th class="col-web-folio">Folio</th>
              <th class="col-web-fecha">Fecha / Hora</th>
              <th class="col-web-cliente">Cliente</th>
              <th class="col-web-modalidad">Modalidad</th>
              <th class="col-web-mesa">Mesa</th>
              <th class="col-web-total">Total</th>
              <th class="col-web-abono">Abono</th>
              <th class="col-web-saldo">Saldo</th>
              <th class="col-web-estado">Estado</th>
            </tr>
          </thead>
          <tbody id="tbodyPedidosWeb">
            <?php if (!empty($pedidosWeb)): ?>
              <?php foreach ($pedidosWeb as $ped): ?>
                <?php
                  $idPed = (int)$ped['id_pedido'];
                  $folio = '#NIX-' . str_pad((string)$idPed, 5, '0', STR_PAD_LEFT);
                  $fechaFmt = !empty($ped['fecha_hora']) ? date('d/m/Y H:i', strtotime((string)$ped['fecha_hora'])) : '---';
                  $nombreCli = trim(($ped['nombre_cliente'] ?? '') . ' ' . ($ped['apellido_cliente'] ?? '')) ?: 'Cliente Web';
                  $telCli = (string)($ped['telefono_cliente'] ?? '');
                  $tipoOrd = (string)($ped['tipo_orden'] ?? 'Retiro en el local');
                  $esDom = ($tipoOrd === 'Envío a domicilio');
                  $modalidadCorta = $esDom ? 'Domicilio' : 'Retiro';
                  $numMesa = $ped['numero_mesa'] ?? $ped['id_mesa'] ?? null;
                  $tieneMesaAsig = ($numMesa !== null && $numMesa !== '' && (int)$numMesa > 0);
                  $estadoPed = (string)($ped['estado_pedido'] ?? 'Pendiente');
                  $stLower = mb_strtolower($estadoPed, 'UTF-8');
                  $claseEstado = 'st-pendiente';
                  if (str_contains($stLower, 'prepar')) $claseEstado = 'st-preparacion';
                  elseif (str_contains($stLower, 'listo')) $claseEstado = 'st-listo';
                  elseif (str_contains($stLower, 'camino')) $claseEstado = 'st-camino';
                  elseif (str_contains($stLower, 'entreg') || str_contains($stLower, 'pagad')) $claseEstado = 'st-pagado';
                ?>
                <tr class="web-order-row"
                    data-id-pedido="<?php echo $idPed; ?>"
                    data-tipo-orden="<?php echo htmlspecialchars($tipoOrd); ?>"
                    data-search="<?php echo htmlspecialchars(mb_strtolower($folio . ' ' . $nombreCli . ' ' . $telCli, 'UTF-8')); ?>">
                  <td class="col-web-folio"><span class="web-folio-code"><?php echo htmlspecialchars($folio); ?></span></td>
                  <td class="col-web-fecha" style="white-space: nowrap; color: #52525B;"><?php echo htmlspecialchars($fechaFmt); ?></td>
                  <td class="col-web-cliente">
                    <div class="web-client-cell">
                      <span class="web-client-name"><?php echo htmlspecialchars($nombreCli); ?></span>
                      <span class="web-client-phone"><?php echo htmlspecialchars($telCli ?: 'Sin teléfono'); ?></span>
                      <div class="web-client-mobile-meta">
                        <span class="mobile-meta-chip <?php echo $esDom ? 'modality-domicilio' : 'modality-retiro'; ?>">
                          <?php echo htmlspecialchars($modalidadCorta); ?>
                        </span>
                        <?php if ($tieneMesaAsig): ?>
                          <span class="mobile-meta-mesa">Mesa <?php echo htmlspecialchars((string)$numMesa); ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td class="col-web-modalidad">
                    <span class="modality-pill <?php echo $esDom ? 'modality-domicilio' : 'modality-retiro'; ?>">
                      <?php echo htmlspecialchars($tipoOrd); ?>
                    </span>
                  </td>
                  <td class="col-web-mesa">
                    <?php if ($tieneMesaAsig): ?>
                      <span class="mesa-assigned-chip">Mesa <?php echo htmlspecialchars((string)$numMesa); ?></span>
                    <?php else: ?>
                      <span class="mesa-unassigned-chip">Sin asignar</span>
                    <?php endif; ?>
                  </td>
                  <td class="col-web-total" style="font-weight: 700;">$<?php echo number_format((float)$ped['total_pagar'], 2); ?></td>
                  <td class="col-web-abono" style="color: #059669; font-weight: 600;">$<?php echo number_format((float)$ped['monto_abonado'], 2); ?></td>
                  <td class="col-web-saldo" style="color: <?php echo ((float)$ped['saldo_pendiente'] > 0) ? '#C52E45' : '#059669'; ?>; font-weight: 700;">
                    $<?php echo number_format((float)$ped['saldo_pendiente'], 2); ?>
                  </td>
                  <td class="col-web-estado">
                    <span class="order-status-pill <?php echo $claseEstado; ?>"><?php echo htmlspecialchars($estadoPed); ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr id="filaSinPedidosWeb">
                <td colspan="9" style="text-align: center; padding: 42px 16px; color: var(--color-texto-muted); border: 1.5px dashed var(--color-borde-suave); border-radius: 14px;">
                  No se encontraron pedidos web registrados en este momento.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </section>

    <!-- COLUMNA DERECHA / DRAWER (30%): Detalle del Pedido Web Seleccionado -->
    <aside class="cuenta-panel web-order-sidebar" id="webOrderSidebar" aria-label="Detalle del pedido web seleccionado">
      <div class="cuenta-card">
        
        <div class="cuenta-header">
          <div class="cuenta-header-left">
            <h2 class="cuenta-title" id="detWebTituloFolio">Detalle de Pedido</h2>
          </div>
          <button class="cuenta-close-btn" id="webOrderCloseBtn" type="button" aria-label="Cerrar panel de detalle" title="Cerrar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Estado vacío cuando ningún pedido web está seleccionado -->
        <div class="cuenta-empty-state" id="webOrderEmptyState">
          <div class="empty-state-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
          </div>
          <h3 class="empty-state-title">Selecciona un pedido web</h3>
          <p class="empty-state-desc">Haz clic en cualquier orden de la bandeja para revisar su desglose, actualizar su estado o asignarle mesa.</p>
        </div>

        <!-- Contenido de detalle del pedido seleccionado -->
        <div id="webOrderDetailContent" class="cuenta-detalle-container" style="display: none; flex-direction: column; flex: 1 1 auto; min-height: 0; width: 100%; min-width: 0; overflow: hidden;">
          
          <div class="web-detail-scroll">
            <!-- Datos de contacto del cliente -->
            <div class="web-info-box">
              <span class="web-info-title">Datos del Cliente</span>
              <div class="web-info-line">
                <span>Cliente:</span>
                <strong id="detWebClienteNombre">---</strong>
              </div>
              <div class="web-info-line">
                <span>Teléfono:</span>
                <strong id="detWebClienteTelefono">---</strong>
              </div>
              <div class="web-info-line">
                <span>Correo:</span>
                <strong id="detWebClienteCorreo">---</strong>
              </div>
              <div class="web-info-line">
                <span>Modalidad:</span>
                <strong id="detWebModalidad">---</strong>
              </div>
              <div class="web-info-line">
                <span>Mesa física:</span>
                <strong id="detWebMesaAsignada">Sin asignar</strong>
              </div>
            </div>

            <!-- Bloque condicional de Datos Logísticos (visible en Envío a domicilio) -->
            <div class="web-info-box logistics" id="detWebBloqueLogistico" style="display: none;">
              <span class="web-info-title" style="color: #9A482B;">Datos Logísticos de Entrega</span>
              <div class="web-info-line">
                <span>Municipio:</span>
                <strong id="detWebMunicipio">San Salvador</strong>
              </div>
              <div class="web-info-line">
                <span>Dirección:</span>
                <strong id="detWebDireccion">---</strong>
              </div>
              <div class="web-info-line">
                <span>Referencia:</span>
                <strong id="detWebReferencia">---</strong>
              </div>
            </div>

            <!-- Lista de Productos del Pedido -->
            <div>
              <div class="cuenta-section-label" style="margin-bottom: 8px;">PRODUCTOS DEL PEDIDO</div>
              <div id="detWebListaProductos" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Se inyecta dinámicamente -->
              </div>
            </div>

            <!-- Control de Estado del Pedido (RF08, RF16) -->
            <div class="web-info-box">
              <label for="selectEstadoPedidoWeb" class="web-info-title">Estado del Pedido</label>
              <div class="web-status-control-row">
                <select id="selectEstadoPedidoWeb" class="web-status-select">
                  <option value="Pendiente">Pendiente</option>
                  <option value="En preparación">En preparación</option>
                  <option value="Listo">Listo</option>
                  <option value="En camino">En camino</option>
                  <option value="Entregado">Entregado</option>
                  <option value="Pagado">Pagado</option>
                </select>
                <button type="button" id="btnActualizarEstadoWeb" class="btn-save-web-status">Actualizar</button>
              </div>
            </div>
          </div>

          <!-- Resumen Financiero -->
          <div class="cuenta-totals" style="margin-top: 12px; padding-top: 12px; gap: 6px;">
            <div class="totals-line">
              <span>Subtotal</span>
              <span id="detWebSubtotal">$0.00</span>
            </div>
            <div class="totals-line">
              <span>Propina (10%)</span>
              <span id="detWebPropina">$0.00</span>
            </div>
            <div class="totals-line" style="font-weight: 700; color: var(--color-negro-suave);">
              <span>Total General</span>
              <span id="detWebTotal">$0.00</span>
            </div>
            <div class="totals-line" style="color: #059669; font-weight: 600;">
              <span>Monto Abonado (Web)</span>
              <span id="detWebAbono">$0.00</span>
            </div>
            <div class="totals-line total-highlight" style="margin-top: 2px; padding-top: 6px;">
              <span class="total-text" style="font-size: 1rem;">Saldo Pendiente</span>
              <span class="total-amount" id="detWebSaldo" style="font-size: 1.3rem; color: var(--color-rojo-frambuesa);">$0.00</span>
            </div>
          </div>

          <!-- Acciones Operativas (RF17) -->
          <div class="cuenta-actions" style="margin-top: 12px;">
            <button type="button" id="btnAbrirAsignarMesaWeb" class="btn-assign-mesa-web" style="display: none;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
              </svg>
              <span>Asignar a Mesa</span>
            </button>

            <div id="bloqueAccionesMesaAsignada" style="display: none; flex-direction: column; gap: 8px;">
              <a href="#" id="btnIrAgregarProductosWeb" class="btn-account-add" style="padding: 12px 16px; font-size: 0.86rem;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="16"></line>
                  <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <span>Agregar Productos en Mesa</span>
              </a>
              <a href="#" id="btnIrCierreCuentaWeb" class="btn-account-close" style="padding: 12px 16px; font-size: 0.86rem;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <span>Ir a Cierre de Cuenta</span>
              </a>
            </div>
          </div>

        </div>

      </div>
    </aside>

    <!-- Backdrop móvil para el drawer lateral -->
    <div class="cuenta-backdrop" id="webOrderBackdrop" aria-hidden="true"></div>

  </main>

  <!-- Modal: Asignar Pedido Web a Mesa Disponible (RF17) -->
  <div id="modalAsignarMesaWeb" class="service-modal-backdrop" aria-hidden="true">
    <div class="service-modal-card" role="dialog" aria-modal="true" aria-labelledby="tituloModalAsignarMesa">
      <h3 id="tituloModalAsignarMesa" style="font-size: 1.2rem; font-weight: 800; color: var(--color-negro-suave); margin-bottom: 6px;">
        Asignar Pedido Web a Mesa Disponible
      </h3>
      <p style="font-size: 0.84rem; color: var(--color-texto-muted); margin-bottom: 16px;">
        Orden <strong id="modalAsignarFolio" style="color: var(--color-rojo-frambuesa);">#NIX-00000</strong> · Cliente: <strong id="modalAsignarCliente">---</strong>
      </p>

      <!-- Alerta Camino Alternativo RF17 cuando no hay mesas disponibles -->
      <div id="alertaSinMesasDisponibles" class="rf17-empty-alert" role="alert">
        No hay mesas disponibles en este momento. Debe esperar a que se libere una mesa.
      </div>

      <!-- Alerta de error del servidor -->
      <div id="errorAsignarMesaWeb" class="rf17-error-alert" role="alert"></div>

      <form id="formAsignarMesaWeb">
        <input type="hidden" id="inputIdPedidoAsignarWeb" name="id_pedido" value="">

        <div style="margin-bottom: 18px;">
          <label for="selectMesaDisponibleWeb" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--color-negro-suave); margin-bottom: 6px;">
            Selecciona una mesa en estado Disponible:
          </label>
          <select id="selectMesaDisponibleWeb" name="id_mesa" required style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--color-borde-suave); border-radius: 12px; font-family: inherit; font-size: 0.9rem; font-weight: 600; color: var(--color-negro-suave); outline: none;">
            <?php if (!empty($mesasDisponibles)): ?>
              <?php foreach ($mesasDisponibles as $mDisp): ?>
                <option value="<?php echo (int)$mDisp['id_mesa']; ?>">
                  Mesa <?php echo (int)$mDisp['numero_mesa']; ?> — Capacidad: <?php echo (int)$mDisp['capacidad']; ?> personas (Disponible)
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="">Sin mesas disponibles</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="service-modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" id="btnCancelarAsignarMesaWeb" style="padding: 10px 18px; border-radius: 10px; border: 1.5px solid var(--color-borde-suave); background: var(--color-blanco); color: var(--color-negro-suave); font-weight: 600; font-size: 0.86rem; cursor: pointer;">
            Cancelar
          </button>
          <button type="submit" id="btnConfirmarAsignarMesaWeb" style="padding: 10px 20px; border-radius: 10px; border: none; background: var(--color-rojo-frambuesa); color: var(--color-blanco); font-weight: 700; font-size: 0.86rem; cursor: pointer;">
            Confirmar Asignación
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Estado inicial JSON hidratado desde SSR para sincronización inmediata -->
  <script id="pedidosWebInitialData" type="application/json"><?php echo json_encode($pedidosWeb, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_HEX_TAG); ?></script>

  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
