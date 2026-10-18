<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Producto
 * models/Producto.php
 * ==============================================================================
 */

class Producto {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Trae todos los productos registrados junto con su categoría
     * Consulta extraída de views/admin/admin_catalogo.php
     *
     * @return array
     */
    public function obtenerTodos(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.codigo_producto,
                p.nombre,
                p.descripcion,
                p.precio,
                p.fotografia,
                p.disponibilidad,
                p.id_categoria,
                c.nombre_categoria
            FROM productos p
            INNER JOIN categorias c ON p.id_categoria = c.id_categoria
            ORDER BY p.id_categoria ASC, p.codigo_producto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Trae los productos activos con disponibilidad = 1 junto con su categoría e ingredientes
     * Consulta extraída de views/cliente/menu_cliente.php y views/servicio/gestion_orden.php
     *
     * @return array
     */
    public function obtenerActivos(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.codigo_producto,
                p.nombre,
                p.fotografia,
                p.descripcion,
                p.precio,
                p.id_categoria,
                p.disponibilidad,
                c.nombre_categoria,
                COALESCE(GROUP_CONCAT(DISTINCT i.nombre ORDER BY i.nombre ASC SEPARATOR ', '), 'Ingredientes naturales de alta calidad') AS ingredientes
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN recetas r ON p.codigo_producto = r.codigo_producto
            LEFT JOIN ingredientes i ON r.codigo_ingrediente = i.codigo_ingrediente
            WHERE p.disponibilidad = 1
            GROUP BY p.codigo_producto, p.nombre, p.fotografia, p.descripcion, p.precio, p.id_categoria, p.disponibilidad, c.nombre_categoria
            ORDER BY p.id_categoria ASC, p.codigo_producto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

