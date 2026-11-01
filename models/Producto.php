<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Modelo: Producto (`models/Producto.php`)
 * Tablas: `productos`, `categorias`, `recetas`, `inventarios`, `detalle_pedidos`
 * ==============================================================================
 */

class Producto
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna los productos activos con categoría e ingredientes concatenados
     * para el menú del cliente y la toma de órdenes (RF01, RF02, RF03).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerActivos(): array
    {
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
                    'Ingredientes de la casa'
                ) AS ingredientes
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN recetas r ON p.id_producto = r.id_producto
            LEFT JOIN inventarios i ON r.id_ingrediente = i.id_ingrediente
            WHERE LOWER(TRIM(p.estado_disponibilidad)) IN ('disponible', 'activo', '1')
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

        return array_map([$this, 'formatearFila'], $stmt->fetchAll());
    }

    /**
     * Retorna todos los productos del catálogo (activos e inactivos) incluyendo
     * el nombre de su categoría e ingredientes para `admin_catalogo.php` (RF04).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
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
                    'Ingredientes de la casa'
                ) AS ingredientes
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN recetas r ON p.id_producto = r.id_producto
            LEFT JOIN inventarios i ON r.id_ingrediente = i.id_ingrediente
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

        return array_map([$this, 'formatearFila'], $stmt->fetchAll());
    }

    /**
     * Obtiene un producto específico por `id_producto` incluyendo sus ingredientes.
     *
     * @param int $idProducto
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $idProducto): ?array
    {
        if ($idProducto <= 0) {
            return null;
        }

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
                    'Ingredientes de la casa'
                ) AS ingredientes
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
            LEFT JOIN recetas r ON p.id_producto = r.id_producto
            LEFT JOIN inventarios i ON r.id_ingrediente = i.id_ingrediente
            WHERE p.id_producto = :id_producto
            GROUP BY
                p.id_producto,
                p.nombre_producto,
                p.descripcion,
                p.fotografia,
                p.precio,
                p.estado_disponibilidad,
                p.id_categoria,
                c.nombre_categoria
            LIMIT 1
        ");
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch();
        return $fila ? $this->formatearFila($fila) : null;
    }

    /**
     * Retorna un arreglo simple de strings con los nombres de los ingredientes
     * de un producto para `ApiController.php?action=obtener_ingredientes` (RF02).
     *
     * @param int $idProducto
     * @return array<int, string>
     */
    public function obtenerIngredientesPorProducto(int $idProducto): array
    {
        if ($idProducto <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT i.nombre_ingrediente
            FROM recetas r
            INNER JOIN inventarios i ON r.id_ingrediente = i.id_ingrediente
            WHERE r.id_producto = :id_producto
            ORDER BY i.nombre_ingrediente ASC
        ");
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Inserta un nuevo producto en `productos` y opcionalmente sus líneas de receta (RF04).
     *
     * @param array<string, mixed> $datos
     * @return int ID del producto creado (`id_producto`)
     */
    public function crear(array $datos): int
    {
        $nombre = trim((string)($datos['nombre_producto'] ?? ($datos['nombre'] ?? '')));
        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }

        $descripcion = isset($datos['descripcion']) ? trim((string)$datos['descripcion']) : '';
        $fotografia  = isset($datos['fotografia'])
            ? trim((string)$datos['fotografia'])
            : (isset($datos['imagen']) ? trim((string)$datos['imagen']) : '');
        $precio      = isset($datos['precio']) ? round((float)$datos['precio'], 2) : 0.00;
        if ($precio < 0) {
            throw new InvalidArgumentException('El precio del producto no puede ser negativo.');
        }

        $idCategoria = isset($datos['id_categoria']) && (int)$datos['id_categoria'] > 0
            ? (int)$datos['id_categoria']
            : 1;
        $estado = $this->normalizarDisponibilidad((string)($datos['estado_disponibilidad'] ?? 'Disponible'));

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO productos (
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
            $stmt->bindValue(':nombre_producto', $nombre, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindValue(':fotografia', $fotografia, PDO::PARAM_STR);
            $stmt->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindValue(':estado_disponibilidad', $estado, PDO::PARAM_STR);
            $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->execute();

            $idProducto = (int)$this->pdo->lastInsertId();

            $recetas = $datos['recetas'] ?? ($datos['ingredientes_receta'] ?? null);
            if (is_array($recetas) && !empty($recetas)) {
                $this->guardarRecetasInterno($idProducto, $recetas);
            }

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return $idProducto;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Actualiza los datos de un producto existente y opcionalmente su receta (RF04).
     *
     * @param int $idProducto
     * @param array<string, mixed> $datos
     * @return bool
     */
    public function actualizar(int $idProducto, array $datos): bool
    {
        $actual = $this->obtenerPorId($idProducto);
        if (!$actual) {
            return false;
        }

        $nombre = array_key_exists('nombre_producto', $datos) || array_key_exists('nombre', $datos)
            ? trim((string)($datos['nombre_producto'] ?? $datos['nombre']))
            : (string)$actual['nombre_producto'];

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del producto no puede quedar vacío.');
        }

        $descripcion = array_key_exists('descripcion', $datos)
            ? trim((string)$datos['descripcion'])
            : (string)($actual['descripcion'] ?? '');

        $fotografia = array_key_exists('fotografia', $datos) || array_key_exists('imagen', $datos)
            ? trim((string)($datos['fotografia'] ?? $datos['imagen']))
            : (string)($actual['fotografia'] ?? '');

        $precio = array_key_exists('precio', $datos)
            ? round((float)$datos['precio'], 2)
            : (float)$actual['precio'];

        if ($precio < 0) {
            throw new InvalidArgumentException('El precio del producto no puede ser negativo.');
        }

        $idCategoria = array_key_exists('id_categoria', $datos) && (int)$datos['id_categoria'] > 0
            ? (int)$datos['id_categoria']
            : (int)$actual['id_categoria'];

        $estado = array_key_exists('estado_disponibilidad', $datos)
            ? $this->normalizarDisponibilidad((string)$datos['estado_disponibilidad'])
            : (string)$actual['estado_disponibilidad'];

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE productos
                SET nombre_producto       = :nombre_producto,
                    descripcion           = :descripcion,
                    fotografia            = :fotografia,
                    precio                = :precio,
                    estado_disponibilidad = :estado_disponibilidad,
                    id_categoria          = :id_categoria
                WHERE id_producto = :id_producto
            ");
            $stmt->bindValue(':nombre_producto', $nombre, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindValue(':fotografia', $fotografia, PDO::PARAM_STR);
            $stmt->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindValue(':estado_disponibilidad', $estado, PDO::PARAM_STR);
            $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $resultado = $stmt->execute();

            if (array_key_exists('recetas', $datos) && is_array($datos['recetas'])) {
                $stmtDel = $this->pdo->prepare("DELETE FROM recetas WHERE id_producto = :id_producto");
                $stmtDel->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtDel->execute();

                if (!empty($datos['recetas'])) {
                    $this->guardarRecetasInterno($idProducto, $datos['recetas']);
                }
            }

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
     * Cambia `estado_disponibilidad` entre `'Disponible'` e `'Inactivo'` (RF04).
     *
     * @param int $idProducto
     * @param string $nuevoEstado
     * @return bool
     */
    public function cambiarDisponibilidad(int $idProducto, string $nuevoEstado): bool
    {
        if ($idProducto <= 0) {
            return false;
        }

        $estadoNormalizado = $this->normalizarDisponibilidad($nuevoEstado);

        $stmt = $this->pdo->prepare("
            UPDATE productos
            SET estado_disponibilidad = :estado_disponibilidad
            WHERE id_producto = :id_producto
        ");
        $stmt->bindValue(':estado_disponibilidad', $estadoNormalizado, PDO::PARAM_STR);
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Alias de compatibilidad hacia `cambiarDisponibilidad` para controladores existentes.
     *
     * @param int|string $idProducto
     * @param mixed $estadoDisponibilidad
     * @return bool
     */
    public function actualizarDisponibilidad($idProducto, $estadoDisponibilidad): bool
    {
        $idInt = is_numeric($idProducto) ? (int)$idProducto : (int)preg_replace('/\D+/', '', (string)$idProducto);
        if (is_bool($estadoDisponibilidad)) {
            $estadoStr = $estadoDisponibilidad ? 'Disponible' : 'Inactivo';
        } else {
            $estadoStr = (string)$estadoDisponibilidad;
        }

        return $this->cambiarDisponibilidad($idInt, $estadoStr);
    }

    /**
     * Elimina el producto físicamente si no tiene historial en `detalle_pedidos`,
     * o lo desactiva de forma segura (`'Inactivo'`) si ya tiene ventas registradas (RF04).
     *
     * @param int $idProducto
     * @return bool
     */
    public function eliminar(int $idProducto): bool
    {
        if ($idProducto <= 0) {
            return false;
        }

        $stmtHist = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM detalle_pedidos
            WHERE id_producto = :id_producto
        ");
        $stmtHist->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmtHist->execute();

        if ((int)$stmtHist->fetchColumn() > 0) {
            return $this->cambiarDisponibilidad($idProducto, 'Inactivo');
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmtDelRec = $this->pdo->prepare("DELETE FROM recetas WHERE id_producto = :id_producto");
            $stmtDelRec->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmtDelRec->execute();

            $stmtDelProd = $this->pdo->prepare("DELETE FROM productos WHERE id_producto = :id_producto");
            $stmtDelProd->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $eliminado = $stmtDelProd->execute();

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return $eliminado;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene el precio unitario vigente de un producto.
     *
     * @param int $idProducto
     * @return float
     */
    public function obtenerPrecio(int $idProducto): float
    {
        if ($idProducto <= 0) {
            return 0.00;
        }

        $stmt = $this->pdo->prepare("
            SELECT precio
            FROM productos
            WHERE id_producto = :id_producto
            LIMIT 1
        ");
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();

        $precio = $stmt->fetchColumn();
        return $precio !== false ? round((float)$precio, 2) : 0.00;
    }

    /**
     * Inserta las relaciones de insumos de un producto en `recetas`.
     *
     * @param int $idProducto
     * @param array<int, mixed> $recetas
     * @return void
     */
    private function guardarRecetasInterno(int $idProducto, array $recetas): void
    {
        $stmtRec = $this->pdo->prepare("
            INSERT INTO recetas (cantidad_necesaria, id_producto, id_ingrediente)
            VALUES (:cantidad_necesaria, :id_producto, :id_ingrediente)
        ");

        foreach ($recetas as $item) {
            if (is_array($item)) {
                $idIngrediente = (int)($item['id_ingrediente'] ?? 0);
                $cantidadNec   = isset($item['cantidad_necesaria']) ? (float)$item['cantidad_necesaria'] : 1.0000;
            } else {
                $idIngrediente = (int)$item;
                $cantidadNec   = 1.0000;
            }

            if ($idIngrediente > 0 && $cantidadNec > 0) {
                $stmtRec->bindValue(':cantidad_necesaria', number_format($cantidadNec, 4, '.', ''), PDO::PARAM_STR);
                $stmtRec->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtRec->bindValue(':id_ingrediente', $idIngrediente, PDO::PARAM_INT);
                $stmtRec->execute();
            }
        }
    }

    /**
     * Normaliza el texto de disponibilidad a `'Disponible'` o `'Inactivo'`.
     *
     * @param string $estado
     * @return string
     */
    private function normalizarDisponibilidad(string $estado): string
    {
        $val = mb_strtolower(trim($estado), 'UTF-8');
        return in_array($val, ['disponible', 'activo', '1', 'true', 'si', 'sí'], true)
            ? 'Disponible'
            : 'Inactivo';
    }

    /**
     * Formatea tipos numéricos y de texto de una fila de producto.
     *
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private function formatearFila(array $fila): array
    {
        return [
            'id_producto'           => (int)$fila['id_producto'],
            'nombre_producto'       => (string)$fila['nombre_producto'],
            'descripcion'           => $fila['descripcion'] !== null ? (string)$fila['descripcion'] : '',
            'fotografia'            => $fila['fotografia'] !== null ? (string)$fila['fotografia'] : '',
            'precio'                => round((float)$fila['precio'], 2),
            'estado_disponibilidad' => (string)$fila['estado_disponibilidad'],
            'id_categoria'          => $fila['id_categoria'] !== null ? (int)$fila['id_categoria'] : null,
            'nombre_categoria'      => isset($fila['nombre_categoria']) ? (string)$fila['nombre_categoria'] : '',
            'ingredientes'          => isset($fila['ingredientes']) ? (string)$fila['ingredientes'] : 'Ingredientes de la casa',
        ];
    }
}
