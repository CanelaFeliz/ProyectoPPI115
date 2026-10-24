<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Mesa
 * models/Mesa.php
 * Gestiona la configuración, capacidad y estados operativos del salón
 * ==============================================================================
 */

class Mesa {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene el listado completo de mesas ordenadas por ID
     *
     * @return array
     */
    public function obtenerTodas(): array {
        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, capacidad, estado 
            FROM mesas 
            ORDER BY id_mesa ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca una mesa por su ID primario o su número de mesa ('M-01', '1', etc.)
     *
     * @param int|string $id Identificador numérico o alfanumérico
     * @return array|false
     */
    public function obtenerPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, capacidad, estado 
            FROM mesas 
            WHERE id_mesa = :id_int 
               OR numero_mesa = :id_str 
               OR numero_mesa = :id_formatted
            LIMIT 1
        ");

        $idInt = is_numeric($id) ? (int)$id : 0;
        $idStr = (string)$id;
        $idFormatted = is_numeric($id) ? ('M-' . str_pad((string)$id, 2, '0', STR_PAD_LEFT)) : $idStr;

        $stmt->bindValue(':id_int', $idInt, PDO::PARAM_INT);
        $stmt->bindValue(':id_str', $idStr, PDO::PARAM_STR);
        $stmt->bindValue(':id_formatted', $idFormatted, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Actualiza el estado operativo de una mesa (ej. 'Disponible', 'Ocupada', 'En proceso', 'Sin servicio')
     *
     * @param int|string $idMesa
     * @param string $nuevoEstado
     * @return bool
     */
    public function actualizarEstado($idMesa, string $nuevoEstado): bool {
        $stmt = $this->pdo->prepare("
            UPDATE mesas 
            SET estado = :estado 
            WHERE id_mesa = :id_int 
               OR numero_mesa = :id_str 
               OR numero_mesa = :id_formatted
        ");

        $idInt = is_numeric($idMesa) ? (int)$idMesa : 0;
        $idStr = (string)$idMesa;
        $idFormatted = is_numeric($idMesa) ? ('M-' . str_pad((string)$idMesa, 2, '0', STR_PAD_LEFT)) : $idStr;

        $stmt->bindValue(':estado', trim($nuevoEstado), PDO::PARAM_STR);
        $stmt->bindValue(':id_int', $idInt, PDO::PARAM_INT);
        $stmt->bindValue(':id_str', $idStr, PDO::PARAM_STR);
        $stmt->bindValue(':id_formatted', $idFormatted, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Asegura la existencia de una mesa y retorna su id_mesa numérico real
     *
     * @param int|string $idMesa
     * @param string $estado
     * @param int $capacidad
     * @return int ID numérico de la mesa en la base de datos
     */
    public function asegurarMesa($idMesa, string $estado = 'Disponible', int $capacidad = 4): int {
        $mesa = $this->obtenerPorId($idMesa);
        if ($mesa) {
            return (int)$mesa['id_mesa'];
        }

        $numeroMesa = is_numeric($idMesa) 
            ? ('M-' . str_pad((string)$idMesa, 2, '0', STR_PAD_LEFT)) 
            : (string)$idMesa;

        $stmt = $this->pdo->prepare("
            INSERT INTO mesas (numero_mesa, capacidad, estado) 
            VALUES (:numero_mesa, :capacidad, :estado)
        ");
        $stmt->bindValue(':numero_mesa', $numeroMesa, PDO::PARAM_STR);
        $stmt->bindValue(':capacidad', $capacidad, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Registra una nueva mesa física en el sistema
     *
     * @param string $numeroMesa Código o número de mesa (ej. 'M-09')
     * @param int $capacidad Número de asientos
     * @param string $estado
     * @return int ID de la mesa insertada
     */
    public function crear(string $numeroMesa, int $capacidad = 4, string $estado = 'Disponible'): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO mesas (numero_mesa, capacidad, estado) 
            VALUES (:numero_mesa, :capacidad, :estado)
        ");
        $stmt->bindValue(':numero_mesa', trim($numeroMesa), PDO::PARAM_STR);
        $stmt->bindValue(':capacidad', $capacidad, PDO::PARAM_INT);
        $stmt->bindValue(':estado', trim($estado), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Elimina una mesa por su ID primario
     *
     * @param int $idMesa
     * @return bool
     */
    public function eliminar(int $idMesa): bool {
        $stmt = $this->pdo->prepare("DELETE FROM mesas WHERE id_mesa = :id_mesa");
        $stmt->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
