<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Modelo: Usuario (`models/Usuario.php`)
 * Tablas: `usuarios`, `roles`
 * ==============================================================================
 */

class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna todas las cuentas de personal junto con su rol asignado
     * ordenadas por `u.id_usuario ASC`.
     *
     * @return array<int, array{id_usuario: int, nombre_completo: string, nombre_usuario: string, id_rol: int, nombre_rol: string}>
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id_usuario,
                u.nombre_completo,
                u.nombre_usuario,
                u.id_rol,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            ORDER BY u.id_usuario ASC
        ");
        $stmt->execute();

        return array_map([$this, 'formatearFila'], $stmt->fetchAll());
    }

    /**
     * Retorna todos los roles registrados en la tabla `roles`.
     *
     * @return array<int, array{id_rol: int, nombre_rol: string}>
     */
    public function obtenerRoles(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_rol, nombre_rol
            FROM roles
            ORDER BY id_rol ASC
        ");
        $stmt->execute();

        $roles = $stmt->fetchAll();
        foreach ($roles as &$rol) {
            $rol['id_rol']     = (int)$rol['id_rol'];
            $rol['nombre_rol'] = (string)$rol['nombre_rol'];
        }
        unset($rol);

        return $roles;
    }

    /**
     * Obtiene un empleado específico por su `id_usuario`.
     *
     * @param int $idUsuario
     * @return array{id_usuario: int, nombre_completo: string, nombre_usuario: string, id_rol: int, nombre_rol: string}|null
     */
    public function obtenerPorId(int $idUsuario): ?array
    {
        if ($idUsuario <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                u.id_usuario,
                u.nombre_completo,
                u.nombre_usuario,
                u.id_rol,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch();
        return $fila ? $this->formatearFila($fila) : null;
    }

    /**
     * Autentica al empleado por `nombre_usuario` o `id_usuario` verificando su clave
     * con `password_verify()` (RNF06). Soporta migración transparente a `password_hash()`
     * cuando la semilla inicial esté en SHA-256 o texto plano, y retorna los datos con `nombre_rol` (RNF04).
     *
     * @param string $usuario
     * @param string $password
     * @return array{id_usuario: int, nombre_completo: string, nombre_usuario: string, id_rol: int, nombre_rol: string}|null
     */
    public function autenticar(string $usuario, string $password): ?array
    {
        $identificador = trim($usuario);
        if ($identificador === '' || $password === '') {
            return null;
        }

        if (ctype_digit($identificador)) {
            $stmt = $this->pdo->prepare("
                SELECT
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.contrasena,
                    u.id_rol,
                    r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.nombre_usuario = :nombre_usuario
                   OR u.id_usuario = :id_usuario
                ORDER BY CASE WHEN u.nombre_usuario = :prioridad_usuario THEN 0 ELSE 1 END ASC
                LIMIT 1
            ");
            $stmt->bindValue(':nombre_usuario', $identificador, PDO::PARAM_STR);
            $stmt->bindValue(':id_usuario', (int)$identificador, PDO::PARAM_INT);
            $stmt->bindValue(':prioridad_usuario', $identificador, PDO::PARAM_STR);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT
                    u.id_usuario,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.contrasena,
                    u.id_rol,
                    r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.nombre_usuario = :nombre_usuario
                LIMIT 1
            ");
            $stmt->bindValue(':nombre_usuario', $identificador, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $hashAlmacenado = (string)$row['contrasena'];
        $autenticado    = false;
        $requiereMigrar = false;

        if (password_verify($password, $hashAlmacenado)) {
            $autenticado    = true;
            $requiereMigrar = password_needs_rehash($hashAlmacenado, PASSWORD_DEFAULT);
        } elseif (
            strlen($hashAlmacenado) === 64
            && ctype_xdigit($hashAlmacenado)
            && hash_equals(strtolower($hashAlmacenado), hash('sha256', $password))
        ) {
            // Semilla inicial SHA-256 -> migrar automáticamente a password_hash()
            $autenticado    = true;
            $requiereMigrar = true;
        } elseif (
            !str_starts_with($hashAlmacenado, '$2')
            && !str_starts_with($hashAlmacenado, '$argon')
            && hash_equals($hashAlmacenado, $password)
        ) {
            // Semilla inicial en texto plano -> migrar automáticamente a password_hash()
            $autenticado    = true;
            $requiereMigrar = true;
        }

        if (!$autenticado) {
            return null;
        }

        if ($requiereMigrar) {
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtMig = $this->pdo->prepare("
                UPDATE usuarios
                SET contrasena = :contrasena
                WHERE id_usuario = :id_usuario
            ");
            $stmtMig->bindValue(':contrasena', $nuevoHash, PDO::PARAM_STR);
            $stmtMig->bindValue(':id_usuario', (int)$row['id_usuario'], PDO::PARAM_INT);
            $stmtMig->execute();
        }

        return $this->formatearFila($row);
    }

    /**
     * Crea una nueva cuenta de empleado validando unicidad de `nombre_usuario` (RF13),
     * cifrando la clave con `password_hash` (RNF06) y resolviendo `id_rol` por entero o nombre.
     *
     * @param string $nombreCompleto
     * @param string $nombreUsuario
     * @param string $password
     * @param int|string $rol
     * @return int ID generado (`id_usuario`)
     */
    public function crear(string $nombreCompleto, string $nombreUsuario, string $password, $rol): int
    {
        $nombreLimpio  = trim($nombreCompleto);
        $usuarioLimpio = trim($nombreUsuario);

        if ($nombreLimpio === '' || $usuarioLimpio === '' || $password === '') {
            throw new InvalidArgumentException('Nombre completo, usuario y contraseña son obligatorios.');
        }

        $stmtExiste = $this->pdo->prepare("
            SELECT id_usuario
            FROM usuarios
            WHERE LOWER(TRIM(nombre_usuario)) = LOWER(:nombre_usuario)
            LIMIT 1
        ");
        $stmtExiste->bindValue(':nombre_usuario', $usuarioLimpio, PDO::PARAM_STR);
        $stmtExiste->execute();

        if ($stmtExiste->fetchColumn()) {
            throw new RuntimeException("El nombre de usuario '{$usuarioLimpio}' ya se encuentra registrado.");
        }

        $idRol = $this->resolverIdRol($rol);
        $hash  = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (nombre_completo, nombre_usuario, contrasena, id_rol)
            VALUES (:nombre_completo, :nombre_usuario, :contrasena, :id_rol)
        ");
        $stmt->bindValue(':nombre_completo', $nombreLimpio, PDO::PARAM_STR);
        $stmt->bindValue(':nombre_usuario', $usuarioLimpio, PDO::PARAM_STR);
        $stmt->bindValue(':contrasena', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualiza nombre completo, usuario, rol y opcionalmente contraseña (RF13, RNF06).
     *
     * @param int $idUsuario
     * @param array<string, mixed> $datos
     * @return bool
     */
    public function actualizar(int $idUsuario, array $datos): bool
    {
        $actual = $this->obtenerPorId($idUsuario);
        if (!$actual) {
            return false;
        }

        $nombreCompleto = array_key_exists('nombre_completo', $datos) || array_key_exists('nombre', $datos)
            ? trim((string)($datos['nombre_completo'] ?? $datos['nombre']))
            : $actual['nombre_completo'];

        $nombreUsuario = array_key_exists('nombre_usuario', $datos) || array_key_exists('usuario', $datos)
            ? trim((string)($datos['nombre_usuario'] ?? $datos['usuario']))
            : $actual['nombre_usuario'];

        if ($nombreCompleto === '' || $nombreUsuario === '') {
            throw new InvalidArgumentException('El nombre completo y el nombre de usuario no pueden quedar vacíos.');
        }

        $stmtDup = $this->pdo->prepare("
            SELECT id_usuario
            FROM usuarios
            WHERE LOWER(TRIM(nombre_usuario)) = LOWER(:nombre_usuario)
              AND id_usuario != :id_usuario
            LIMIT 1
        ");
        $stmtDup->bindValue(':nombre_usuario', $nombreUsuario, PDO::PARAM_STR);
        $stmtDup->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtDup->execute();

        if ($stmtDup->fetchColumn()) {
            throw new RuntimeException("El nombre de usuario '{$nombreUsuario}' ya está en uso por otro empleado.");
        }

        $rolEntrada = $datos['rol'] ?? ($datos['id_rol'] ?? ($datos['nombre_rol'] ?? null));
        $idRol = $rolEntrada !== null && $rolEntrada !== ''
            ? $this->resolverIdRol($rolEntrada)
            : $actual['id_rol'];

        $passwordRaw = isset($datos['contrasena'])
            ? (string)$datos['contrasena']
            : (isset($datos['password']) ? (string)$datos['password'] : '');

        if ($passwordRaw !== '') {
            $hash = password_hash($passwordRaw, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE usuarios
                SET nombre_completo = :nombre_completo,
                    nombre_usuario  = :nombre_usuario,
                    contrasena      = :contrasena,
                    id_rol          = :id_rol
                WHERE id_usuario = :id_usuario
            ");
            $stmt->bindValue(':contrasena', $hash, PDO::PARAM_STR);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE usuarios
                SET nombre_completo = :nombre_completo,
                    nombre_usuario  = :nombre_usuario,
                    id_rol          = :id_rol
                WHERE id_usuario = :id_usuario
            ");
        }

        $stmt->bindValue(':nombre_completo', $nombreCompleto, PDO::PARAM_STR);
        $stmt->bindValue(':nombre_usuario', $nombreUsuario, PDO::PARAM_STR);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina la cuenta del empleado desvinculando de forma segura referencias históricas en `pedidos` (RF13).
     *
     * @param int $idUsuario
     * @return bool
     */
    public function eliminar(int $idUsuario): bool
    {
        if ($idUsuario <= 0) {
            return false;
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmtDesvincular = $this->pdo->prepare("
                UPDATE pedidos
                SET id_usuario = NULL
                WHERE id_usuario = :id_usuario
            ");
            $stmtDesvincular->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtDesvincular->execute();

            $stmt = $this->pdo->prepare("
                DELETE FROM usuarios
                WHERE id_usuario = :id_usuario
            ");
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $resultado = $stmt->execute();

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return $resultado;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Resuelve el `id_rol` numérico tanto si `$rol` se recibe como ID entero
     * o como etiqueta textual del rol.
     *
     * @param int|string $rol
     * @return int
     */
    private function resolverIdRol($rol): int
    {
        if (is_int($rol) || (is_string($rol) && ctype_digit(trim($rol)))) {
            $idInt = (int)$rol;
            $stmtId = $this->pdo->prepare("SELECT id_rol FROM roles WHERE id_rol = :id_rol LIMIT 1");
            $stmtId->bindValue(':id_rol', $idInt, PDO::PARAM_INT);
            $stmtId->execute();
            $encontrado = $stmtId->fetchColumn();
            if ($encontrado !== false) {
                return (int)$encontrado;
            }
        }

        $textoRol = mb_strtolower(trim((string)$rol), 'UTF-8');
        $nombreCanonico = match ($textoRol) {
            'administrador', 'admin'                            => 'Administrador',
            'cajero', 'caja'                                    => 'Cajero',
            'mesero', 'mesera', 'personal de servicio', 'servicio' => 'Mesero',
            'barista'                                           => 'Barista',
            'cocina', 'cocinero', 'cocinera', 'chef'            => 'Cocina',
            default                                             => trim((string)$rol),
        };

        $stmtNom = $this->pdo->prepare("
            SELECT id_rol
            FROM roles
            WHERE LOWER(TRIM(nombre_rol)) = LOWER(:nombre_rol)
            LIMIT 1
        ");
        $stmtNom->bindValue(':nombre_rol', $nombreCanonico, PDO::PARAM_STR);
        $stmtNom->execute();
        $idEncontrado = $stmtNom->fetchColumn();

        if ($idEncontrado !== false) {
            return (int)$idEncontrado;
        }

        // Respaldo al rol Mesero (3) o al primer rol existente en BD
        $stmtFallback = $this->pdo->prepare("SELECT id_rol FROM roles ORDER BY id_rol ASC LIMIT 1");
        $stmtFallback->execute();
        $fallback = $stmtFallback->fetchColumn();

        return $fallback !== false ? (int)$fallback : 3;
    }

    /**
     * Formatea una fila de usuario ocultando siempre la columna `contrasena`.
     *
     * @param array<string, mixed> $fila
     * @return array{id_usuario: int, nombre_completo: string, nombre_usuario: string, id_rol: int, nombre_rol: string}
     */
    private function formatearFila(array $fila): array
    {
        return [
            'id_usuario'      => (int)$fila['id_usuario'],
            'nombre_completo' => (string)$fila['nombre_completo'],
            'nombre_usuario'  => (string)$fila['nombre_usuario'],
            'id_rol'          => (int)$fila['id_rol'],
            'nombre_rol'      => (string)$fila['nombre_rol'],
        ];
    }
}
