<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Categoria
 * models/Categoria.php
 * Gestiona las categorías del menú en la tabla `Categorias`
 * ==============================================================================
 */

class Categoria {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las categorías ordenadas por su ID (id_categoria)
     *
     * @return array
     */
    public function obtenerTodas(): array {
        $stmt = $this->pdo->prepare("
            SELECT id_categoria, nombre_categoria 
            FROM Categorias 
            ORDER BY id_categoria ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una categoría por su ID primario (id_categoria)
     *
     * @param int $idCategoria
     * @return array|false
     */
    public function obtenerPorId(int $idCategoria) {
        $stmt = $this->pdo->prepare("
            SELECT id_categoria, nombre_categoria 
            FROM Categorias 
            WHERE id_categoria = :id_categoria
            LIMIT 1
        ");
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Registra una nueva categoría en la tabla Categorias
     *
     * @param string $nombreCategoria
     * @return int ID de la categoría insertada
     */
    public function crear(string $nombreCategoria): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO Categorias (nombre_categoria) 
            VALUES (:nombre_categoria)
        ");
        $stmt->bindValue(':nombre_categoria', trim($nombreCategoria), PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza el nombre de una categoría existente
     *
     * @param int $idCategoria
     * @param string $nombreCategoria
     * @return bool
     */
    public function actualizar(int $idCategoria, string $nombreCategoria): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Categorias 
            SET nombre_categoria = :nombre_categoria 
            WHERE id_categoria = :id_categoria
        ");
        $stmt->bindValue(':nombre_categoria', trim($nombreCategoria), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina una categoría por su ID primario
     *
     * @param int $idCategoria
     * @return bool
     */
    public function eliminar(int $idCategoria): bool {
        $stmt = $this->pdo->prepare("
            DELETE FROM Categorias 
            WHERE id_categoria = :id_categoria
        ");
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
