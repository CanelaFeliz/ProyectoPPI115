<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Administración de Registro de Mesas (admin_mesas.php)
 * Cumple con RF05 (Registro, edición, capacidad y validación de unicidad)
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Mesa.php';

try {
    $mesaModel = new Mesa($pdo);
    $mesas = $mesaModel->obtenerTodas();
    $totalMesas = count($mesas);
    $capacidadTotal = !empty($mesas) ? (int)array_sum(array_column($mesas, 'capacidad')) : 0;
} catch (PDOException $e) {
    die("Error al consultar las mesas: " . $e->getMessage());
}

$pageTitle = 'NIXGARU – Administración de Mesas';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-content">
      <div class="admin-content-card">
        
        <!-- Encabezado de la Pantalla -->
        <div class="admin-content-header">
          <div class="admin-title-group">
            <h1 class="admin-page-title">Administración de registro de mesas</h1>
            <p class="admin-page-subtitle">Configura la distribución del salón, define la capacidad de comensales y gestiona los estados operativos</p>
          </div>
          <button class="btn-add-table" id="btnAbrirModalMesa" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Agregar nueva mesa</span>
          </button>
        </div>

        <!-- Tarjetas Estadísticas Superiores -->
        <div class="admin-stats-row">
          
          <!-- Tarjeta 1: Total de Mesas -->
          <div class="admin-stat-card">
            <span class="stat-label">TOTAL DE MESAS</span>
            <div class="stat-value-group">
              <span class="stat-number" id="statTotalMesas"><?php echo $totalMesas; ?> Mesas</span>
              <span class="stat-badge-active">Activas</span>
            </div>
          </div>

          <!-- Tarjeta 2: Capacidad Máxima -->
          <div class="admin-stat-card">
            <span class="stat-label">CAPACIDAD MÁXIMA</span>
            <div class="stat-value-group">
              <span class="stat-number" id="statCapacidadMaxima"><?php echo $capacidadTotal; ?> personas</span>
            </div>
          </div>

        </div>

        <!-- Contenedor de la Tabla: Distribución del Salón -->
        <div class="admin-table-container">
          
          <div class="admin-table-header">
            <div class="table-header-left">
              <h2 class="table-card-title">Distribución del Salón</h2>
              <p class="table-card-subtitle">Listado general de mesas físicas habilitadas para atención al público en tiempo real</p>
            </div>
            <span class="table-count-badge" id="mesasCountBadge">Total: <?php echo $totalMesas; ?> mostradas</span>
          </div>

          <!-- Tabla de Datos Dinámica -->
          <div class="table-responsive">
            <table class="admin-data-table">
              <thead>
                <tr>
                  <th class="col-id">ID</th>
                  <th class="col-mesa">Número de Mesa</th>
                  <th class="col-estado">Estado Operativo</th>
                  <th class="col-acciones">Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaMesasBody">
                
                <?php foreach ($mesas as $mesa): ?>
                  <?php
                    $idMesa = (int)$mesa['id_mesa'];
                    $numeroMesa = (int)$mesa['numero_mesa'];
                    $capacidadMesa = (int)($mesa['capacidad'] ?? 4);
                    $estadoMesa = (string)$mesa['estado_mesa'];
                    $estadoSlug = strtolower(trim($estadoMesa));
                    $pillClass = match (true) {
                        str_contains($estadoSlug, 'ocupad') => 'status-ocupada',
                        str_contains($estadoSlug, 'proces') || str_contains($estadoSlug, 'reserv') => 'status-reservada',
                        str_contains($estadoSlug, 'sin') => 'status-inactivo',
                        default => 'status-libre',
                    };
                  ?>
                  <tr
                    data-id="<?php echo $idMesa; ?>"
                    data-numero="<?php echo $numeroMesa; ?>"
                    data-capacidad="<?php echo $capacidadMesa; ?>"
                    data-estado="<?php echo htmlspecialchars($estadoMesa); ?>"
                  >
                    <td class="cell-id"><?php echo str_pad((string)$idMesa, 2, '0', STR_PAD_LEFT); ?></td>
                    <td class="cell-name">Mesa <?php echo htmlspecialchars((string)$numeroMesa); ?> <span style="font-size:12px; color:#6C757D; font-weight:normal;">(Cap. <?php echo $capacidadMesa; ?>)</span></td>
                    <td class="cell-estado">
                      <span class="admin-status-pill <?php echo $pillClass; ?>"><?php echo strtoupper(htmlspecialchars($estadoMesa)); ?></span>
                    </td>
                    <td class="cell-acciones">
                      <button
                        class="btn-action-edit"
                        title="Editar mesa"
                        type="button"
                        data-id="<?php echo $idMesa; ?>"
                        data-numero="<?php echo $numeroMesa; ?>"
                        data-capacidad="<?php echo $capacidadMesa; ?>"
                        data-estado="<?php echo htmlspecialchars($estadoMesa); ?>"
                      >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </button>
                      <button
                        class="btn-action-delete"
                        title="Eliminar mesa"
                        type="button"
                        data-id="<?php echo $idMesa; ?>"
                        data-numero="<?php echo $numeroMesa; ?>"
                        data-capacidad="<?php echo $capacidadMesa; ?>"
                        data-estado="<?php echo htmlspecialchars($estadoMesa); ?>"
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
            <span id="mesasFooterCountText">Mostrando <?php echo $totalMesas; ?> de <?php echo $totalMesas; ?> mesas activas registradas en el sistema</span>
          </div>

        </div>

      </div>
    </main>

  </div>

  <!-- MODAL REUTILIZABLE: REGISTRAR / EDITAR MESA (RF05) -->
  <div id="modalNuevaMesa" class="modal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title" id="tituloModalMesa">Registrar Nueva Mesa</h3>
        <p class="modal-subtitle" id="subtituloModalMesa">Define el número único de mesa, su capacidad y el estado inicial en salón.</p>
      </div>

      <div id="errorModalMesa" role="alert" style="display: none; background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; font-weight: 600; margin-bottom: 16px;"></div>
      
      <form id="formNuevaMesa" class="modal-form">
        <input type="hidden" id="inputIdMesa" name="id_mesa" value="">

        <!-- Campo: Número de Mesa -->
        <div class="form-group">
          <label for="inputNumeroMesa" class="form-label">Número de mesa</label>
          <input type="number" id="inputNumeroMesa" name="numero_mesa" class="form-input" min="1" step="1" placeholder="Ej. 9" required>
        </div>

        <!-- Campo: Capacidad de Comensales (RF05) -->
        <div class="form-group">
          <label for="inputCapacidadMesa" class="form-label">Capacidad (personas)</label>
          <input type="number" id="inputCapacidadMesa" name="capacidad" class="form-input" min="1" max="20" value="4" required>
        </div>

        <!-- Campo: Estado Operativo Homologado -->
        <div class="form-group">
          <label for="selectEstadoMesa" class="form-label">Estado operativo</label>
          <div class="select-wrapper">
            <select id="selectEstadoMesa" name="estado_mesa" class="form-select">
              <option value="Disponible">Disponible</option>
              <option value="Ocupada">Ocupada</option>
              <option value="En proceso">En proceso</option>
              <option value="Reservada">Reservada</option>
              <option value="Sin servicio">Sin servicio</option>
            </select>
          </div>
        </div>

        <!-- Botones de Acción del Modal -->
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" id="btnCancelarModal">Cancelar</button>
          <button type="submit" class="btn-modal-save" id="btnGuardarMesa">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
