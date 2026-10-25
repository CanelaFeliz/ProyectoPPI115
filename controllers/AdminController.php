<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de Módulo Administrativo
 * controllers/AdminController.php
 * Gestiona operaciones administrativas del catálogo, personal, inventario y mesas
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Pedido.php';

class AdminController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Alterna el estado_disponibilidad de un producto en la tabla `Productos`
     */
    public function cambiarDisponibilidad(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $idProducto = $data['id_producto'] ?? ($data['codigo'] ?? ($data['id'] ?? 0));
        $estadoDisponibilidad = $data['estado_disponibilidad'] ?? ($data['disponibilidad'] ?? 'Disponible');

        if (empty($idProducto)) {
            echo json_encode(['success' => false, 'error' => 'ID de producto requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $productoModel = new Producto($this->pdo);
            $actualizado = $productoModel->actualizarDisponibilidad($idProducto, $estadoDisponibilidad);

            echo json_encode([
                'success'               => $actualizado,
                'id_producto'           => (int)$idProducto,
                'estado_disponibilidad' => $estadoDisponibilidad,
                'message'               => $actualizado ? 'Disponibilidad actualizada' : 'No se pudo actualizar'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Ajusta la cantidad_estimada de un ingrediente en la tabla `Inventarios`
     */
    public function ajustarStock(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $idIngrediente = $data['id_ingrediente'] ?? ($data['codigo_ingrediente'] ?? ($data['codigo'] ?? 0));
        $cantidadEstimada = isset($data['cantidad_estimada'])
            ? (float)$data['cantidad_estimada']
            : (isset($data['cantidad']) ? (float)$data['cantidad'] : 0.00);

        if (empty($idIngrediente)) {
            echo json_encode(['success' => false, 'error' => 'ID de ingrediente requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $inventarioModel = new Inventario($this->pdo);
            $actualizado = $inventarioModel->actualizarStock($idIngrediente, $cantidadEstimada);

            echo json_encode([
                'success'           => $actualizado,
                'id_ingrediente'    => (int)$idIngrediente,
                'cantidad_estimada' => $cantidadEstimada,
                'message'           => $actualizado ? 'Stock de ingrediente actualizado' : 'No se pudo actualizar el stock'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Actualiza el estado_mesa de una mesa en la tabla `Mesas`
     */
    public function actualizarEstadoMesa(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $idMesa = $data['id_mesa'] ?? ($data['numero_mesa'] ?? '');
        $nuevoEstado = isset($data['estado_mesa'])
            ? trim((string)$data['estado_mesa'])
            : (isset($data['estado']) ? trim((string)$data['estado']) : 'Disponible');

        if (empty($idMesa)) {
            echo json_encode(['success' => false, 'error' => 'Identificador de mesa requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $mesaModel = new Mesa($this->pdo);
            $actualizado = $mesaModel->actualizarEstado($idMesa, $nuevoEstado);

            echo json_encode([
                'success'     => $actualizado,
                'id_mesa'     => $idMesa,
                'estado_mesa' => $nuevoEstado
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Registra una nueva mesa en la tabla `Mesas`
     */
    public function crearMesa(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $numeroMesa = $data['numero_mesa'] ?? '';
        $capacidad = isset($data['capacidad']) ? (int)$data['capacidad'] : 4;
        $estadoMesa = isset($data['estado_mesa'])
            ? trim((string)$data['estado_mesa'])
            : (isset($data['estado']) ? trim((string)$data['estado']) : 'Disponible');

        if ($numeroMesa === '') {
            echo json_encode(['success' => false, 'error' => 'Número de mesa requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $mesaModel = new Mesa($this->pdo);
            $idMesa = $mesaModel->crear($numeroMesa, $capacidad, $estadoMesa);

            echo json_encode([
                'success'     => true,
                'id_mesa'     => $idMesa,
                'numero_mesa' => (int)preg_replace('/\D/', '', (string)$numeroMesa),
                'capacidad'   => $capacidad,
                'estado_mesa' => $estadoMesa,
                'message'     => 'Mesa registrada con éxito'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Retorna los KPIs del dashboard y el Top de ventas
     */
    public function obtenerDashboard(): void {
        try {
            $pedidoModel = new Pedido($this->pdo);
            $kpis = $pedidoModel->obtenerEstadisticasVentas();
            $top = $pedidoModel->obtenerProductosMasVendidos(5);

            echo json_encode([
                'success'       => true,
                'kpis'          => $kpis,
                'top_productos' => $top
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}

// Router de acciones
if (isset($_GET['action'])) {
    $adminController = new AdminController($pdo);
    switch ($_GET['action']) {
        case 'cambiar_disponibilidad':
            $adminController->cambiarDisponibilidad();
            break;
        case 'ajustar_stock':
            $adminController->ajustarStock();
            break;
        case 'actualizar_estado_mesa':
            $adminController->actualizarEstadoMesa();
            break;
        case 'crear_mesa':
            $adminController->crearMesa();
            break;
        case 'dashboard':
            $adminController->obtenerDashboard();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Acción administrativa no soportada'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
