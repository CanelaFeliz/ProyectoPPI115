<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Controlador: AuthController (`controllers/AuthController.php`)
 * Descripción: Autenticación segura de empleados, control de sesiones y rutas
 *              de acceso diferenciadas por rol (RNF04, RNF06).
 * ==============================================================================
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enruta la acción solicitada hacia el método correspondiente.
     *
     * @param string $action
     * @param array<string, mixed> $input
     * @return void
     */
    public function manejarPeticion(string $action, array $input): void
    {
        try {
            switch ($action) {
                case 'login':
                    $this->login($input);
                    break;

                case 'logout':
                    $this->logout();
                    break;

                case 'session_status':
                    $this->sessionStatus();
                    break;

                default:
                    $this->responderJson([
                        'success' => false,
                        'error'   => 'Acción de autenticación no válida o no especificada.',
                    ], 400);
                    break;
            }
        } catch (InvalidArgumentException $e) {
            $this->responderJson(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            error_log('[NIXGARU][AuthController][PDOException] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error de base de datos al procesar la autenticación.'], 500);
        } catch (Throwable $e) {
            error_log('[NIXGARU][AuthController][Throwable] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error interno en el servidor de autenticación.'], 500);
        }
    }

    /**
     * Procesa `action=login`: valida credenciales, regenera el ID de sesión,
     * persiste datos en `$_SESSION` y determina la redirección según el rol (RNF04, RNF06).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function login(array $input): void
    {
        $usuario = trim((string)($input['usuario'] ?? ($input['nombre_usuario'] ?? ($input['username'] ?? ''))));
        $password = (string)($input['password'] ?? ($input['contrasena'] ?? ''));

        if ($usuario === '' || $password === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Por favor ingrese usuario y contraseña.',
            ], 400);
            return;
        }

        $usuarioModel = new Usuario($this->pdo);
        $datosUsuario = $usuarioModel->autenticar($usuario, $password);

        if (!$datosUsuario) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Credenciales incorrectas',
            ], 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $idUsuario      = (int)$datosUsuario['id_usuario'];
        $nombreUsuario  = (string)$datosUsuario['nombre_usuario'];
        $nombreCompleto = (string)$datosUsuario['nombre_completo'];
        $idRol          = (int)$datosUsuario['id_rol'];
        $nombreRol      = (string)$datosUsuario['nombre_rol'];

        // Claves principales y alias de sesión para vistas y layouts
        $_SESSION['id_usuario']      = $idUsuario;
        $_SESSION['usuario']         = $nombreUsuario;
        $_SESSION['nombre']          = $nombreCompleto;
        $_SESSION['id_rol']          = $idRol;
        $_SESSION['rol']             = $nombreRol;
        $_SESSION['nombre_usuario']  = $nombreUsuario;
        $_SESSION['nombre_completo'] = $nombreCompleto;
        $_SESSION['nombre_rol']      = $nombreRol;

        $rolNormalizado = mb_strtolower(trim($nombreRol), 'UTF-8');
        if ($idRol === 1 || in_array($rolNormalizado, ['administrador', 'administradora'], true)) {
            $redirect = 'views/admin/admin_dashboard.php';
        } else {
            // Personal de servicio, Cajero, Mesero, Barista, Cocina, Cocinero
            $redirect = 'views/servicio/mesas.php';
        }

        $this->responderJson([
            'success'  => true,
            'redirect' => $redirect,
            'usuario'  => [
                'id_usuario'      => $idUsuario,
                'usuario'         => $nombreUsuario,
                'nombre_usuario'  => $nombreUsuario,
                'nombre'          => $nombreCompleto,
                'nombre_completo' => $nombreCompleto,
                'id_rol'          => $idRol,
                'rol'             => $nombreRol,
                'nombre_rol'      => $nombreRol,
            ],
        ], 200);
    }

    /**
     * Procesa `action=logout`: limpia la sesión activa y responde con JSON (AJAX/fetch)
     * o redirige por encabezado HTTP cuando se accede por navegación directa.
     *
     * @return void
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        if ($this->esNavegacionDirecta()) {
            header('Location: /NIXGARU/index.php');
            exit;
        }

        $this->responderJson([
            'success'  => true,
            'redirect' => '/NIXGARU/index.php',
        ], 200);
    }

    /**
     * Procesa `action=session_status`: informa al frontend si existe una sesión activa.
     *
     * @return void
     */
    public function sessionStatus(): void
    {
        $autenticado = !empty($_SESSION['id_usuario']);

        $this->responderJson([
            'success'       => true,
            'authenticated' => $autenticado,
            'autenticado'   => $autenticado,
            'usuario'       => $autenticado ? [
                'id_usuario' => (int)$_SESSION['id_usuario'],
                'usuario'    => (string)($_SESSION['usuario'] ?? ''),
                'nombre'     => (string)($_SESSION['nombre'] ?? ''),
                'id_rol'     => (int)($_SESSION['id_rol'] ?? 0),
                'rol'        => (string)($_SESSION['rol'] ?? ''),
            ] : null,
        ], 200);
    }

    /**
     * Detecta si la petición proviene de navegación directa del navegador (no `fetch`/AJAX).
     *
     * @return bool
     */
    private function esNavegacionDirecta(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $metodo        = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $accept        = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $secFetchMode  = strtolower((string)($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));

        if ($metodo !== 'GET') {
            return false;
        }
        if (strcasecmp($requestedWith, 'XMLHttpRequest') === 0) {
            return false;
        }
        if (stripos($accept, 'application/json') !== false) {
            return false;
        }
        if ($secFetchMode !== '' && $secFetchMode !== 'navigate') {
            return false;
        }

        return stripos($accept, 'text/html') !== false;
    }

    /**
     * Emite una respuesta JSON estandarizada con código de estado HTTP.
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

    $authController = new AuthController($pdo);
    $authController->manejarPeticion($accionReq, $inputData);
}
