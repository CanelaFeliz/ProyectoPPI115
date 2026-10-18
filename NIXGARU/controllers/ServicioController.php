<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de Personal de Servicio
 * controllers/ServicioController.php
 * ==============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Mesa.php';

class ServicioController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Captura el JSON enviado por el frontend, instancia Pedido y Mesa,
     * inserta los datos transaccionales de la orden y actualiza el estado de la mesa a 'En proceso'.
     */
    public function guardarOrden(): void {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $idMesa = isset($data['id_mesa']) ? (int)$data['id_mesa'] : 0;
        $productos = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];
        $idMesero = isset($data['id_mesero']) ? (int)$data['id_mesero'] : 1;
        $estadoMesa = isset($data['estado_mesa']) ? trim($data['estado_mesa']) : 'En proceso';
        $estadoPedido = isset($data['estado_pedido']) ? trim($data['estado_pedido']) : 'En preparación';

        if ($idMesa <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de mesa no válido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($productos)) {
            echo json_encode(['success' => false, 'error' => 'No hay productos en la orden'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $mesaModel = new Mesa($this->pdo);
            $pedidoModel = new Pedido($this->pdo);

            // Asegura que la mesa exista en la BD y obtiene su id_mesa real
            $idMesaReal = $mesaModel->asegurarMesa($idMesa, $estadoMesa);

            // Inserta la orden y sus detalles
            $idPedido = $pedidoModel->guardarOrden($idMesaReal, $productos, $idMesero, $estadoPedido);

            // Actualiza el estado de la mesa
            $mesaModel->actualizarEstado($idMesaReal, $estadoMesa);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'id_pedido' => $idPedido,
                'id_mesa' => $idMesaReal,
                'message' => 'Orden guardada y enviada a cocina con éxito'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error al guardar la orden: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Captura el JSON enviado por el frontend, instancia Pedido y Mesa,
     * registra el pago en cuentas, marca el pedido como 'Pagado' y la mesa como 'Disponible'.
     */
    public function cerrarCuenta(): void {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $idMesa = isset($data['id_mesa']) ? (int)$data['id_mesa'] : 0;
        $montoRecibido = isset($data['monto_recibido']) ? (float)$data['monto_recibido'] : 0.00;
        $idCajero = isset($data['id_cajero']) ? (int)$data['id_cajero'] : 1;

        if ($idMesa <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de mesa no válido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $mesaModel = new Mesa($this->pdo);
            $pedidoModel = new Pedido($this->pdo);

            // Asegura que la mesa exista en la BD y obtiene su id_mesa real
            $idMesaReal = $mesaModel->asegurarMesa($idMesa, 'Disponible');

            // Cierra la cuenta e inserta en la tabla cuentas
            $resCuenta = $pedidoModel->cerrarCuenta($idMesaReal, $montoRecibido, $idCajero);

            // Actualiza el estado de la mesa a 'Disponible'
            $mesaModel->actualizarEstado($idMesaReal, 'Disponible');

            $this->pdo->commit();

            echo json_encode(array_merge([
                'success' => true,
                'id_mesa' => $idMesaReal
            ], $resCuenta), JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error al cerrar la cuenta: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

// Al final del archivo, usa un switch($_GET['action']) para dirigir la petición al método correcto
if (isset($_GET['action'])) {
    $controller = new ServicioController($pdo);
    switch ($_GET['action']) {
        case 'guardar_orden':
            $controller->guardarOrden();
            break;
        case 'cerrar_cuenta':
            $controller->cerrarCuenta();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
