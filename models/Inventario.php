<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Inventario
 * models/Inventario.php
 * Gestiona el inventario de insumos en la tabla `Inventarios` y su relación
 * técnica con `Recetas` y `Productos`
 * ==============================================================================
 */

class Inventario {
    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la lista completa de ingredientes y cantidades estimadas desde `Inventarios`
     *
     * @return array
     */
    public function obtenerTodos(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                id_ingrediente,
                nombre_ingrediente,
                cantidad_estimada
            FROM Inventarios
            ORDER BY id_ingrediente ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene la información de un ingrediente específico por su id_ingrediente
     *
     * @param int|string $idIngrediente
     * @return array|false
     */
    public function obtenerPorId($idIngrediente) {
        $idInt = is_numeric($idIngrediente) ? (int)$idIngrediente : (int)preg_replace('/\D/', '', (string)$idIngrediente);

        $stmt = $this->pdo->prepare("
            SELECT 
                id_ingrediente,
                nombre_ingrediente,
                cantidad_estimada
            FROM Inventarios
            WHERE id_ingrediente = :id_ingrediente
            LIMIT 1
        ");
        $stmt->bindValue(':id_ingrediente', $idInt, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Alias de obtenerPorId para compatibilidad
     *
     * @param int|string $idIngrediente
     * @return array|false
     */
    public function obtenerPorCodigo($idIngrediente) {
        return $this->obtenerPorId($idIngrediente);
    }

    /**
     * Obtiene los nombres de los ingredientes de la receta de un producto
     * uniendo `Inventarios` y `Recetas` mediante id_ingrediente e id_producto
     *
     * @param int|string $idProducto
     * @return array Lista de nombres de ingredientes
     */
    public function obtenerPorProducto($idProducto): array {
        $idInt = is_numeric($idProducto) ? (int)$idProducto : (int)preg_replace('/\D/', '', (string)$idProducto);

        $stmt = $this->pdo->prepare("
            SELECT i.nombre_ingrediente 
            FROM Inventarios i 
            INNER JOIN Recetas r ON i.id_ingrediente = r.id_ingrediente 
            WHERE r.id_producto = :id_producto
            ORDER BY i.nombre_ingrediente ASC
        ");
        $stmt->bindValue(':id_producto', $idInt, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtiene el detalle completo de la receta de un producto (insumos y cantidad_necesaria)
     *
     * @param int $idProducto
     * @return array
     */
    public function obtenerRecetaDetalladaPorProducto(int $idProducto): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.id_receta,
                r.cantidad_necesaria,
                r.id_producto,
                r.id_ingrediente,
                i.nombre_ingrediente,
                i.cantidad_estimada
            FROM Recetas r
            INNER JOIN Inventarios i ON r.id_ingrediente = r.id_ingrediente
            WHERE r.id_producto = :id_producto
            ORDER BY i.id_ingrediente ASC
        ");
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Actualiza la cantidad estimada de un ingrediente en la tabla `Inventarios`
     *
     * @param int|string $idIngrediente
     * @param float $nuevaCantidad
     * @return bool
     */
    public function actualizarStock($idIngrediente, float $nuevaCantidad): bool {
        $idInt = is_numeric($idIngrediente) ? (int)$idIngrediente : (int)preg_replace('/\D/', '', (string)$idIngrediente);

        $stmt = $this->pdo->prepare("
            UPDATE Inventarios 
            SET cantidad_estimada = :cantidad_estimada
            WHERE id_ingrediente = :id_ingrediente
        ");
        $stmt->bindValue(':cantidad_estimada', number_format($nuevaCantidad, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':id_ingrediente', $idInt, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Registra un nuevo ingrediente en la tabla `Inventarios`
     *
     * @param string $nombreIngrediente
     * @param float $cantidadEstimada
     * @return int ID del ingrediente insertado
     */
    public function crear(string $nombreIngrediente, float $cantidadEstimada = 0.00): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO Inventarios (
                nombre_ingrediente, 
                cantidad_estimada
            ) VALUES (
                :nombre_ingrediente, 
                :cantidad_estimada
            )
        ");
        $stmt->bindValue(':nombre_ingrediente', trim($nombreIngrediente), PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_estimada', number_format($cantidadEstimada, 2, '.', ''), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza la información de un ingrediente existente en `Inventarios`
     *
     * @param int $idIngrediente
     * @param string $nombreIngrediente
     * @param float $cantidadEstimada
     * @return bool
     */
    public function actualizar(int $idIngrediente, string $nombreIngrediente, float $cantidadEstimada): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Inventarios 
            SET nombre_ingrediente = :nombre_ingrediente,
                cantidad_estimada = :cantidad_estimada
            WHERE id_ingrediente = :id_ingrediente
        ");
        $stmt->bindValue(':nombre_ingrediente', trim($nombreIngrediente), PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_estimada', number_format($cantidadEstimada, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un ingrediente por su id_ingrediente
     *
     * @param int|string $idIngrediente
     * @return bool
     */
    public function eliminar($idIngrediente): bool {
        $idInt = is_numeric($idIngrediente) ? (int)$idIngrediente : (int)preg_replace('/\D/', '', (string)$idIngrediente);

        $stmt = $this->pdo->prepare("DELETE FROM Inventarios WHERE id_ingrediente = :id_ingrediente");
        $stmt->bindValue(':id_ingrediente', $idInt, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
