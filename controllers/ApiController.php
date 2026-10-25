<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de API
 * controllers/ApiController.php
 * Proporciona endpoints JSON para consumo asíncrono desde el frontend
 * ==============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Categoria.php';

class ApiController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Captura el parámetro id_producto (o codigo) por GET, consulta los ingredientes
     * de la receta en la base de datos a través del modelo Inventario y retorna JSON
     */
    public function obtenerIngredientes(): void {
        $rawId = $_GET['id_producto'] ?? ($_GET['codigo'] ?? '');
        $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D/', '', (string)$rawId);

        if ($idProducto <= 0) {
            echo json_encode([
                'success'      => false,
                'id_producto'  => 0,
                'ingredientes' => []
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $inventarioModel = new Inventario($this->pdo);
            $ingredientes = $inventarioModel->obtenerPorProducto($idProducto);

            if (empty($ingredientes)) {
                $ingredientes = ['Ingredientes frescos seleccionados artesanalmente'];
            }

            echo json_encode([
                'success'      => true,
                'id_producto'  => $idProducto,
                'codigo'       => (string)$idProducto,
                'ingredientes' => $ingredientes
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success'      => false,
                'error'        => $e->getMessage(),
                'ingredientes' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Retorna el catálogo de productos disponibles en formato JSON
     */
    public function obtenerProductos(): void {
        try {
            $productoModel = new Producto($this->pdo);
            $productos = $productoModel->obtenerActivos();

            echo json_encode([
                'success'   => true,
                'total'     => count($productos),
                'productos' => $productos
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success'   => false,
                'error'     => $e->getMessage(),
                'productos' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Retorna la lista de mesas físicas con su número y estado_mesa
     */
    public function obtenerMesas(): void {
        try {
            $mesaModel = new Mesa($this->pdo);
            $mesas = $mesaModel->obtenerTodas();

            echo json_encode([
                'success' => true,
                'total'   => count($mesas),
                'mesas'   => $mesas
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'mesas'   => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Retorna las categorías del menú
     */
    public function obtenerCategorias(): void {
        try {
            $categoriaModel = new Categoria($this->pdo);
            $categorias = $categoriaModel->obtenerTodas();

            echo json_encode([
                'success'    => true,
                'categorias' => $categorias
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success'    => false,
                'error'      => $e->getMessage(),
                'categorias' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

// Router de peticiones de la API
if (isset($_GET['action'])) {
    $apiController = new ApiController($pdo);
    switch ($_GET['action']) {
        case 'obtener_ingredientes':
            $apiController->obtenerIngredientes();
            break;
        case 'obtener_productos':
            $apiController->obtenerProductos();
            break;
        case 'obtener_mesas':
            $apiController->obtenerMesas();
            break;
        case 'obtener_categorias':
            $apiController->obtenerCategorias();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no soportada en la API'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
