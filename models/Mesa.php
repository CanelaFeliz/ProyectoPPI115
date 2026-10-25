<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Mesa
 * models/Mesa.php
 * Gestiona el registro y los estados operativos del salón en la tabla `Mesas`
 * ==============================================================================
 */

class Mesa {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene el listado completo de mesas ordenadas por id_mesa
     *
     * @return array
     */
    public function obtenerTodas(): array {
        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, capacidad, estado_mesa 
            FROM Mesas 
            ORDER BY id_mesa ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca una mesa por su id_mesa o por su numero_mesa
     *
     * @param int|string $id Identificador o número de mesa
     * @return array|false
     */
    public function obtenerPorId($id) {
        $idInt = is_numeric($id) ? (int)$id : (int)preg_replace('/\D/', '', (string)$id);

        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, capacidad, estado_mesa 
            FROM Mesas 
            WHERE id_mesa = :id_mesa 
               OR numero_mesa = :numero_mesa
            LIMIT 1
        ");
        $stmt->bindValue(':id_mesa', $idInt, PDO::PARAM_INT);
        $stmt->bindValue(':numero_mesa', $idInt, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Actualiza el estado operativo de una mesa en la columna estado_mesa
     *
     * @param int|string $idMesa
     * @param string $nuevoEstado
     * @return bool
     */
    public function actualizarEstado($idMesa, string $nuevoEstado): bool {
        $idInt = is_numeric($idMesa) ? (int)$idMesa : (int)preg_replace('/\D/', '', (string)$idMesa);

        $stmt = $this->pdo->prepare("
            UPDATE Mesas 
            SET estado_mesa = :estado_mesa 
            WHERE id_mesa = :id_mesa 
               OR numero_mesa = :numero_mesa
        ");
        $stmt->bindValue(':estado_mesa', trim($nuevoEstado), PDO::PARAM_STR);
        $stmt->bindValue(':id_mesa', $idInt, PDO::PARAM_INT);
        $stmt->bindValue(':numero_mesa', $idInt, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Asegura la existencia de una mesa y retorna su id_mesa numérico real
     *
     * @param int|string $idMesa
     * @param string $estadoMesa
     * @param int $capacidad
     * @return int ID numérico de la mesa en la base de datos
     */
    public function asegurarMesa($idMesa, string $estadoMesa = 'Disponible', int $capacidad = 4): int {
        $mesa = $this->obtenerPorId($idMesa);
        if ($mesa) {
            return (int)$mesa['id_mesa'];
        }

        $numeroMesa = is_numeric($idMesa) ? (int)$idMesa : (int)preg_replace('/\D/', '', (string)$idMesa);
        if ($numeroMesa <= 0) {
            $numeroMesa = 1;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO Mesas (numero_mesa, capacidad, estado_mesa) 
            VALUES (:numero_mesa, :capacidad, :estado_mesa)
        ");
        $stmt->bindValue(':numero_mesa', $numeroMesa, PDO::PARAM_INT);
        $stmt->bindValue(':capacidad', max(1, $capacidad), PDO::PARAM_INT);
        $stmt->bindValue(':estado_mesa', trim($estadoMesa), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Registra una nueva mesa física en la tabla Mesas
     *
     * @param int|string $numeroMesa Número de la mesa (INT)
     * @param int|string $capacidad Capacidad de comensales (por defecto 4)
     * @param string $estadoMesa Estado operativo inicial (ej. 'Disponible')
     * @return int ID de la mesa insertada
     */
    public function crear($numeroMesa, $capacidad = 4, string $estadoMesa = 'Disponible'): int {
        if (is_string($capacidad) && !is_numeric($capacidad)) {
            $estadoMesa = $capacidad;
            $capacidad = 4;
        }

        $numInt = is_numeric($numeroMesa) ? (int)$numeroMesa : (int)preg_replace('/\D/', '', (string)$numeroMesa);
        if ($numInt <= 0) {
            $numInt = 1;
        }
        $capInt = max(1, (int)$capacidad);

        $stmt = $this->pdo->prepare("
            INSERT INTO Mesas (numero_mesa, capacidad, estado_mesa) 
            VALUES (:numero_mesa, :capacidad, :estado_mesa)
        ");
        $stmt->bindValue(':numero_mesa', $numInt, PDO::PARAM_INT);
        $stmt->bindValue(':capacidad', $capInt, PDO::PARAM_INT);
        $stmt->bindValue(':estado_mesa', trim($estadoMesa), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza los datos de una mesa existente
     *
     * @param int $idMesa
     * @param int $numeroMesa
     * @param int $capacidad
     * @param string $estadoMesa
     * @return bool
     */
    public function actualizar(int $idMesa, int $numeroMesa, int $capacidad, string $estadoMesa): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Mesas 
            SET numero_mesa = :numero_mesa, 
                capacidad = :capacidad, 
                estado_mesa = :estado_mesa 
            WHERE id_mesa = :id_mesa
        ");
        $stmt->bindValue(':numero_mesa', $numeroMesa, PDO::PARAM_INT);
        $stmt->bindValue(':capacidad', max(1, $capacidad), PDO::PARAM_INT);
        $stmt->bindValue(':estado_mesa', trim($estadoMesa), PDO::PARAM_STR);
        $stmt->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina una mesa por su id_mesa
     *
     * @param int $idMesa
     * @return bool
     */
    public function eliminar(int $idMesa): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Mesas WHERE id_mesa = :id_mesa");
        $stmt->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
