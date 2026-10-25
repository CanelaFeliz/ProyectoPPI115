<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Producto
 * models/Producto.php
 * Gestiona el catálogo de productos en la tabla `Productos` y sus relaciones
 * con `Categorias`, `Recetas` e `Inventarios`
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
                p.id_producto,
                p.nombre_producto,
                p.descripcion,
                p.fotografia,
                p.precio,
                p.estado_disponibilidad,
                p.id_categoria,
                c.nombre_categoria
            FROM Productos p
            INNER JOIN Categorias c ON p.id_categoria = c.id_categoria
            ORDER BY p.id_categoria ASC, p.id_producto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Trae los productos activos (estado_disponibilidad = 'Disponible') junto con su categoría
     * y los ingredientes asociados a través de `Recetas` e `Inventarios`
     *
     * @return array
     */
    public function obtenerActivos(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id_producto,
                p.nombre_producto,
                p.descripcion,
                p.fotografia,
                p.precio,
                p.estado_disponibilidad,
                p.id_categoria,
                c.nombre_categoria,
                COALESCE(
                    GROUP_CONCAT(DISTINCT i.nombre_ingrediente ORDER BY i.nombre_ingrediente ASC SEPARATOR ', '),
                    'Ingredientes naturales de alta calidad'
                ) AS ingredientes
            FROM Productos p
            LEFT JOIN Categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN Recetas r ON p.id_producto = r.id_producto
            LEFT JOIN Inventarios i ON r.id_ingrediente = i.id_ingrediente
            WHERE p.estado_disponibilidad = 'Disponible'
            GROUP BY 
                p.id_producto, 
                p.nombre_producto, 
                p.descripcion, 
                p.fotografia, 
                p.precio, 
                p.estado_disponibilidad, 
                p.id_categoria, 
                c.nombre_categoria
            ORDER BY p.id_categoria ASC, p.id_producto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos de un producto específico por su id_producto
     *
     * @param int|string $idProducto
     * @return array|false
     */
    public function obtenerPorId($idProducto) {
        $idInt = is_numeric($idProducto) ? (int)$idProducto : (int)preg_replace('/\D/', '', (string)$idProducto);

        $stmt = $this->pdo->prepare("
            SELECT 
                p.id_producto,
                p.nombre_producto,
                p.descripcion,
                p.fotografia,
                p.precio,
                p.estado_disponibilidad,
                p.id_categoria,
                c.nombre_categoria
            FROM Productos p
            INNER JOIN Categorias c ON p.id_categoria = c.id_categoria
            WHERE p.id_producto = :id_producto
            LIMIT 1
        ");
        $stmt->bindValue(':id_producto', $idInt, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Alias de obtenerPorId para compatibilidad
     *
     * @param int|string $idProducto
     * @return array|false
     */
    public function obtenerPorCodigo($idProducto) {
        return $this->obtenerPorId($idProducto);
    }

    /**
     * Obtiene el precio actual de un producto por su id_producto
     *
     * @param int|string $idProducto
     * @return float
     */
    public function obtenerPrecio($idProducto): float {
        $idInt = is_numeric($idProducto) ? (int)$idProducto : (int)preg_replace('/\D/', '', (string)$idProducto);

        $stmt = $this->pdo->prepare("
            SELECT precio 
            FROM Productos 
            WHERE id_producto = :id_producto 
            LIMIT 1
        ");
        $stmt->bindValue(':id_producto', $idInt, PDO::PARAM_INT);
        $stmt->execute();
        $precio = $stmt->fetchColumn();
        return $precio !== false ? (float)$precio : 0.00;
    }

    /**
     * Actualiza el estado de disponibilidad de un producto ('Disponible' / 'No disponible')
     *
     * @param int|string $idProducto
     * @param int|string|bool $estadoDisponibilidad
     * @return bool
     */
    public function actualizarDisponibilidad($idProducto, $estadoDisponibilidad): bool {
        $idInt = is_numeric($idProducto) ? (int)$idProducto : (int)preg_replace('/\D/', '', (string)$idProducto);

        if (is_bool($estadoDisponibilidad) || is_numeric($estadoDisponibilidad)) {
            $estadoStr = ((int)$estadoDisponibilidad === 1) ? 'Disponible' : 'No disponible';
        } else {
            $val = strtolower(trim((string)$estadoDisponibilidad));
            $estadoStr = in_array($val, ['disponible', 'activo', '1', 'true'], true) ? 'Disponible' : 'No disponible';
        }

        $stmt = $this->pdo->prepare("
            UPDATE Productos 
            SET estado_disponibilidad = :estado_disponibilidad 
            WHERE id_producto = :id_producto
        ");
        $stmt->bindValue(':estado_disponibilidad', $estadoStr, PDO::PARAM_STR);
        $stmt->bindValue(':id_producto', $idInt, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Registra un nuevo producto en la tabla Productos
     *
     * @param string $nombreProducto
     * @param string $descripcion
     * @param string $fotografia
     * @param float $precio
     * @param int $idCategoria
     * @param string $estadoDisponibilidad
     * @return int ID del producto creado
     */
    public function crear(
        string $nombreProducto, 
        string $descripcion, 
        string $fotografia, 
        float $precio, 
        int $idCategoria, 
        string $estadoDisponibilidad = 'Disponible'
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO Productos (
                nombre_producto, 
                descripcion, 
                fotografia, 
                precio, 
                estado_disponibilidad, 
                id_categoria
            ) VALUES (
                :nombre_producto, 
                :descripcion, 
                :fotografia, 
                :precio, 
                :estado_disponibilidad, 
                :id_categoria
            )
        ");
        $stmt->bindValue(':nombre_producto', trim($nombreProducto), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim($descripcion), PDO::PARAM_STR);
        $stmt->bindValue(':fotografia', trim($fotografia), PDO::PARAM_STR);
        $stmt->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':estado_disponibilidad', trim($estadoDisponibilidad), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza la información de un producto existente en la tabla Productos
     *
     * @param int $idProducto
     * @param string $nombreProducto
     * @param string $descripcion
     * @param string $fotografia
     * @param float $precio
     * @param int $idCategoria
     * @param string $estadoDisponibilidad
     * @return bool
     */
    public function actualizar(
        int $idProducto, 
        string $nombreProducto, 
        string $descripcion, 
        string $fotografia, 
        float $precio, 
        int $idCategoria, 
        string $estadoDisponibilidad = 'Disponible'
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Productos 
            SET nombre_producto = :nombre_producto,
                descripcion = :descripcion,
                fotografia = :fotografia,
                precio = :precio,
                estado_disponibilidad = :estado_disponibilidad,
                id_categoria = :id_categoria
            WHERE id_producto = :id_producto
        ");
        $stmt->bindValue(':nombre_producto', trim($nombreProducto), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', trim($descripcion), PDO::PARAM_STR);
        $stmt->bindValue(':fotografia', trim($fotografia), PDO::PARAM_STR);
        $stmt->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':estado_disponibilidad', trim($estadoDisponibilidad), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un producto por su id_producto
     *
     * @param int|string $idProducto
     * @return bool
     */
    public function eliminar($idProducto): bool {
        $idInt = is_numeric($idProducto) ? (int)$idProducto : (int)preg_replace('/\D/', '', (string)$idProducto);

        $stmt = $this->pdo->prepare("DELETE FROM Productos WHERE id_producto = :id_producto");
        $stmt->bindValue(':id_producto', $idInt, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
