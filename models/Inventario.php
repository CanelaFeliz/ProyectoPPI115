<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Modelo: Inventario (`models/Inventario.php`)
 * Tablas: `inventarios`, `recetas`, `detalle_pedidos`
 * ==============================================================================
 */

class Inventario
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna todos los ingredientes e insumos registrados en `inventarios`
     * ordenados por `id_ingrediente ASC`.
     *
     * @return array<int, array{id_ingrediente: int, nombre_ingrediente: string, cantidad_estimada: float}>
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_ingrediente, nombre_ingrediente, cantidad_estimada
            FROM inventarios
            ORDER BY id_ingrediente ASC
        ");
        $stmt->execute();

        return array_map([$this, 'formatearFila'], $stmt->fetchAll());
    }

    /**
     * Obtiene un ingrediente por su identificador primario (`id_ingrediente`).
     *
     * @param int $idIngrediente
     * @return array{id_ingrediente: int, nombre_ingrediente: string, cantidad_estimada: float}|null
     */
    public function obtenerPorId(int $idIngrediente): ?array
    {
        if ($idIngrediente <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT id_ingrediente, nombre_ingrediente, cantidad_estimada
            FROM inventarios
            WHERE id_ingrediente = :id_ingrediente
            LIMIT 1
        ");
        $stmt->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch();
        return $fila ? $this->formatearFila($fila) : null;
    }

    /**
     * Valida que `$cantidadReal >= 0` y actualiza `cantidad_estimada` en `inventarios` (RF14).
     *
     * @param int $idIngrediente
     * @param float $cantidadReal
     * @return bool
     */
    public function ajustarCantidad(int $idIngrediente, float $cantidadReal): bool
    {
        if ($idIngrediente <= 0) {
            throw new InvalidArgumentException('El identificador de ingrediente no es válido.');
        }

        if ($cantidadReal < 0) {
            throw new InvalidArgumentException('La cantidad en inventario no puede ser negativa.');
        }

        $cantidadFormateada = number_format(round($cantidadReal, 2), 2, '.', '');

        $stmt = $this->pdo->prepare("
            UPDATE inventarios
            SET cantidad_estimada = :cantidad_estimada
            WHERE id_ingrediente = :id_ingrediente
        ");
        $stmt->bindValue(':cantidad_estimada', $cantidadFormateada, PDO::PARAM_STR);
        $stmt->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Alias de compatibilidad hacia `ajustarCantidad` usado por controladores existentes.
     *
     * @param int|string $idIngrediente
     * @param float $nuevaCantidad
     * @return bool
     */
    public function actualizarStock($idIngrediente, float $nuevaCantidad): bool
    {
        $idInt = is_numeric($idIngrediente)
            ? (int)$idIngrediente
            : (int)preg_replace('/\D+/', '', (string)$idIngrediente);

        return $this->ajustarCantidad($idInt, $nuevaCantidad);
    }

    /**
     * Descuenta automáticamente del inventario los insumos utilizados en un pedido
     * según su formulación en `recetas` y cantidades en `detalle_pedidos` (RF14).
     * Agrupa por ingrediente para acumular consumos cuando varios platillos del mismo
     * pedido comparten un insumo.
     *
     * @param int $idPedido
     * @return bool
     */
    public function descontarPorPedido(int $idPedido): bool
    {
        if ($idPedido <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE inventarios i
            INNER JOIN (
                SELECT
                    r.id_ingrediente,
                    SUM(r.cantidad_necesaria * dp.cantidad) AS total_consumido
                FROM recetas r
                INNER JOIN detalle_pedidos dp ON dp.id_producto = r.id_producto
                WHERE dp.id_pedido = :id_pedido
                GROUP BY r.id_ingrediente
            ) consumo ON i.id_ingrediente = consumo.id_ingrediente
            SET i.cantidad_estimada = GREATEST(0, ROUND(i.cantidad_estimada - consumo.total_consumido, 2))
        ");
        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Retorna la lista de nombres de ingredientes de un producto (`ApiController`).
     *
     * @param int|string $idProducto
     * @return array<int, string>
     */
    public function obtenerPorProducto($idProducto): array
    {
        $idInt = is_numeric($idProducto)
            ? (int)$idProducto
            : (int)preg_replace('/\D+/', '', (string)$idProducto);

        if ($idInt <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT i.nombre_ingrediente
            FROM inventarios i
            INNER JOIN recetas r ON i.id_ingrediente = r.id_ingrediente
            WHERE r.id_producto = :id_producto
            ORDER BY i.nombre_ingrediente ASC
        ");
        $stmt->bindValue(':id_producto', $idInt, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Crea un nuevo insumo en `inventarios`.
     *
     * @param string $nombreIngrediente
     * @param float $cantidadEstimada
     * @return int
     */
    public function crear(string $nombreIngrediente, float $cantidadEstimada = 0.00): int
    {
        $nombreLimpio = trim($nombreIngrediente);
        if ($nombreLimpio === '') {
            throw new InvalidArgumentException('El nombre del ingrediente es obligatorio.');
        }
        if ($cantidadEstimada < 0) {
            throw new InvalidArgumentException('La cantidad inicial no puede ser negativa.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO inventarios (nombre_ingrediente, cantidad_estimada)
            VALUES (:nombre_ingrediente, :cantidad_estimada)
        ");
        $stmt->bindValue(':nombre_ingrediente', $nombreLimpio, PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_estimada', number_format(round($cantidadEstimada, 2), 2, '.', ''), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza nombre y cantidad de un ingrediente existente.
     *
     * @param int $idIngrediente
     * @param string $nombreIngrediente
     * @param float $cantidadEstimada
     * @return bool
     */
    public function actualizar(int $idIngrediente, string $nombreIngrediente, float $cantidadEstimada): bool
    {
        $nombreLimpio = trim($nombreIngrediente);
        if ($idIngrediente <= 0 || $nombreLimpio === '' || $cantidadEstimada < 0) {
            throw new InvalidArgumentException('Datos de ingrediente inválidos para actualizar.');
        }

        $stmt = $this->pdo->prepare("
            UPDATE inventarios
            SET nombre_ingrediente = :nombre_ingrediente,
                cantidad_estimada  = :cantidad_estimada
            WHERE id_ingrediente = :id_ingrediente
        ");
        $stmt->bindValue(':nombre_ingrediente', $nombreLimpio, PDO::PARAM_STR);
        $stmt->bindValue(':cantidad_estimada', number_format(round($cantidadEstimada, 2), 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un ingrediente de `inventarios` limpiando primero sus vínculos en `recetas`.
     *
     * @param int $idIngrediente
     * @return bool
     */
    public function eliminar(int $idIngrediente): bool
    {
        if ($idIngrediente <= 0) {
            return false;
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmtRec = $this->pdo->prepare("DELETE FROM recetas WHERE id_ingrediente = :id_ingrediente");
            $stmtRec->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);
            $stmtRec->execute();

            $stmt = $this->pdo->prepare("DELETE FROM inventarios WHERE id_ingrediente = :id_ingrediente");
            $stmt->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);
            $resultado = $stmt->execute();

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return $resultado;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Normaliza los tipos numéricos de una fila de `inventarios`.
     *
     * @param array<string, mixed> $fila
     * @return array{id_ingrediente: int, nombre_ingrediente: string, cantidad_estimada: float}
     */
    private function formatearFila(array $fila): array
    {
        return [
            'id_ingrediente'     => (int)$fila['id_ingrediente'],
            'nombre_ingrediente' => (string)$fila['nombre_ingrediente'],
            'cantidad_estimada'  => round((float)$fila['cantidad_estimada'], 2),
        ];
    }
}
