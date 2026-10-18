<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';

try {
    $usuarioModel = new Usuario($pdo);
    $personal = $usuarioModel->obtenerTodos();
    if (empty($personal)) {
        $personal = [
            ['id_usuario' => 'U01', 'nombre_completo' => 'Ana Ruiz', 'usuario' => 'aruiz', 'nombre_rol' => 'Administrador'],
            ['id_usuario' => 'U02', 'nombre_completo' => 'Luis Gil', 'usuario' => 'lgil', 'nombre_rol' => 'Personal de servicio']
        ];
    }
} catch (PDOException $e) {
    die("Error al consultar el personal: " . $e->getMessage());
}

$pageTitle = 'NIXGARU – Gestión de Cuentas de Personal';
require_once '../layouts/sidebar.php';
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
              <span class="stat-number"><?php echo count($personal); ?> Trabajadores</span>
              <span class="stat-badge-active">Activos</span>
            </div>
          </div>

          <!-- Tarjeta 2: Estructura de Roles -->
          <div class="admin-stat-card">
            <span class="stat-label">ESTRUCTURA DE ROLES</span>
            <div class="stat-value-group">
              <span class="stat-number">5 Roles del sistema</span>
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
            <span class="table-count-badge" id="personalCountBadge">Total: <?php echo count($personal); ?> mostrados</span>
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
                  <tr>
                    <td class="cell-id"><?php echo htmlspecialchars($emp['id_usuario']); ?></td>
                    <td class="cell-name"><?php echo htmlspecialchars($emp['nombre_completo']); ?></td>
                    <td class="cell-user"><?php echo htmlspecialchars($emp['usuario']); ?></td>
                    <td class="cell-role">
                      <span class="role-pill"><?php echo htmlspecialchars($emp['nombre_rol']); ?></span>
                    </td>
                    <td class="cell-acciones">
                      <button class="btn-action-edit" title="Editar empleado" type="button">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </button>
                      <button class="btn-action-delete" title="Eliminar empleado" type="button">
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
            <span>Mostrando <?php echo count($personal); ?> de <?php echo count($personal); ?> cuentas de personal activas</span>
          </div>

        </div>

      </div>
    </main>

  </div>

  <!-- MODAL: REGISTRAR NUEVO EMPLEADO (Oculto por defecto) -->
  <div id="modalNuevoEmpleado" class="modal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title">Nueva Cuenta</h3>
        <p class="modal-subtitle">Registra un nuevo empleado asignando credenciales de acceso.</p>
      </div>
      
      <form id="formNuevoEmpleado" class="modal-form">
        <!-- Campo: Nombre Completo -->
        <div class="form-group">
          <label for="inputNombreEmpleado" class="form-label">Nombre Completo</label>
          <input type="text" id="inputNombreEmpleado" class="form-input" placeholder="Ej. Ana Ruiz" required>
        </div>

        <!-- Campo: Nombre de Usuario -->
        <div class="form-group">
          <label for="inputUsuarioEmpleado" class="form-label">Nombre de Usuario</label>
          <input type="text" id="inputUsuarioEmpleado" class="form-input" placeholder="Ej. aruiz" required>
        </div>

        <!-- Campo: Contraseña -->
        <div class="form-group">
          <label for="inputPasswordEmpleado" class="form-label">Contraseña</label>
          <input type="password" id="inputPasswordEmpleado" class="form-input" placeholder="••••••••" required>
        </div>

        <!-- Campo: Rol Asignado -->
        <div class="form-group">
          <label for="selectRolEmpleado" class="form-label">Rol Asignado</label>
          <div class="select-wrapper">
            <select id="selectRolEmpleado" class="form-select" required>
              <option value="" disabled selected>Seleccionar Rol...</option>
              <option value="Administrador">Administrador</option>
              <option value="Personal de servicio">Personal de servicio</option>
              <option value="Cajero">Cajero</option>
              <option value="Cocinero">Cocinero</option>
              <option value="Barista">Barista</option>
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

  <script src="../../public/js/main.js"></script>
</body>
</html>

