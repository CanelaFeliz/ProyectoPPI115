<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de Vista del Cliente
 * controllers/ClienteController.php
 * Gestiona la interacción del menú digital del cliente y el auto-pedido
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Pedido.php';

class ClienteController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retorna la carta digital con productos activos y categorías
     */
    public function obtenerMenu(): void {
        try {
            $productoModel = new Producto($this->pdo);
            $categoriaModel = new Categoria($this->pdo);

            $productos = $productoModel->obtenerActivos();
            $categorias = $categoriaModel->obtenerTodas();

            echo json_encode([
                'success' => true,
                'categorias' => $categorias,
                'productos' => $productos
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Procesa la confirmación de pedido realizada por el comensal desde su mesa
     */
    public function confirmarPedido(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $idMesa = isset($data['id_mesa']) ? $data['id_mesa'] : (isset($data['mesa']) ? $data['mesa'] : 0);
        $productos = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];

        if (empty($idMesa)) {
            echo json_encode(['success' => false, 'error' => 'Debe seleccionar una mesa válida'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($productos)) {
            echo json_encode(['success' => false, 'error' => 'El carrito está vacío'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $mesaModel = new Mesa($this->pdo);
            $pedidoModel = new Pedido($this->pdo);

            // Asegura la mesa física
            $idMesaReal = $mesaModel->asegurarMesa($idMesa, 'En proceso');

            // Guarda el pedido asignando mesero predeterminado (ej. 3 - Ana Ruiz)
            $idPedido = $pedidoModel->guardarOrden($idMesaReal, $productos, 3, 'En preparación');

            // Actualiza la mesa a 'En proceso'
            $mesaModel->actualizarEstado($idMesaReal, 'En proceso');

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'id_pedido' => $idPedido,
                'id_mesa' => $idMesaReal,
                'message' => '¡Su pedido ha sido enviado con éxito a la barra y cocina!'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}

// Router de acciones
if (isset($_GET['action'])) {
    $clienteController = new ClienteController($pdo);
    switch ($_GET['action']) {
        case 'menu':
            $clienteController->obtenerMenu();
            break;
        case 'confirmar_pedido':
            $clienteController->confirmarPedido();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no soportada'], JSON_UNESCAPED_UNICODE);
            break;
    }
}

