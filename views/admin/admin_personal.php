<?php
/**
 * ==============================================================================
 * NIXGARU – Cafetería | Módulo Administrativo
 * Pantalla: Gestión de Cuentas de Personal (admin_personal.php)
 * Cumple con RF13 (Gestión de cuentas y roles) y RNF06 (Cifrado de contraseñas)
 * ==============================================================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';

try {
    $usuarioModel = new Usuario($pdo);
    $personal = $usuarioModel->obtenerTodos();
    $rolesSistema = $usuarioModel->obtenerRoles();
    if (empty($rolesSistema)) {
        $rolesSistema = [
            ['id_rol' => 1, 'nombre_rol' => 'Administrador'],
            ['id_rol' => 2, 'nombre_rol' => 'Cajero'],
            ['id_rol' => 3, 'nombre_rol' => 'Mesero'],
            ['id_rol' => 4, 'nombre_rol' => 'Barista'],
            ['id_rol' => 5, 'nombre_rol' => 'Cocina'],
        ];
    }
} catch (PDOException $e) {
    die("Error al consultar el personal: " . $e->getMessage());
}

$totalPersonal = count($personal);
$totalRoles = count($rolesSistema);

$pageTitle = 'NIXGARU – Gestión de Cuentas de Personal';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-content">
      <div class="admin-content-card">
        
        <!-- Encabezado de la Pantalla: Título y Botón Agregar Empleado -->
        <div class="admin-content-header">
          <div class="admin-title-group">
            <h1 class="admin-page-title">Gestión de Cuentas de Personal</h1>
            <p class="admin-page-subtitle">Administra el personal del sistema, asigna roles de servicio y controla accesos</p>
          </div>
          <button class="btn-add-table" id="btnAbrirModalEmpleado" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Agregar Empleado</span>
          </button>
        </div>

        <!-- Tarjetas Estadísticas Superiores -->
        <div class="admin-stats-row">
          
          <!-- Tarjeta 1: Personal Registrado -->
          <div class="admin-stat-card">
            <span class="stat-label">PERSONAL REGISTRADO</span>
            <div class="stat-value-group">
              <span class="stat-number" id="statTotalPersonal"><?php echo $totalPersonal; ?> Trabajadores</span>
              <span class="stat-badge-active">Activos</span>
            </div>
          </div>

          <!-- Tarjeta 2: Estructura de Roles -->
          <div class="admin-stat-card">
            <span class="stat-label">ESTRUCTURA DE ROLES</span>
            <div class="stat-value-group">
              <span class="stat-number"><?php echo $totalRoles; ?> Roles del sistema</span>
            </div>
          </div>

        </div>

        <!-- Contenedor de la Tabla: Listado de Personal -->
        <div class="admin-table-container">
          
          <div class="admin-table-header">
            <div class="table-header-left">
              <h2 class="table-card-title">Listado de Personal</h2>
              <p class="table-card-subtitle">Cuentas de usuario configuradas con accesos activos para el servicio de cafetería</p>
            </div>
            <span class="table-count-badge" id="personalCountBadge">Total: <?php echo $totalPersonal; ?> mostrados</span>
          </div>

          <!-- Tabla de Datos -->
          <div class="table-responsive">
            <table class="admin-data-table">
              <thead>
                <tr>
                  <th class="col-id">ID</th>
                  <th class="col-emp-name">Nombre Completo</th>
                  <th class="col-emp-user">Usuario</th>
                  <th class="col-emp-role">Rol Asignado</th>
                  <th class="col-acciones">Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaPersonalBody">
                <?php foreach ($personal as $emp): ?>
                  <?php
                    $idEmp = (int)$emp['id_usuario'];
                    $nombreEmp = (string)$emp['nombre_completo'];
                    $usuarioEmp = (string)$emp['nombre_usuario'];
                    $rolEmp = (string)$emp['nombre_rol'];
                  ?>
                  <tr
                    data-id="<?php echo $idEmp; ?>"
                    data-nombre="<?php echo htmlspecialchars($nombreEmp); ?>"
                    data-usuario="<?php echo htmlspecialchars($usuarioEmp); ?>"
                    data-rol="<?php echo htmlspecialchars($rolEmp); ?>"
                  >
                    <td class="cell-id"><?php echo htmlspecialchars((string)$idEmp); ?></td>
                    <td class="cell-name"><?php echo htmlspecialchars($nombreEmp); ?></td>
                    <td class="cell-user"><?php echo htmlspecialchars($usuarioEmp); ?></td>
                    <td class="cell-role">
                      <span class="role-pill"><?php echo htmlspecialchars($rolEmp); ?></span>
                    </td>
                    <td class="cell-acciones">
                      <button
                        class="btn-action-edit"
                        title="Editar empleado"
                        type="button"
                        data-id="<?php echo $idEmp; ?>"
                        data-nombre="<?php echo htmlspecialchars($nombreEmp); ?>"
                        data-usuario="<?php echo htmlspecialchars($usuarioEmp); ?>"
                        data-rol="<?php echo htmlspecialchars($rolEmp); ?>"
                      >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </button>
                      <button
                        class="btn-action-delete"
                        title="Eliminar empleado"
                        type="button"
                        data-id="<?php echo $idEmp; ?>"
                        data-nombre="<?php echo htmlspecialchars($nombreEmp); ?>"
                        data-usuario="<?php echo htmlspecialchars($usuarioEmp); ?>"
                        data-rol="<?php echo htmlspecialchars($rolEmp); ?>"
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
            <span id="personalFooterCountText">Mostrando <?php echo $totalPersonal; ?> de <?php echo $totalPersonal; ?> cuentas de personal activas</span>
          </div>

        </div>

      </div>
    </main>

  </div>

  <!-- MODAL REUTILIZABLE: REGISTRAR / EDITAR CUENTA DE PERSONAL (RF13, RNF06) -->
  <div id="modalNuevoEmpleado" class="modal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title" id="tituloModalEmpleado">Nueva Cuenta</h3>
        <p class="modal-subtitle" id="subtituloModalEmpleado">Registra un nuevo empleado asignando credenciales de acceso.</p>
      </div>

      <div id="errorModalEmpleado" role="alert" style="display: none; background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; font-weight: 600; margin-bottom: 16px;"></div>
      
      <form id="formNuevoEmpleado" class="modal-form">
        <input type="hidden" id="inputIdEmpleado" name="id_usuario" value="">

        <!-- Campo: Nombre Completo -->
        <div class="form-group">
          <label for="inputNombreEmpleado" class="form-label">Nombre Completo</label>
          <input type="text" id="inputNombreEmpleado" name="nombre_completo" class="form-input" placeholder="Ej. Ana Ruiz" required>
        </div>

        <!-- Campo: Nombre de Usuario -->
        <div class="form-group">
          <label for="inputUsuarioEmpleado" class="form-label">Nombre de Usuario</label>
          <input type="text" id="inputUsuarioEmpleado" name="nombre_usuario" class="form-input" placeholder="Ej. aruiz" required>
        </div>

        <!-- Campo: Contraseña -->
        <div class="form-group">
          <label for="inputPasswordEmpleado" class="form-label">Contraseña</label>
          <input type="password" id="inputPasswordEmpleado" name="password" class="form-input" placeholder="••••••••" required>
          <small id="ayudaPasswordEmpleado" style="display: none; color: var(--color-texto-muted); font-size: 0.78rem; margin-top: 2px;">Deje en blanco si no desea cambiar la contraseña actual.</small>
        </div>

        <!-- Campo: Rol Asignado -->
        <div class="form-group">
          <label for="selectRolEmpleado" class="form-label">Rol Asignado</label>
          <div class="select-wrapper">
            <select id="selectRolEmpleado" name="rol" class="form-select" required>
              <option value="" disabled selected>Seleccionar Rol...</option>
              <?php foreach ($rolesSistema as $rolItem): ?>
                <option value="<?php echo htmlspecialchars((string)$rolItem['nombre_rol']); ?>">
                  <?php echo htmlspecialchars((string)$rolItem['nombre_rol']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Botones de Acción del Modal -->
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" id="btnCancelarModalEmpleado">Cancelar</button>
          <button type="submit" class="btn-modal-save" id="btnGuardarEmpleado">Guardar Cuenta</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../public/js/main.js?v=<?php echo file_exists(__DIR__ . '/../../public/js/main.js') ? filemtime(__DIR__ . '/../../public/js/main.js') : time(); ?>"></script>
</body>
</html>
