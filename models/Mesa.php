<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Modelo: Mesa (`models/Mesa.php`)
 * Tabla: `mesas`
 * ==============================================================================
 */

class Mesa
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna todas las mesas ordenadas por `numero_mesa ASC`.
     *
     * @return array<int, array{id_mesa: int, numero_mesa: int, estado_mesa: string, capacidad: int}>
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, estado_mesa, capacidad
            FROM mesas
            ORDER BY numero_mesa ASC
        ");
        $stmt->execute();

        return array_map([$this, 'formatearFila'], $stmt->fetchAll());
    }

    /**
     * Busca una mesa de forma flexible por `id_mesa` o `numero_mesa`,
     * limpiando prefijos como 'Mesa ' o 'M-'.
     *
     * @param int|string $id
     * @return array{id_mesa: int, numero_mesa: int, estado_mesa: string, capacidad: int}|null
     */
    public function obtenerPorId($id): ?array
    {
        $valorNumerico = $this->extraerEntero($id);
        if ($valorNumerico <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, estado_mesa, capacidad
            FROM mesas
            WHERE id_mesa = :id_mesa
               OR numero_mesa = :numero_mesa
            ORDER BY CASE WHEN id_mesa = :id_prioridad THEN 0 ELSE 1 END ASC
            LIMIT 1
        ");
        $stmt->bindValue(':id_mesa', $valorNumerico, PDO::PARAM_INT);
        $stmt->bindValue(':numero_mesa', $valorNumerico, PDO::PARAM_INT);
        $stmt->bindValue(':id_prioridad', $valorNumerico, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch();
        return $fila ? $this->formatearFila($fila) : null;
    }

    /**
     * Retorna únicamente las mesas disponibles para asignación (RF17).
     *
     * @return array<int, array{id_mesa: int, numero_mesa: int, estado_mesa: string, capacidad: int}>
     */
    public function obtenerDisponibles(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, estado_mesa, capacidad
            FROM mesas
            WHERE LOWER(TRIM(estado_mesa)) IN ('disponible', 'libre')
            ORDER BY numero_mesa ASC
        ");
        $stmt->execute();

        return array_map([$this, 'formatearFila'], $stmt->fetchAll());
    }

    /**
     * Registra una nueva mesa validando la unicidad de `numero_mesa` (RF05).
     *
     * @param int|string $numeroMesa
     * @param int $capacidad
     * @param string $estadoMesa
     * @return int ID primario generado (`id_mesa`)
     */
    public function crear(int|string $numeroMesa, int $capacidad = 4, string $estadoMesa = 'Disponible'): int
    {
        $numInt = $this->extraerEntero($numeroMesa);
        if ($numInt <= 0) {
            throw new InvalidArgumentException('El número de mesa debe ser un entero positivo.');
        }

        $capInt = max(1, $capacidad);
        $estadoNormalizado = $this->normalizarEstado($estadoMesa);

        $stmtExiste = $this->pdo->prepare("
            SELECT id_mesa
            FROM mesas
            WHERE numero_mesa = :numero_mesa
            LIMIT 1
        ");
        $stmtExiste->bindValue(':numero_mesa', $numInt, PDO::PARAM_INT);
        $stmtExiste->execute();

        if ($stmtExiste->fetchColumn()) {
            throw new RuntimeException("Ya existe una mesa registrada con el número {$numInt}.");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO mesas (numero_mesa, estado_mesa, capacidad)
            VALUES (:numero_mesa, :estado_mesa, :capacidad)
        ");
        $stmt->bindValue(':numero_mesa', $numInt, PDO::PARAM_INT);
        $stmt->bindValue(':estado_mesa', $estadoNormalizado, PDO::PARAM_STR);
        $stmt->bindValue(':capacidad', $capInt, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza los datos completos de una mesa existente.
     *
     * @param int $idMesa
     * @param int $numeroMesa
     * @param int $capacidad
     * @param string $estadoMesa
     * @return bool
     */
    public function actualizar(int $idMesa, int $numeroMesa, int $capacidad, string $estadoMesa): bool
    {
        if ($idMesa <= 0 || $numeroMesa <= 0) {
            throw new InvalidArgumentException('Identificador y número de mesa deben ser mayores a cero.');
        }

        $stmtDup = $this->pdo->prepare("
            SELECT id_mesa
            FROM mesas
            WHERE numero_mesa = :numero_mesa
              AND id_mesa != :id_mesa
            LIMIT 1
        ");
        $stmtDup->bindValue(':numero_mesa', $numeroMesa, PDO::PARAM_INT);
        $stmtDup->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        $stmtDup->execute();

        if ($stmtDup->fetchColumn()) {
            throw new RuntimeException("El número de mesa {$numeroMesa} ya está asignado a otra mesa.");
        }

        $estadoNormalizado = $this->normalizarEstado($estadoMesa);
        $capInt = max(1, $capacidad);

        $stmt = $this->pdo->prepare("
            UPDATE mesas
            SET numero_mesa = :numero_mesa,
                capacidad   = :capacidad,
                estado_mesa = :estado_mesa
            WHERE id_mesa = :id_mesa
        ");
        $stmt->bindValue(':numero_mesa', $numeroMesa, PDO::PARAM_INT);
        $stmt->bindValue(':capacidad', $capInt, PDO::PARAM_INT);
        $stmt->bindValue(':estado_mesa', $estadoNormalizado, PDO::PARAM_STR);
        $stmt->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Normaliza el estado recibido desde el frontend y actualiza la mesa
     * buscando por `id_mesa` o `numero_mesa` (RF06, RF08, RF17).
     *
     * @param int|string $idMesaONumero
     * @param string $estadoMesa
     * @return bool
     */
    public function actualizarEstado($idMesaONumero, string $estadoMesa): bool
    {
        $mesa = $this->obtenerPorId($idMesaONumero);
        if (!$mesa) {
            return false;
        }

        $estadoNormalizado = $this->normalizarEstado($estadoMesa);

        $stmt = $this->pdo->prepare("
            UPDATE mesas
            SET estado_mesa = :estado_mesa
            WHERE id_mesa = :id_mesa
        ");
        $stmt->bindValue(':estado_mesa', $estadoNormalizado, PDO::PARAM_STR);
        $stmt->bindValue(':id_mesa', $mesa['id_mesa'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina una mesa física validando que no posea órdenes activas pendientes.
     *
     * @param int $idMesa
     * @return bool
     */
    public function eliminar(int $idMesa): bool
    {
        if ($idMesa <= 0) {
            return false;
        }

        $stmtActivos = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM pedidos
            WHERE id_mesa = :id_mesa
              AND LOWER(TRIM(estado_pedido)) NOT IN ('pagado', 'finalizado', 'cancelado', 'entregado')
        ");
        $stmtActivos->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        $stmtActivos->execute();

        if ((int)$stmtActivos->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar una mesa con órdenes activas en curso.');
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            // Desvincular pedidos históricos cerrados para preservar el registro contable
            $stmtDesvincular = $this->pdo->prepare("
                UPDATE pedidos
                SET id_mesa = NULL
                WHERE id_mesa = :id_mesa
            ");
            $stmtDesvincular->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $stmtDesvincular->execute();

            $stmt = $this->pdo->prepare("
                DELETE FROM mesas
                WHERE id_mesa = :id_mesa
            ");
            $stmt->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $eliminado = $stmt->execute();

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
     * Resuelve el `id_mesa` real de una mesa existente o la crea si no existe.
     * Mantiene compatibilidad con controladores de servicio y cliente.
     *
     * @param int|string $idMesa
     * @param string $estadoMesa
     * @param int $capacidad
     * @return int
     */
    public function asegurarMesa($idMesa, string $estadoMesa = 'Disponible', int $capacidad = 4): int
    {
        $mesa = $this->obtenerPorId($idMesa);
        if ($mesa) {
            return $mesa['id_mesa'];
        }

        $numInt = $this->extraerEntero($idMesa);
        if ($numInt <= 0) {
            $numInt = 1;
        }

        return $this->crear($numInt, $capacidad, $estadoMesa);
    }

    /**
     * Normaliza slugs y etiquetas de estado a los valores canónicos del sistema.
     *
     * @param string $estado
     * @return string
     */
    public function normalizarEstado(string $estado): string
    {
        $limpio = mb_strtolower(trim($estado), 'UTF-8');
        $slug = preg_replace('/[\s_]+/', '-', $limpio) ?? $limpio;

        return match ($slug) {
            'disponible', 'libre'                                        => 'Disponible',
            'ocupada', 'ocupado'                                         => 'Ocupada',
            'proceso', 'en-proceso'                                      => 'En proceso',
            'sin-servicio', 'fuera-de-servicio', 'inactiva', 'inactivo'  => 'Sin servicio',
            'reservada', 'reservado'                                     => 'Reservada',
            default                                                      => 'Disponible',
        };
    }

    /**
     * Extrae el valor entero positivo de un identificador o etiqueta ('Mesa 5', 'M-03', 5).
     *
     * @param mixed $valor
     * @return int
     */
    private function extraerEntero($valor): int
    {
        if (is_int($valor)) {
            return $valor;
        }
        if (is_numeric($valor)) {
            return (int)$valor;
        }
        $soloDigitos = preg_replace('/\D+/', '', (string)$valor);
        return ($soloDigitos !== null && $soloDigitos !== '') ? (int)$soloDigitos : 0;
    }

    /**
     * Normaliza los tipos de datos de una fila de la tabla `mesas`.
     *
     * @param array<string, mixed> $fila
     * @return array{id_mesa: int, numero_mesa: int, estado_mesa: string, capacidad: int}
     */
    private function formatearFila(array $fila): array
    {
        return [
            'id_mesa'     => (int)$fila['id_mesa'],
            'numero_mesa' => (int)$fila['numero_mesa'],
            'estado_mesa' => (string)$fila['estado_mesa'],
            'capacidad'   => (int)$fila['capacidad'],
        ];
    }
}
