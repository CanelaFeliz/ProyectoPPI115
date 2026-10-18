<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Usuario
 * models/Usuario.php
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
     * Autentica a un usuario recibiendo credenciales y verificando contraseña cifrada (RNF06)
     * Consulta en la base de datos haciendo JOIN con la tabla roles.
     *
     * @param string $username Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @return array|false Arreglo asociativo con datos del usuario y su rol si es correcto; false en caso contrario
     */
    public function autenticar(string $username, string $password) {
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
            WHERE u.usuario = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return false;
        }

        // Si el usuario está deshabilitado en el sistema
        if (isset($usuario['estado']) && (int)$usuario['estado'] === 0) {
            return false;
        }

        // Verificación con password_verify para cumplimiento de RNF06 (cifrado hash)
        $passwordValida = false;
        if (password_verify($password, $usuario['contrasenha'])) {
            $passwordValida = true;
        } elseif ($usuario['contrasenha'] === $password) {
            // Compatibilidad legacy: migración automática y segura a hash Bcrypt
            $passwordValida = true;
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpd = $this->pdo->prepare("UPDATE usuarios SET contrasenha = ? WHERE id_usuario = ?");
            $stmtUpd->execute([$nuevoHash, $usuario['id_usuario']]);
            $usuario['contrasenha'] = $nuevoHash;
        }

        if (!$passwordValida) {
            return false;
        }

        // No exponer el hash en el retorno
        unset($usuario['contrasenha']);

        return $usuario;
    }
}

