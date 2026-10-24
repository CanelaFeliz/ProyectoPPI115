<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Categoria
 * models/Categoria.php
 * ==============================================================================
 */

class Categoria {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todas las categorías ordenadas por su ID
     *
     * @return array
     */
    public function obtenerTodas(): array {
        $stmt = $this->pdo->prepare("
            SELECT id_categoria, nombre_categoria 
            FROM categorias 
            ORDER BY id_categoria ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una categoría por su ID
     *
     * @param int $idCategoria
     * @return array|false
     */
    public function obtenerPorId(int $idCategoria) {
        $stmt = $this->pdo->prepare("
            SELECT id_categoria, nombre_categoria 
            FROM categorias 
            WHERE id_categoria = :id_categoria
            LIMIT 1
        ");
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
}

