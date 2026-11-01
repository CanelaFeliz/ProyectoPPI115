<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Modelo: Categoria (`models/Categoria.php`)
 * Tabla: `categorias`
 * ==============================================================================
 */

class Categoria
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna todas las categorías ordenadas por `id_categoria ASC`.
     *
     * @return array<int, array{id_categoria: int, nombre_categoria: string}>
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_categoria, nombre_categoria
            FROM categorias
            ORDER BY id_categoria ASC
        ");
        $stmt->execute();

        $filas = $stmt->fetchAll();
        foreach ($filas as &$fila) {
            $fila['id_categoria']     = (int)$fila['id_categoria'];
            $fila['nombre_categoria'] = (string)$fila['nombre_categoria'];
        }
        unset($fila);

        return $filas;
    }

    /**
     * Obtiene una categoría específica por su identificador primario.
     *
     * @param int $idCategoria
     * @return array{id_categoria: int, nombre_categoria: string}|null
     */
    public function obtenerPorId(int $idCategoria): ?array
    {
        if ($idCategoria <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT id_categoria, nombre_categoria
            FROM categorias
            WHERE id_categoria = :id_categoria
            LIMIT 1
        ");
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }

        return [
            'id_categoria'     => (int)$fila['id_categoria'],
            'nombre_categoria' => (string)$fila['nombre_categoria'],
        ];
    }

    /**
     * Crea una nueva categoría validando que el nombre no esté vacío ni duplicado.
     *
     * @param string $nombreCategoria
     * @return int ID generado (`id_categoria`)
     */
    public function crear(string $nombreCategoria): int
    {
        $nombreLimpio = trim($nombreCategoria);
        if ($nombreLimpio === '') {
            throw new InvalidArgumentException('El nombre de la categoría es obligatorio.');
        }

        $stmtDup = $this->pdo->prepare("
            SELECT id_categoria
            FROM categorias
            WHERE LOWER(TRIM(nombre_categoria)) = LOWER(:nombre_categoria)
            LIMIT 1
        ");
        $stmtDup->bindValue(':nombre_categoria', $nombreLimpio, PDO::PARAM_STR);
        $stmtDup->execute();
        if ($stmtDup->fetchColumn()) {
            throw new RuntimeException('Ya existe una categoría con ese nombre.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO categorias (nombre_categoria)
            VALUES (:nombre_categoria)
        ");
        $stmt->bindValue(':nombre_categoria', $nombreLimpio, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza el nombre de una categoría existente.
     *
     * @param int $idCategoria
     * @param string $nombreCategoria
     * @return bool
     */
    public function actualizar(int $idCategoria, string $nombreCategoria): bool
    {
        $nombreLimpio = trim($nombreCategoria);
        if ($idCategoria <= 0 || $nombreLimpio === '') {
            throw new InvalidArgumentException('Datos de categoría no válidos para actualizar.');
        }

        $stmt = $this->pdo->prepare("
            UPDATE categorias
            SET nombre_categoria = :nombre_categoria
            WHERE id_categoria = :id_categoria
        ");
        $stmt->bindValue(':nombre_categoria', $nombreLimpio, PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina una categoría verificando que no tenga productos asociados.
     *
     * @param int $idCategoria
     * @return bool
     */
    public function eliminar(int $idCategoria): bool
    {
        if ($idCategoria <= 0) {
            return false;
        }

        $stmtUso = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM productos
            WHERE id_categoria = :id_categoria
        ");
        $stmtUso->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmtUso->execute();

        if ((int)$stmtUso->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar la categoría porque tiene productos asociados.');
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM categorias
            WHERE id_categoria = :id_categoria
        ");
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
