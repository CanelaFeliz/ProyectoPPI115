<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Usuario
 * models/Usuario.php
 * Gestiona cuentas de personal, autenticación segura y roles en `Usuarios` y `Roles`
 * ==============================================================================
 */

class Usuario {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas las cuentas de personal junto con el nombre de su rol
     *
     * @return array
     */
    public function obtenerTodos(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_usuario,
                u.nombre_completo,
                u.nombre_usuario,
                u.id_rol,
                r.nombre_rol
            FROM Usuarios u
            INNER JOIN Roles r ON u.id_rol = r.id_rol
            ORDER BY u.id_usuario ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos de un usuario por su id_usuario
     *
     * @param int $idUsuario
     * @return array|false
     */
    public function obtenerPorId(int $idUsuario) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_usuario,
                u.nombre_completo,
                u.nombre_usuario,
                u.id_rol,
                r.nombre_rol
            FROM Usuarios u
            INNER JOIN Roles r ON u.id_rol = r.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtiene los datos de un usuario por su nombre_usuario
     *
     * @param string $nombreUsuario
     * @return array|false
     */
    public function obtenerPorUsuario(string $nombreUsuario) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_usuario,
                u.nombre_completo,
                u.nombre_usuario,
                u.contrasena,
                u.id_rol,
                r.nombre_rol
            FROM Usuarios u
            INNER JOIN Roles r ON u.id_rol = r.id_rol
            WHERE u.nombre_usuario = :nombre_usuario
            LIMIT 1
        ");
        $stmt->bindValue(':nombre_usuario', trim($nombreUsuario), PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtiene todos los roles disponibles en la tabla `Roles`
     *
     * @return array
     */
    public function obtenerRoles(): array {
        $stmt = $this->pdo->prepare("
            SELECT id_rol, nombre_rol 
            FROM Roles 
            ORDER BY id_rol ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Autentica a un usuario verificando su contraseña contra la columna `contrasena`
     * Soporta Bcrypt (password_verify), SHA-256 (datos semilla de nixgaru_db.sql) y texto plano
     *
     * @param string $nombreUsuario Nombre de usuario (nombre_usuario)
     * @param string $password Contraseña en texto plano
     * @return array|false Datos del usuario y rol si es válido; false en caso contrario
     */
    public function autenticar(string $nombreUsuario, string $password) {
        $usuario = $this->obtenerPorUsuario($nombreUsuario);

        if (!$usuario) {
            return false;
        }

        $hashAlmacenado = (string)$usuario['contrasena'];
        $passwordValida = false;

        if (password_verify($password, $hashAlmacenado)) {
            $passwordValida = true;
        } elseif (hash_equals($hashAlmacenado, hash('sha256', $password))) {
            $passwordValida = true;
        } elseif (hash_equals($hashAlmacenado, $password)) {
            $passwordValida = true;
        }

        if (!$passwordValida) {
            return false;
        }

        // No exponer el hash en el arreglo de retorno
        unset($usuario['contrasena']);

        return $usuario;
    }

    /**
     * Registra una nueva cuenta de usuario en la tabla `Usuarios`
     *
     * @param string $nombreCompleto
     * @param string $nombreUsuario
     * @param string $password Contraseña en texto plano
     * @param int $idRol
     * @return int ID del usuario creado
     */
    public function crear(
        string $nombreCompleto, 
        string $nombreUsuario, 
        string $password, 
        int $idRol
    ): int {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("
            INSERT INTO Usuarios (
                nombre_completo, 
                nombre_usuario, 
                contrasena, 
                id_rol
            ) VALUES (
                :nombre_completo, 
                :nombre_usuario, 
                :contrasena, 
                :id_rol
            )
        ");
        $stmt->bindValue(':nombre_completo', trim($nombreCompleto), PDO::PARAM_STR);
        $stmt->bindValue(':nombre_usuario', trim($nombreUsuario), PDO::PARAM_STR);
        $stmt->bindValue(':contrasena', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza la información de un usuario en la tabla `Usuarios`
     *
     * @param int $idUsuario
     * @param string $nombreCompleto
     * @param string $nombreUsuario
     * @param string|null $password Si se proporciona, se actualiza el hash; si es null, se conserva la contraseña actual
     * @param int $idRol
     * @return bool
     */
    public function actualizar(
        int $idUsuario, 
        string $nombreCompleto, 
        string $nombreUsuario, 
        ?string $password, 
        int $idRol
    ): bool {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("
                UPDATE Usuarios 
                SET nombre_completo = :nombre_completo,
                    nombre_usuario = :nombre_usuario,
                    contrasena = :contrasena,
                    id_rol = :id_rol
                WHERE id_usuario = :id_usuario
            ");
            $stmt->bindValue(':contrasena', $hash, PDO::PARAM_STR);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE Usuarios 
                SET nombre_completo = :nombre_completo,
                    nombre_usuario = :nombre_usuario,
                    id_rol = :id_rol
                WHERE id_usuario = :id_usuario
            ");
        }

        $stmt->bindValue(':nombre_completo', trim($nombreCompleto), PDO::PARAM_STR);
        $stmt->bindValue(':nombre_usuario', trim($nombreUsuario), PDO::PARAM_STR);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un usuario por su id_usuario
     *
     * @param int $idUsuario
     * @return bool
     */
    public function eliminar(int $idUsuario): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Usuarios WHERE id_usuario = :id_usuario");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
