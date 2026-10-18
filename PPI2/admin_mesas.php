<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NIXGARU – Administración de Mesas</title>
  <!-- Preconexión y carga de Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="admin-body">

  <div class="admin-layout">

    <!-- BARRA LATERAL (SIDEBAR ADMIN OSCURA) -->
    <aside class="admin-sidebar">
      
      <!-- Logotipo Admin -->
      <div class="admin-brand">
        <div class="admin-brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
        </div>
        <div class="admin-brand-info">
          <span class="admin-brand-title">NIXGARU</span>
          <span class="admin-brand-sub">ADMINISTRACION</span>
        </div>
      </div>

      <!-- Menú de Navegación del Administrador -->
      <nav class="admin-nav">
        <!-- Catálogo y Menú (Enlace a admin_catalogo.php) -->
        <a href="admin_catalogo.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span>Catálogo y Menú</span>
        </a>

        <!-- Gestión de Mesas (ACTIVO: Fondo blanco, texto oscuro, esquinas redondeadas) -->
        <a href="admin_mesas.php" class="admin-nav-item active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3h18v18H3zM3 9h18M9 21V9"></path>
          </svg>
          <span>Gestión de Mesas</span>
        </a>

        <!-- Control de Inventarios (Enlace a admin_inventario.php) -->
        <a href="admin_inventario.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
          <span>Control de Inventarios</span>
        </a>

        <!-- Cuentas de Personal (Enlace a admin_personal.php) -->
        <a href="admin_personal.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span>Cuentas de Personal</span>
        </a>

        <!-- Análisis y Reportes (Enlace a admin_dashboard.php) -->
        <a href="admin_dashboard.php" class="admin-nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          <span>Análisis y Reportes</span>
        </a>
      </nav>

      <!-- Perfil de Administrador en el pie de la barra lateral -->
      <div class="admin-sidebar-profile">
        <div class="admin-profile-avatar">ML</div>
        <div class="admin-profile-meta">
          <span class="admin-profile-name">María López</span>
          <span class="admin-profile-role">Administradora</span>
        </div>
      </div>

    </aside>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-content">
      <div class="admin-content-card">
        
        <!-- Encabezado de la Pantalla -->
        <div class="admin-content-header">
          <div class="admin-title-group">
            <h1 class="admin-page-title">Administración de registro de mesas</h1>
            <p class="admin-page-subtitle">Configura la distribución del salón, define la capacidad de comensales y gestiona los estados operativos</p>
          </div>
          <button class="btn-add-table" id="btnAbrirModalMesa">
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
              <span class="stat-number">8 Mesas</span>
              <span class="stat-badge-active">Activas</span>
            </div>
          </div>

          <!-- Tarjeta 2: Capacidad Máxima -->
          <div class="admin-stat-card">
            <span class="stat-label">CAPACIDAD MÁXIMA</span>
            <div class="stat-value-group">
              <span class="stat-number">24 personas</span>
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
            <span class="table-count-badge">Total: 4 mostradas</span>
          </div>

          <!-- Tabla de Datos -->
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
                
                <!-- Fila 1 -->
                <tr>
                  <td class="cell-id">01</td>
                  <td class="cell-name">Mesa 1</td>
                  <td class="cell-estado">
                    <span class="admin-status-pill status-libre">LIBRE</span>
                  </td>
                  <td class="cell-acciones">
                    <button class="btn-action-edit" title="Editar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button class="btn-action-delete" title="Eliminar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </td>
                </tr>

                <!-- Fila 2 -->
                <tr>
                  <td class="cell-id">02</td>
                  <td class="cell-name">Mesa 2</td>
                  <td class="cell-estado">
                    <span class="admin-status-pill status-ocupada">OCUPADA</span>
                  </td>
                  <td class="cell-acciones">
                    <button class="btn-action-edit" title="Editar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button class="btn-action-delete" title="Eliminar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </td>
                </tr>

                <!-- Fila 3 -->
                <tr>
                  <td class="cell-id">03</td>
                  <td class="cell-name">Mesa 3</td>
                  <td class="cell-estado">
                    <span class="admin-status-pill status-reservada">RESERVADA</span>
                  </td>
                  <td class="cell-acciones">
                    <button class="btn-action-edit" title="Editar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button class="btn-action-delete" title="Eliminar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </td>
                </tr>

                <!-- Fila 4 -->
                <tr>
                  <td class="cell-id">04</td>
                  <td class="cell-name">Mesa 4</td>
                  <td class="cell-estado">
                    <span class="admin-status-pill status-libre">LIBRE</span>
                  </td>
                  <td class="cell-acciones">
                    <button class="btn-action-edit" title="Editar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button class="btn-action-delete" title="Eliminar mesa">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>

          <!-- Pie de la Tabla -->
          <div class="admin-table-footer">
            <span>Mostrando 4 de 8 mesas activas registradas en el sistema</span>
          </div>

        </div>

      </div>
    </main>

  </div>

  <!-- MODAL: REGISTRAR NUEVA MESA (Oculto por defecto) -->
  <div id="modalNuevaMesa" class="modal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title">Registrar Nueva Mesa</h3>
      </div>
      
      <form id="formNuevaMesa" class="modal-form">
        <!-- Campo: Número de Mesa -->
        <div class="form-group">
          <label for="inputNumeroMesa" class="form-label">Número de mesa</label>
          <input type="text" id="inputNumeroMesa" class="form-input" placeholder="Ej. Mesa 5" required>
        </div>

        <!-- Campo: Estado Operativo -->
        <div class="form-group">
          <label for="selectEstadoMesa" class="form-label">Estado</label>
          <div class="select-wrapper">
            <select id="selectEstadoMesa" class="form-select">
              <option value="LIBRE">LIBRE</option>
              <option value="OCUPADA">OCUPADA</option>
              <option value="RESERVADA">RESERVADA</option>
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

  <script src="JS/main.js"></script>
</body>
</html>

