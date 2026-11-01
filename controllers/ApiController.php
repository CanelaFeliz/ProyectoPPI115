<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Controlador: ApiController (`controllers/ApiController.php`)
 * Descripción: Endpoints JSON ligeros de consulta de ingredientes, catálogo activo
 *              y disponibilidad de mesas para modales dinámicos (RF01, RF02, RF06, RF17).
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
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Categoria.php';

class ApiController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enruta la petición entrante según el parámetro `action`.
     *
     * @param string $action
     * @param array<string, mixed> $input
     * @return void
     */
    public function manejarPeticion(string $action, array $input): void
    {
        try {
            switch ($action) {
                case 'obtener_ingredientes':
                    $this->obtenerIngredientes($input);
                    break;

                case 'obtener_productos':
                    $this->obtenerProductos();
                    break;

                case 'obtener_mesas_disponibles':
                    $this->obtenerMesasDisponibles();
                    break;

                case 'obtener_mesas':
                    $this->obtenerMesas();
                    break;

                case 'obtener_categorias':
                    $this->obtenerCategorias();
                    break;

                default:
                    $this->responderJson([
                        'success' => false,
                        'error'   => 'Acción no soportada en ApiController.',
                    ], 400);
                    break;
            }
        } catch (InvalidArgumentException $e) {
            $this->responderJson(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            error_log('[NIXGARU][ApiController][PDOException] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error al consultar la base de datos.'], 500);
        } catch (Throwable $e) {
            error_log('[NIXGARU][ApiController][Throwable] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error interno en la API.'], 500);
        }
    }

    /**
     * `action=obtener_ingredientes`: consulta los ingredientes asociados a un producto
     * para `openProductModal` en `public/js/main.js` (RF02).
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
                'error'        => 'Debe proporcionar un código o ID de producto válido.',
                'id_producto'  => 0,
                'ingredientes' => [],
            ], 400);
            return;
        }

        $productoModel = new Producto($this->pdo);
        $ingredientes  = $productoModel->obtenerIngredientesPorProducto($idProducto);

        if (empty($ingredientes)) {
            $inventarioModel = new Inventario($this->pdo);
            $ingredientes    = $inventarioModel->obtenerPorProducto($idProducto);
        }

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
     * `action=obtener_productos`: retorna los productos activos junto con sus ingredientes y categorías (RF01).
     *
     * @return void
     */
    public function obtenerProductos(): void
    {
        $productoModel  = new Producto($this->pdo);
        $categoriaModel = new Categoria($this->pdo);

        $productos  = $productoModel->obtenerActivos();
        $categorias = $categoriaModel->obtenerTodas();

        $this->responderJson([
            'success'    => true,
            'total'      => count($productos),
            'productos'  => $productos,
            'categorias' => $categorias,
        ], 200);
    }

    /**
     * `action=obtener_mesas_disponibles`: retorna las mesas libres/disponibles para asignar
     * pedidos web de retiro en local (RF17).
     *
     * @return void
     */
    public function obtenerMesasDisponibles(): void
    {
        $mesaModel        = new Mesa($this->pdo);
        $mesasDisponibles = $mesaModel->obtenerDisponibles();

        $this->responderJson([
            'success' => true,
            'total'   => count($mesasDisponibles),
            'mesas'   => $mesasDisponibles,
        ], 200);
    }

    /**
     * `action=obtener_mesas`: retorna todas las mesas del salón (RF06).
     *
     * @return void
     */
    public function obtenerMesas(): void
    {
        $mesaModel = new Mesa($this->pdo);
        $mesas     = $mesaModel->obtenerTodas();

        $this->responderJson([
            'success' => true,
            'total'   => count($mesas),
            'mesas'   => $mesas,
        ], 200);
    }

    /**
     * `action=obtener_categorias`: retorna todas las categorías del menú.
     *
     * @return void
     */
    public function obtenerCategorias(): void
    {
        $categoriaModel = new Categoria($this->pdo);
        $categorias     = $categoriaModel->obtenerTodas();

        $this->responderJson([
            'success'    => true,
            'categorias' => $categorias,
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

    $apiController = new ApiController($pdo);
    $apiController->manejarPeticion($accionReq, $inputData);
}
