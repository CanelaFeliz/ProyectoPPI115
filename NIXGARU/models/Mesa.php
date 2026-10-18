<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Mesa
 * models/Mesa.php
 * ==============================================================================
 */

class Mesa {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ejecuta la consulta SQL para listar todas las mesas
     * Consulta extraída de views/servicio/mesas.php
     *
     * @return array
     */
    public function obtenerTodas(): array {
        $stmt = $this->pdo->query("SELECT id_mesa, numero_mesa, capacidad, estado FROM mesas ORDER BY id_mesa ASC");
        return $stmt->fetchAll();
    }

    /**
     * Extrae la información de una mesa específica según su ID o número de mesa
     *
     * @param int|string $id ID de la mesa o número de mesa
     * @return array|false
     */
    public function obtenerPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT id_mesa, numero_mesa, capacidad, estado 
            FROM mesas 
            WHERE id_mesa = ? OR numero_mesa = ? 
            LIMIT 1
        ");
        $stmt->execute([$id, (string)$id]);
        return $stmt->fetch();
    }

    /**
     * Actualiza el estado de una mesa por ID o número de mesa
     *
     * @param int|string $idMesa ID o número de la mesa
     * @param string $nuevoEstado Nuevo estado de la mesa (ej. 'En proceso', 'Disponible')
     * @return bool
     */
    public function actualizarEstado($idMesa, string $nuevoEstado): bool {
        $stmt = $this->pdo->prepare("UPDATE mesas SET estado = ? WHERE id_mesa = ? OR numero_mesa = ?");
        return $stmt->execute([$nuevoEstado, $idMesa, (string)$idMesa]);
    }

    /**
     * Obtiene el ID numérico real de la mesa o la crea si no existe
     *
     * @param int|string $idMesa
     * @param string $estado
     * @param int $capacidad
     * @return int
     */
    public function asegurarMesa($idMesa, string $estado = 'Disponible', int $capacidad = 4): int {
        $mesa = $this->obtenerPorId($idMesa);
        if (!$mesa) {
            $stmt = $this->pdo->prepare("INSERT INTO mesas (id_mesa, numero_mesa, capacidad, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([(int)$idMesa, (string)$idMesa, $capacidad, $estado]);
            return (int)$idMesa;
        }
        return (int)$mesa['id_mesa'];
    }
}

