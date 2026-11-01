<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Controlador: ClienteController (`controllers/ClienteController.php`)
 * Descripción: Gestiona la consulta del menú digital del cliente, ingredientes
 *              de recetas y el envío de pedidos web o desde mesa (RF01, RF02, RF03, RF15).
 * ==============================================================================
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Pedido.php';

class ClienteController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enruta la petición entrante según `action`.
     *
     * @param string $action
     * @param array<string, mixed> $input
     * @return void
     */
    public function manejarPeticion(string $action, array $input): void
    {
        try {
            switch ($action) {
                case 'crear_pedido_web':
                case 'guardar_orden':
                case 'confirmar_pedido':
                    $this->procesarPedido($action, $input);
                    break;

                case 'obtener_productos':
                case 'menu':
                    $this->obtenerProductos();
                    break;

                case 'obtener_ingredientes':
                    $this->obtenerIngredientes($input);
                    break;

                default:
                    $this->responderJson([
                        'success' => false,
                        'error'   => 'Acción no soportada en ClienteController.',
                    ], 400);
                    break;
            }
        } catch (InvalidArgumentException $e) {
            $this->responderJson(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $this->responderJson(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            error_log('[NIXGARU][ClienteController][PDOException] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error de base de datos al procesar la solicitud del cliente.'], 500);
        } catch (Throwable $e) {
            error_log('[NIXGARU][ClienteController][Throwable] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error interno al procesar la solicitud del cliente.'], 500);
        }
    }

    /**
     * Soporta tanto la creación de un pedido web (`Envío a domicilio` / `Retiro en el local`, RF15)
     * como el envío de orden desde mesa física cuando se indica `id_mesa`.
     *
     * @param string $action
     * @param array<string, mixed> $input
     * @return void
     */
    public function procesarPedido(string $action, array $input): void
    {
        $productos = isset($input['productos']) && is_array($input['productos']) ? $input['productos'] : [];
        if (empty($productos)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'El carrito no contiene productos válidos.',
            ], 400);
            return;
        }

        $pedidoModel = new Pedido($this->pdo);

        $tipoOrden  = mb_strtolower(trim((string)($input['tipo_orden'] ?? '')), 'UTF-8');
        $esTipoWeb  = in_array($tipoOrden, ['envío a domicilio', 'envio a domicilio', 'domicilio', 'retiro en el local', 'retiro'], true);
        $idMesa     = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['mesa'] ?? null));
        $sinMesa    = ($idMesa === null || $idMesa === '' || $idMesa === 0 || $idMesa === '0');
        $datosCli   = isset($input['cliente']) && is_array($input['cliente'])
            ? array_merge($input, $input['cliente'])
            : $input;
        $tieneDatosCliente = !empty($datosCli['nombre_cliente']) || !empty($datosCli['telefono_cliente']);

        if ($action === 'crear_pedido_web' || $esTipoWeb || ($tieneDatosCliente && $sinMesa)) {
            $resultado = $pedidoModel->crearPedidoWeb($datosCli, $productos);

            $this->responderJson([
                'success'   => true,
                'id_pedido' => $resultado['id_pedido'],
                'pedido'    => $resultado,
                'message'   => '¡Su pedido web ha sido recibido exitosamente!',
            ], 200);
            return;
        }

        if ($sinMesa) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar una mesa válida o completar los datos del pedido web.',
            ], 400);
            return;
        }

        $idUsuario    = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;
        $estadoPedido = trim((string)($input['estado_pedido'] ?? 'En preparación')) ?: 'En preparación';
        $estadoMesa   = trim((string)($input['estado_mesa'] ?? 'En proceso')) ?: 'En proceso';

        $resultado = $pedidoModel->guardarOrdenMesa($idMesa, $productos, $idUsuario, $estadoPedido, $estadoMesa, false);

        $this->responderJson([
            'success'   => true,
            'id_pedido' => $resultado['id_pedido'],
            'id_mesa'   => $resultado['id_mesa'],
            'orden'     => $resultado,
            'message'   => '¡Su pedido ha sido enviado con éxito a barra y cocina!',
        ], 200);
    }

    /**
     * `action=obtener_productos`: retorna el catálogo activo, las categorías y las mesas (RF01, RF03).
     *
     * @return void
     */
    public function obtenerProductos(): void
    {
        $productoModel  = new Producto($this->pdo);
        $categoriaModel = new Categoria($this->pdo);
        $mesaModel      = new Mesa($this->pdo);

        $productos  = $productoModel->obtenerActivos();
        $categorias = $categoriaModel->obtenerTodas();
        $mesas      = $mesaModel->obtenerTodas();

        $this->responderJson([
            'success'    => true,
            'total'      => count($productos),
            'productos'  => $productos,
            'categorias' => $categorias,
            'mesas'      => $mesas,
        ], 200);
    }

    /**
     * `action=obtener_ingredientes`: retorna los ingredientes del producto solicitado (RF02).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function obtenerIngredientes(array $input): void
    {
        $rawCodigo  = $input['codigo'] ?? ($input['id_producto'] ?? '');
        $idProducto = is_numeric($rawCodigo)
            ? (int)$rawCodigo
            : (int)preg_replace('/\D+/', '', (string)$rawCodigo);

        if ($idProducto <= 0) {
            $this->responderJson([
                'success'      => false,
                'error'        => 'Identificador de producto no válido.',
                'ingredientes' => [],
            ], 400);
            return;
        }

        $productoModel = new Producto($this->pdo);
        $ingredientes  = $productoModel->obtenerIngredientesPorProducto($idProducto);

        if (empty($ingredientes)) {
            $ingredientes = ['Ingredientes de la casa'];
        }

        $this->responderJson([
            'success'      => true,
            'id_producto'  => $idProducto,
            'codigo'       => (string)$idProducto,
            'ingredientes' => $ingredientes,
        ], 200);
    }

    /**
     * Emite respuesta JSON con código HTTP.
     *
     * @param array<string, mixed> $payload
     * @param int $statusCode
     * @return void
     */
    private function responderJson(array $payload, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}

if (!defined('NIXGARU_NO_AUTORUN')) {
    $rawBody   = file_get_contents('php://input');
    $jsonBody  = (is_string($rawBody) && trim($rawBody) !== '') ? json_decode($rawBody, true) : null;
    $inputData = is_array($jsonBody) ? array_merge($_GET, $_POST, $jsonBody) : array_merge($_GET, $_POST);
    $accionReq = trim((string)($_GET['action'] ?? ($_POST['action'] ?? ($inputData['action'] ?? ''))));

    $clienteController = new ClienteController($pdo);
    $clienteController->manejarPeticion($accionReq, $inputData);
}
