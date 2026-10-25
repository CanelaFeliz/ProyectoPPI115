<?php
/**
 * ==============================================================================
 * NIXGARU – Configuración de Conexión PDO a Base de Datos MySQL
 * config/database.php
 * ==============================================================================
 */

$host = 'localhost';
$dbname = 'nixgaru_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos MySQL (nixgaru_db): " . $e->getMessage());
}

