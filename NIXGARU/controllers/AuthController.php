<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de Autenticación
 * controllers/AuthController.php
 * ==============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Procesa el inicio de sesión, inicia la sesión PHP, autentica con el modelo Usuario,
     * almacena los datos en $_SESSION y devuelve la respuesta JSON con la ruta de redirección.
     */
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Captura el JSON enviado por el frontend (o fallback a $_POST)
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $username = isset($data['usuario']) ? trim($data['usuario']) : (isset($data['username']) ? trim($data['username']) : '');
        $password = isset($data['password']) ? $data['password'] : (isset($data['contrasenha']) ? $data['contrasenha'] : '');

        if (empty($username) || empty($password)) {
            echo json_encode([
                'success' => false,
                'error' => 'Por favor ingrese usuario y contraseña.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $usuarioModel = new Usuario($this->pdo);
            $usuario = $usuarioModel->autenticar($username, $password);

            if ($usuario) {
                // Guardar los datos en $_SESSION
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre'] = $usuario['nombre_completo'];
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['rol'] = $usuario['nombre_rol'];
                $_SESSION['id_rol'] = $usuario['id_rol'];

                // Determinar ruta según el rol y sus privilegios
                $rolNombre = strtolower(trim($usuario['nombre_rol']));
                if ($rolNombre === 'administrador') {
                    $redirect = 'views/admin/admin_dashboard.php';
                } else {
                    $redirect = 'views/servicio/mesas.php';
                }

                echo json_encode([
                    'success' => true,
                    'redirect' => $redirect,
                    'usuario' => [
                        'id_usuario' => $usuario['id_usuario'],
                        'nombre' => $usuario['nombre_completo'],
                        'rol' => $usuario['nombre_rol']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Credenciales incorrectas. Verifique su usuario y contraseña.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al procesar la autenticación: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

// Bloque condicional para manejar $_GET['action'] === 'login'
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    $authController = new AuthController($pdo);
    $authController->login();
}

