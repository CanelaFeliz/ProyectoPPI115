<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Archivo: config/database.php
 * Descripción: Configuración centralizada e instancia activa de conexión PDO
 *              hacia MySQL (`nixgaru_db`) con soporte utf8mb4.
 * ==============================================================================
 */

// Zona horaria operativa para El Salvador
date_default_timezone_set('America/El_Salvador');

// Parámetros de conexión para entorno local (XAMPP / WAMP / Laragon)
// Uso de defined() para evitar redeclaraciones ante inclusiones múltiples
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'nixgaru_db');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    $envPass = getenv('DB_PASS');
    define('DB_PASS', $envPass !== false ? $envPass : '');
    unset($envPass);
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
}

$dsn  = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$user = DB_USER;
$pass = DB_PASS;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        $pdo = new PDO($dsn, $user, $pass, $options);
    }
} catch (PDOException $e) {
    // Registro interno seguro para diagnóstico sin exponer credenciales al cliente
    error_log('[NIXGARU][PDOException] Error de conexión a nixgaru_db: ' . $e->getMessage());

    // Determinar si la petición entrante espera una respuesta en formato JSON
    $esperaJson = false;

    foreach (headers_list() as $headerItem) {
        if (stripos($headerItem, 'application/json') !== false) {
            $esperaJson = true;
            break;
        }
    }

    if (!$esperaJson) {
        $acceptHeader      = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentTypeHeader = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        $requestedWith     = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $scriptName        = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? ''));

        if (
            stripos($acceptHeader, 'application/json') !== false ||
            stripos($contentTypeHeader, 'application/json') !== false ||
            strcasecmp($requestedWith, 'XMLHttpRequest') === 0 ||
            stripos($scriptName, '/controllers/') !== false
        ) {
            $esperaJson = true;
        }
    }

    if ($esperaJson) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'error'   => 'Error de conexión a la base de datos',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Error crítico: No fue posible conectar con la base de datos del sistema NIXGARU.\n");
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    die(
        '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>NIXGARU – Servicio no disponible</title>'
        . '<style>body{font-family:system-ui,-apple-system,sans-serif;background:#fdf8f5;color:#2d1e18;'
        . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem;}'
        . '.box{background:#fff;border:1px solid #eadcd5;border-radius:12px;padding:2rem;max-width:460px;'
        . 'text-align:center;box-shadow:0 4px 16px rgba(0,0,0,.06);}'
        . 'h1{font-size:1.25rem;margin:0 0 .75rem;color:#9e2a2b;}'
        . 'p{font-size:.95rem;line-height:1.5;margin:0;color:#5c473e;}</style></head>'
        . '<body><div class="box"><h1>Error de conexión al servicio</h1>'
        . '<p>No se pudo establecer comunicación con la base de datos en este momento. '
        . 'Por favor, intente nuevamente más tarde o contacte al administrador del sistema.</p>'
        . '</div></body></html>'
    );
}
