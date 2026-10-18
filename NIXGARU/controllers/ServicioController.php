<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de Personal de Servicio
 * controllers/ServicioController.php
 * Procesa la recepción de comandas de mesas y el cierre financiero de cuentas
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Cuenta.php';

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $idMesa = isset($data['id_mesa']) ? $data['id_mesa'] : 0;
        $productos = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];
        $idMesero = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($data['id_mesero']) ? (int)$data['id_mesero'] : 3);
        $estadoMesa = isset($data['estado_mesa']) ? trim($data['estado_mesa']) : 'En proceso';
        $estadoPedido = isset($data['estado_pedido']) ? trim($data['estado_pedido']) : 'En preparación';

        if (empty($idMesa)) {
            echo json_encode(['success' => false, 'error' => 'Identificador de mesa no válido o vacío'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($productos)) {
            echo json_encode(['success' => false, 'error' => 'No hay productos en la orden enviada'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $mesaModel = new Mesa($this->pdo);
            $pedidoModel = new Pedido($this->pdo);

            // Asegura que la mesa exista en la BD y obtiene su id_mesa numérico real
            $idMesaReal = $mesaModel->asegurarMesa($idMesa, $estadoMesa);

            // Inserta o actualiza la orden y sus detalles_pedido con código de producto alfanumérico
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
     * Captura el JSON enviado por el frontend, registra el cobro en la tabla `cuentas`,
     * marca el pedido como 'Pagado' en `pedidos` y libera la mesa poniéndola en 'Disponible'.
     */
    public function cerrarCuenta(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $idMesa = isset($data['id_mesa']) ? $data['id_mesa'] : 0;
        $idPedidoDirecto = isset($data['id_pedido']) ? (int)$data['id_pedido'] : null;
        $montoRecibido = isset($data['monto_recibido']) ? (float)$data['monto_recibido'] : 0.00;
        $idCajero = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($data['id_cajero']) ? (int)$data['id_cajero'] : 2);

        if (empty($idMesa) && empty($idPedidoDirecto)) {
            echo json_encode(['success' => false, 'error' => 'No se especificó la mesa ni el pedido para procesar el cobro'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $mesaModel = new Mesa($this->pdo);
            $pedidoModel = new Pedido($this->pdo);

            // Asegura y resuelve el ID de la mesa
            $idMesaReal = !empty($idMesa) ? $mesaModel->asegurarMesa($idMesa, 'Disponible') : 0;

            // Registra el pago en la tabla normalizada `cuentas` y actualiza `pedidos` a 'Pagado'
            $resCuenta = $pedidoModel->cerrarCuenta($idMesaReal, $montoRecibido, $idCajero, $idPedidoDirecto);

            if (empty($resCuenta['id_pedido'])) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                echo json_encode([
                    'success' => false,
                    'error' => 'No se encontró un pedido activo pendiente de cobro para esta mesa'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Actualiza el estado de la mesa física a 'Disponible'
            if ($idMesaReal > 0) {
                $mesaModel->actualizarEstado($idMesaReal, 'Disponible');
            }

            $this->pdo->commit();

            echo json_encode(array_merge([
                'success' => true,
                'id_mesa' => $idMesaReal,
                'message' => 'Cobro procesado con éxito, registrado en cuentas y mesa liberada'
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

// Enrutador de acciones para peticiones GET o POST
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
