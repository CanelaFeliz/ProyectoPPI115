<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Usuario
 * models/Usuario.php
 * Gestiona cuentas de personal, autenticación segura y roles operativos
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
                u.usuario,
                u.id_rol,
                u.estado,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            ORDER BY u.id_usuario ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos de un usuario por su ID
     *
     * @param int $idUsuario
     * @return array|false
     */
    public function obtenerPorId(int $idUsuario) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_usuario,
                u.nombre_completo,
                u.usuario,
                u.id_rol,
                u.estado,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtiene los datos de un usuario por su nombre de usuario único
     *
     * @param string $username
     * @return array|false
     */
    public function obtenerPorUsuario(string $username) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_usuario,
                u.nombre_completo,
                u.usuario,
                u.contrasenha,
                u.id_rol,
                u.estado,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.usuario = :usuario
            LIMIT 1
        ");
        $stmt->bindValue(':usuario', trim($username), PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Autentica a un usuario recibiendo credenciales y verificando contraseña cifrada (RNF06)
     *
     * @param string $username Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @return array|false Datos del usuario y rol si es válido; false en caso contrario
     */
    public function autenticar(string $username, string $password) {
        $usuario = $this->obtenerPorUsuario($username);

        if (!$usuario) {
            return false;
        }

        // Si el usuario está deshabilitado en el sistema
        if (isset($usuario['estado']) && (int)$usuario['estado'] === 0) {
            return false;
        }

        // Verificación con password_verify para cumplimiento de RNF06
        $passwordValida = false;
        if (password_verify($password, $usuario['contrasenha'])) {
            $passwordValida = true;
        } elseif ($usuario['contrasenha'] === $password) {
            // Compatibilidad legacy: migración automática y segura a hash Bcrypt
            $passwordValida = true;
            $nuevoHash = password_hash($password, PASSWORD_BCRYPT);
            $stmtUpd = $this->pdo->prepare("UPDATE usuarios SET contrasenha = :pass WHERE id_usuario = :id");
            $stmtUpd->bindValue(':pass', $nuevoHash, PDO::PARAM_STR);
            $stmtUpd->bindValue(':id', $usuario['id_usuario'], PDO::PARAM_INT);
            $stmtUpd->execute();
            $usuario['contrasenha'] = $nuevoHash;
        }

        if (!$passwordValida) {
            return false;
        }

        // No exponer el hash en el arreglo de retorno
        unset($usuario['contrasenha']);

        return $usuario;
    }

    /**
     * Registra una nueva cuenta de usuario
     *
     * @param string $nombreCompleto
     * @param string $usuario
     * @param string $password Contraseña en texto plano (se cifra con BCRYPT)
     * @param int $idRol
     * @param int $estado
     * @return int ID del usuario creado
     */
    public function crear(
        string $nombreCompleto, 
        string $usuario, 
        string $password, 
        int $idRol, 
        int $estado = 1
    ): int {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (
                nombre_completo, 
                usuario, 
                contrasenha, 
                id_rol, 
                estado
            ) VALUES (
                :nombre, 
                :usuario, 
                :password, 
                :id_rol, 
                :estado
            )
        ");
        $stmt->bindValue(':nombre', trim($nombreCompleto), PDO::PARAM_STR);
        $stmt->bindValue(':usuario', trim($usuario), PDO::PARAM_STR);
        $stmt->bindValue(':password', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza la información de un empleado
     *
     * @param int $idUsuario
     * @param string $nombreCompleto
     * @param string $usuario
     * @param string|null $password Si se proporciona, se actualiza el hash; si es null, se conserva la contraseña actual
     * @param int $idRol
     * @param int $estado
     * @return bool
     */
    public function actualizar(
        int $idUsuario, 
        string $nombreCompleto, 
        string $usuario, 
        ?string $password, 
        int $idRol, 
        int $estado
    ): bool {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("
                UPDATE usuarios 
                SET nombre_completo = :nombre,
                    usuario = :usuario,
                    contrasenha = :password,
                    id_rol = :id_rol,
                    estado = :estado
                WHERE id_usuario = :id_usuario
            ");
            $stmt->bindValue(':password', $hash, PDO::PARAM_STR);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE usuarios 
                SET nombre_completo = :nombre,
                    usuario = :usuario,
                    id_rol = :id_rol,
                    estado = :estado
                WHERE id_usuario = :id_usuario
            ");
        }

        $stmt->bindValue(':nombre', trim($nombreCompleto), PDO::PARAM_STR);
        $stmt->bindValue(':usuario', trim($usuario), PDO::PARAM_STR);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deshabilita o elimina un usuario por su ID
     *
     * @param int $idUsuario
     * @return bool
     */
    public function eliminar(int $idUsuario): bool {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
