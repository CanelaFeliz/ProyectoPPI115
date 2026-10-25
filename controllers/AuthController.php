<?php
/**
 * ==============================================================================
 * NIXGARU – Controlador de Autenticación
 * controllers/AuthController.php
 * Gestiona el inicio de sesión, sesiones seguras y control de accesos
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
     * Procesa el inicio de sesión, autentica con el modelo Usuario,
     * almacena los datos en $_SESSION y devuelve la respuesta JSON con la ruta de redirección.
     */
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $nombreUsuario = isset($data['nombre_usuario'])
            ? trim((string)$data['nombre_usuario'])
            : (isset($data['usuario']) ? trim((string)$data['usuario']) : (isset($data['username']) ? trim((string)$data['username']) : ''));

        $password = isset($data['contrasena'])
            ? (string)$data['contrasena']
            : (isset($data['password']) ? (string)$data['password'] : '');

        if (empty($nombreUsuario) || empty($password)) {
            echo json_encode([
                'success' => false,
                'error'   => 'Por favor ingrese usuario y contraseña.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $usuarioModel = new Usuario($this->pdo);
            $usuario = $usuarioModel->autenticar($nombreUsuario, $password);

            if ($usuario) {
                session_regenerate_id(true);

                // Guardar los datos del usuario en $_SESSION usando las columnas exactas de Usuarios y Roles
                $_SESSION['id_usuario']      = (int)$usuario['id_usuario'];
                $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
                $_SESSION['nombre_usuario']  = $usuario['nombre_usuario'];
                $_SESSION['nombre_rol']      = $usuario['nombre_rol'];
                $_SESSION['id_rol']          = (int)$usuario['id_rol'];

                // Índices cortos de compatibilidad
                $_SESSION['nombre']  = $usuario['nombre_completo'];
                $_SESSION['usuario'] = $usuario['nombre_usuario'];
                $_SESSION['rol']     = $usuario['nombre_rol'];

                $rolNombre = strtolower(trim($usuario['nombre_rol']));
                if ($rolNombre === 'administrador') {
                    $redirect = 'views/admin/admin_dashboard.php';
                } else {
                    $redirect = 'views/servicio/mesas.php';
                }

                echo json_encode([
                    'success'  => true,
                    'redirect' => $redirect,
                    'usuario'  => [
                        'id_usuario'      => (int)$usuario['id_usuario'],
                        'nombre_completo' => $usuario['nombre_completo'],
                        'nombre_usuario'  => $usuario['nombre_usuario'],
                        'id_rol'          => (int)$usuario['id_rol'],
                        'nombre_rol'      => $usuario['nombre_rol']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'error'   => 'Credenciales incorrectas. Verifique su usuario y contraseña.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => 'Error al procesar la autenticación: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Cierra la sesión activa
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        echo json_encode([
            'success'  => true,
            'redirect' => '../../index.php'
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Router de acciones
if (isset($_GET['action'])) {
    $authController = new AuthController($pdo);
    switch ($_GET['action']) {
        case 'login':
            $authController->login();
            break;
        case 'logout':
            $authController->logout();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no permitida'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
