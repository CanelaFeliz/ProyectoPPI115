<?php
$host = 'localhost';
$dbname = 'nixgaru_db';
$username = 'root'; 

// Si usas una contraseña para entrar a tu conexión en MySQL Workbench, escríbela aquí.
// Si usas XAMPP por defecto y no te pide contraseña, déjalo vacío ('').
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos MySQL: " . $e->getMessage());
}
?>
