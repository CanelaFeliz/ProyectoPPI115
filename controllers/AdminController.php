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
require_once __DIR__ . '/../models/Cuenta.php';

class AdminController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Alterna la disponibilidad de un producto en el menú
     */
    public function cambiarDisponibilidad(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $codigo = isset($data['codigo']) ? trim((string)$data['codigo']) : '';
        $disponibilidad = isset($data['disponibilidad']) ? (int)$data['disponibilidad'] : 0;

        if (empty($codigo)) {
            echo json_encode(['success' => false, 'error' => 'Código de producto requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $productoModel = new Producto($this->pdo);
            $actualizado = $productoModel->actualizarDisponibilidad($codigo, $disponibilidad);

            echo json_encode([
                'success' => $actualizado,
                'codigo' => $codigo,
                'disponibilidad' => $disponibilidad,
                'message' => $actualizado ? 'Disponibilidad actualizada' : 'No se pudo actualizar'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Ajusta la cantidad de stock de un ingrediente y recalcula su estado
     */
    public function ajustarStock(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $codigo = isset($data['codigo_ingrediente']) ? trim((string)$data['codigo_ingrediente']) : (isset($data['codigo']) ? trim((string)$data['codigo']) : '');
        $cantidad = isset($data['cantidad']) ? (float)$data['cantidad'] : 0.00;

        if (empty($codigo)) {
            echo json_encode(['success' => false, 'error' => 'Código de ingrediente requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $inventarioModel = new Inventario($this->pdo);
            $actualizado = $inventarioModel->actualizarStock($codigo, $cantidad);

            echo json_encode([
                'success' => $actualizado,
                'codigo_ingrediente' => $codigo,
                'nueva_cantidad' => $cantidad,
                'message' => $actualizado ? 'Stock de ingrediente actualizado' : 'No se pudo actualizar el stock'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Actualiza el estado operativo de una mesa
     */
    public function actualizarEstadoMesa(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $idMesa = isset($data['id_mesa']) ? $data['id_mesa'] : (isset($data['numero_mesa']) ? $data['numero_mesa'] : '');
        $nuevoEstado = isset($data['estado']) ? trim((string)$data['estado']) : 'Disponible';

        if (empty($idMesa)) {
            echo json_encode(['success' => false, 'error' => 'Identificador de mesa requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $mesaModel = new Mesa($this->pdo);
            $actualizado = $mesaModel->actualizarEstado($idMesa, $nuevoEstado);

            echo json_encode([
                'success' => $actualizado,
                'id_mesa' => $idMesa,
                'nuevo_estado' => $nuevoEstado
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Registra una nueva mesa
     */
    public function crearMesa(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $numeroMesa = isset($data['numero_mesa']) ? trim((string)$data['numero_mesa']) : '';
        $capacidad = isset($data['capacidad']) ? (int)$data['capacidad'] : 4;
        $estado = isset($data['estado']) ? trim((string)$data['estado']) : 'Disponible';

        if (empty($numeroMesa)) {
            echo json_encode(['success' => false, 'error' => 'Número de mesa requerido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $mesaModel = new Mesa($this->pdo);
            $idMesa = $mesaModel->crear($numeroMesa, $capacidad, $estado);

            echo json_encode([
                'success' => true,
                'id_mesa' => $idMesa,
                'numero_mesa' => $numeroMesa,
                'message' => 'Mesa registrada con éxito'
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
                'success' => true,
                'kpis' => $kpis,
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

