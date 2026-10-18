<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de API
 * controllers/ApiController.php
 * ==============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Inventario.php';

class ApiController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Captura el parámetro codigo por GET, consulta los ingredientes de la receta
     * en la base de datos a través del modelo Inventario y retorna JSON
     */
    public function obtenerIngredientes(): void {
        $codigo = $_GET['codigo'] ?? '';

        if (empty($codigo)) {
            echo json_encode([
                'success' => false,
                'codigo' => '',
                'ingredientes' => []
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $inventarioModel = new Inventario($this->pdo);
            $ingredientes = $inventarioModel->obtenerPorProducto($codigo);

            if (empty($ingredientes)) {
                $ingredientes = ['Ingredientes frescos seleccionados artesanalmente'];
            }

            echo json_encode([
                'success' => true,
                'codigo' => $codigo,
                'ingredientes' => $ingredientes
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'ingredientes' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

// Instanciar el controlador y ejecutar el método si $_GET['action'] == 'obtener_ingredientes'
if (isset($_GET['action']) && $_GET['action'] === 'obtener_ingredientes') {
    $apiController = new ApiController($pdo);
    $apiController->obtenerIngredientes();
}
