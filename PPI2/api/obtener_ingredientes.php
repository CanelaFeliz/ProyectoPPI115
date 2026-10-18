<?php
/**
 * ==============================================================================
 * API: Obtener ingredientes reales de un producto desde la base de datos
 * Consulta cruzando las tablas: ingredientes, recetas y productos
 * ==============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode([
        'success' => false,
        'codigo' => '',
        'ingredientes' => []
    ]);
    exit;
}

try {
    // Consulta cruzando las tablas ingredientes y recetas para el producto dado
    $stmt = $pdo->prepare("
        SELECT i.nombre 
        FROM ingredientes i 
        INNER JOIN recetas r ON i.codigo_ingrediente = r.codigo_ingrediente 
        WHERE r.codigo_producto = ?
        ORDER BY i.nombre ASC
    ");
    $stmt->execute([$codigo]);
    $ingredientes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ingredientes)) {
        // En caso de no tener receta registrada, mensaje descriptivo
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
    ]);
}

