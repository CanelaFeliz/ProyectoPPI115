<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Producto
 * models/Producto.php
 * Gestiona el catálogo de productos y platillos con llaves primarias alfanuméricas
 * ==============================================================================
 */

class Producto {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Trae todos los productos registrados junto con su categoría
     * Consulta utilizada en la administración del catálogo
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
     * Consulta utilizada en la carta del cliente y en la pantalla de comandas de meseros
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

    /**
     * Obtiene los datos de un producto específico por su código alfanumérico
     *
     * @param string $codigoProducto Código alfanumérico (ej. 'P001', 'P018')
     * @return array|false
     */
    public function obtenerPorCodigo(string $codigoProducto) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.codigo_producto,
                p.nombre,
                p.fotografia,
                p.descripcion,
                p.precio,
                p.id_categoria,
                p.disponibilidad,
                c.nombre_categoria
            FROM productos p
            INNER JOIN categorias c ON p.id_categoria = c.id_categoria
            WHERE p.codigo_producto = :codigo
            LIMIT 1
        ");
        $stmt->bindValue(':codigo', trim($codigoProducto), PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtiene el precio actual de un producto por su código
     *
     * @param string $codigoProducto
     * @return float
     */
    public function obtenerPrecio(string $codigoProducto): float {
        $stmt = $this->pdo->prepare("
            SELECT precio 
            FROM productos 
            WHERE codigo_producto = :codigo 
            LIMIT 1
        ");
        $stmt->bindValue(':codigo', trim($codigoProducto), PDO::PARAM_STR);
        $stmt->execute();
        $precio = $stmt->fetchColumn();
        return $precio !== false ? (float)$precio : 0.00;
    }

    /**
     * Alterna la disponibilidad de venta de un producto (1 = Activo, 0 = Inactivo)
     *
     * @param string $codigoProducto Código alfanumérico
     * @param int $disponibilidad 1 o 0
     * @return bool
     */
    public function actualizarDisponibilidad(string $codigoProducto, int $disponibilidad): bool {
        $stmt = $this->pdo->prepare("
            UPDATE productos 
            SET disponibilidad = :disponibilidad 
            WHERE codigo_producto = :codigo
        ");
        $stmt->bindValue(':disponibilidad', $disponibilidad ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':codigo', trim($codigoProducto), PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Registra un nuevo producto en el catálogo
     *
     * @param string $codigo Código alfanumérico VARCHAR(10)
     * @param string $nombre
     * @param string|null $fotografia
     * @param string|null $descripcion
     * @param float $precio
     * @param int $idCategoria
     * @param int $disponibilidad
     * @return bool
     */
    public function crear(
        string $codigo, 
        string $nombre, 
        ?string $fotografia, 
        ?string $descripcion, 
        float $precio, 
        int $idCategoria, 
        int $disponibilidad = 1
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO productos (
                codigo_producto, 
                nombre, 
                fotografia, 
                descripcion, 
                precio, 
                id_categoria, 
                disponibilidad
            ) VALUES (
                :codigo, 
                :nombre, 
                :fotografia, 
                :descripcion, 
                :precio, 
                :id_categoria, 
                :disponibilidad
            )
        ");
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->bindValue(':fotografia', $fotografia ? trim($fotografia) : null, $fotografia ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':descripcion', $descripcion ? trim($descripcion) : null, $descripcion ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->bindValue(':disponibilidad', $disponibilidad ? 1 : 0, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Actualiza la información de un producto existente
     *
     * @param string $codigo
     * @param string $nombre
     * @param string|null $fotografia
     * @param string|null $descripcion
     * @param float $precio
     * @param int $idCategoria
     * @param int $disponibilidad
     * @return bool
     */
    public function actualizar(
        string $codigo, 
        string $nombre, 
        ?string $fotografia, 
        ?string $descripcion, 
        float $precio, 
        int $idCategoria, 
        int $disponibilidad = 1
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE productos 
            SET nombre = :nombre,
                fotografia = :fotografia,
                descripcion = :descripcion,
                precio = :precio,
                id_categoria = :id_categoria,
                disponibilidad = :disponibilidad
            WHERE codigo_producto = :codigo
        ");
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->bindValue(':fotografia', $fotografia ? trim($fotografia) : null, $fotografia ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':descripcion', $descripcion ? trim($descripcion) : null, $descripcion ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->bindValue(':disponibilidad', $disponibilidad ? 1 : 0, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un producto por su código alfanumérico
     *
     * @param string $codigo
     * @return bool
     */
    public function eliminar(string $codigo): bool {
        $stmt = $this->pdo->prepare("DELETE FROM productos WHERE codigo_producto = :codigo");
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        return $stmt->execute();
    }
}
