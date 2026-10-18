<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Inventario
 * models/Inventario.php
 * ==============================================================================
 */

class Inventario {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la lista de ingredientes, sus cantidades estimadas y unidades
     *
     * @return array
     */
    public function obtenerTodos(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                codigo_ingrediente,
                nombre,
                unidad,
                cantidad,
                stock_minimo,
                estado
            FROM ingredientes
            ORDER BY codigo_ingrediente ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los nombres de los ingredientes de la receta de un producto
     *
     * @param string $codigoProducto Código del producto (ej. 'P001')
     * @return array Lista de nombres de ingredientes
     */
    public function obtenerPorProducto(string $codigoProducto): array {
        $stmt = $this->pdo->prepare("
            SELECT i.nombre 
            FROM ingredientes i 
            INNER JOIN recetas r ON i.codigo_ingrediente = r.codigo_ingrediente 
            WHERE r.codigo_producto = ?
            ORDER BY i.nombre ASC
        ");
        $stmt->execute([$codigoProducto]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

