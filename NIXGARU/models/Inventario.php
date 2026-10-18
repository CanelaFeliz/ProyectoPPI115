<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Inventario / Ingrediente
 * models/Inventario.php
 * Gestiona el inventario de insumos en la tabla `ingredientes`
 * ==============================================================================
 */

class Inventario {
    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la lista completa de ingredientes, stock actual y estados
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
     * Obtiene la información de un ingrediente específico por su código alfanumérico
     *
     * @param string $codigoIngrediente Código alfanumérico (ej. 'I01', 'I03')
     * @return array|false
     */
    public function obtenerPorCodigo(string $codigoIngrediente) {
        $stmt = $this->pdo->prepare("
            SELECT 
                codigo_ingrediente,
                nombre,
                unidad,
                cantidad,
                stock_minimo,
                estado
            FROM ingredientes
            WHERE codigo_ingrediente = :codigo
            LIMIT 1
        ");
        $stmt->bindValue(':codigo', trim($codigoIngrediente), PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtiene los nombres de los ingredientes de la receta de un producto
     * mediante la tabla intermedia `recetas`
     *
     * @param string $codigoProducto Código alfanumérico del producto (ej. 'P001')
     * @return array Lista de nombres de ingredientes
     */
    public function obtenerPorProducto(string $codigoProducto): array {
        $stmt = $this->pdo->prepare("
            SELECT i.nombre 
            FROM ingredientes i 
            INNER JOIN recetas r ON i.codigo_ingrediente = r.codigo_ingrediente 
            WHERE r.codigo_producto = :codigo_producto
            ORDER BY i.nombre ASC
        ");
        $stmt->bindValue(':codigo_producto', trim($codigoProducto), PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Actualiza la cantidad de stock de un ingrediente y recalcula automáticamente su estado
     *
     * @param string $codigoIngrediente Código alfanumérico
     * @param float $nuevaCantidad Nueva cantidad de existencia
     * @return bool
     */
    public function actualizarStock(string $codigoIngrediente, float $nuevaCantidad): bool {
        $stmt = $this->pdo->prepare("
            UPDATE ingredientes 
            SET cantidad = :cantidad,
                estado = CASE 
                    WHEN :cantidad_eval <= 0 THEN 'Agotado'
                    WHEN :cantidad_eval <= stock_minimo THEN 'Bajo'
                    ELSE 'Suficiente'
                END
            WHERE codigo_ingrediente = :codigo
        ");
        $stmt->bindValue(':cantidad', number_format($nuevaCantidad, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_eval', $nuevaCantidad);
        $stmt->bindValue(':codigo', trim($codigoIngrediente), PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Registra un nuevo ingrediente en el catálogo de inventario
     *
     * @param string $codigo Alfanumérico VARCHAR(10)
     * @param string $nombre Nombre del ingrediente
     * @param string $unidad Unidad de medida ('lb', 'L', 'unidad', etc.)
     * @param float $cantidad Cantidad inicial
     * @param float $stockMinimo Nivel mínimo antes de generar alerta
     * @param string $estado 'Suficiente', 'Bajo' o 'Agotado'
     * @return bool
     */
    public function crear(
        string $codigo, 
        string $nombre, 
        string $unidad, 
        float $cantidad, 
        float $stockMinimo, 
        string $estado = 'Suficiente'
    ): bool {
        // Determinación de estado si no se especifica
        if ($cantidad <= 0) {
            $estado = 'Agotado';
        } elseif ($cantidad <= $stockMinimo) {
            $estado = 'Bajo';
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO ingredientes (
                codigo_ingrediente, 
                nombre, 
                unidad, 
                cantidad, 
                stock_minimo, 
                estado
            ) VALUES (
                :codigo, 
                :nombre, 
                :unidad, 
                :cantidad, 
                :stock_minimo, 
                :estado
            )
        ");
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->bindValue(':unidad', trim($unidad), PDO::PARAM_STR);
        $stmt->bindValue(':cantidad', number_format($cantidad, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':stock_minimo', number_format($stockMinimo, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':estado', trim($estado), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Actualiza la información completa de un ingrediente
     *
     * @param string $codigo
     * @param string $nombre
     * @param string $unidad
     * @param float $cantidad
     * @param float $stockMinimo
     * @param string|null $estado
     * @return bool
     */
    public function actualizar(
        string $codigo, 
        string $nombre, 
        string $unidad, 
        float $cantidad, 
        float $stockMinimo, 
        ?string $estado = null
    ): bool {
        if ($estado === null || $estado === '') {
            if ($cantidad <= 0) {
                $estado = 'Agotado';
            } elseif ($cantidad <= $stockMinimo) {
                $estado = 'Bajo';
            } else {
                $estado = 'Suficiente';
            }
        }

        $stmt = $this->pdo->prepare("
            UPDATE ingredientes 
            SET nombre = :nombre,
                unidad = :unidad,
                cantidad = :cantidad,
                stock_minimo = :stock_minimo,
                estado = :estado
            WHERE codigo_ingrediente = :codigo
        ");
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->bindValue(':unidad', trim($unidad), PDO::PARAM_STR);
        $stmt->bindValue(':cantidad', number_format($cantidad, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':stock_minimo', number_format($stockMinimo, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':estado', trim($estado), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Elimina un ingrediente por su código
     *
     * @param string $codigo
     * @return bool
     */
    public function eliminar(string $codigo): bool {
        $stmt = $this->pdo->prepare("DELETE FROM ingredientes WHERE codigo_ingrediente = :codigo");
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        return $stmt->execute();
    }
}
