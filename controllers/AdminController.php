<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Controlador: AdminController (`controllers/AdminController.php`)
 * Descripción: Gestiona las operaciones administrativas del catálogo y categorías,
 *              distribución de mesas, ajustes de inventario físico, cuentas de
 *              personal con contraseñas cifradas y reportes analíticos de ventas
 *              y demanda por rango de fechas (RF04, RF05, RF11, RF12, RF13, RF14, RNF06).
 * ==============================================================================
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Pedido.php';

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enruta la acción administrativa solicitada hacia su método correspondiente.
     *
     * @param string $action
     * @param array<string, mixed> $input
     * @return void
     */
    public function manejarPeticion(string $action, array $input): void
    {
        try {
            switch ($action) {
                // 1. Catálogo y Categorías (RF04)
                case 'toggle_disponibilidad':
                case 'cambiar_disponibilidad':
                    $this->toggleDisponibilidad($input);
                    break;

                case 'guardar_producto':
                case 'crear_producto':
                    $this->guardarProducto($input);
                    break;

                case 'actualizar_producto':
                case 'editar_producto':
                    $this->actualizarProducto($input);
                    break;

                case 'eliminar_producto':
                    $this->eliminarProducto($input);
                    break;

                case 'crear_categoria':
                    $this->crearCategoria($input);
                    break;

                case 'actualizar_categoria':
                    $this->actualizarCategoria($input);
                    break;

                case 'eliminar_categoria':
                    $this->eliminarCategoria($input);
                    break;

                // 2. Mesas (RF05)
                case 'crear_mesa':
                    $this->crearMesa($input);
                    break;

                case 'actualizar_mesa':
                case 'actualizar_estado_mesa':
                    $this->actualizarMesa($input);
                    break;

                case 'eliminar_mesa':
                    $this->eliminarMesa($input);
                    break;

                // 3. Inventarios (RF14)
                case 'ajustar_inventario':
                case 'ajustar_stock':
                    $this->ajustarInventario($input);
                    break;

                // 4. Cuentas de Personal (RF13, RNF06)
                case 'crear_usuario':
                    $this->crearUsuario($input);
                    break;

                case 'actualizar_usuario':
                    $this->actualizarUsuario($input);
                    break;

                case 'eliminar_usuario':
                    $this->eliminarUsuario($input);
                    break;

                // 5. Reportes de Ventas y Demanda (RF11, RF12)
                case 'filtrar_ventas':
                case 'dashboard':
                    $this->filtrarVentas($input);
                    break;

                default:
                    $this->responderJson([
                        'success' => false,
                        'error'   => 'Acción administrativa no soportada o no especificada.',
                    ], 400);
                    break;
            }
        } catch (InvalidArgumentException $e) {
            $this->responderJson(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $mensaje = $e->getMessage();
            $codigo  = (stripos($mensaje, 'no existe') !== false) ? 404 : 400;
            $this->responderJson(['success' => false, 'error' => $mensaje], $codigo);
        } catch (PDOException $e) {
            error_log('[NIXGARU][AdminController][PDOException] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error de base de datos al ejecutar la operación administrativa.'], 500);
        } catch (Throwable $e) {
            error_log('[NIXGARU][AdminController][Throwable] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error interno en el módulo administrativo.'], 500);
        }
    }

    /**
     * `action=toggle_disponibilidad`: cambia o alterna `estado_disponibilidad`
     * entre `'Disponible'` e `'Inactivo'` (RF04).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function toggleDisponibilidad(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawId      = $input['id_producto'] ?? ($input['codigo'] ?? ($input['id'] ?? 0));
        $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);

        if ($idProducto <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'El identificador del producto es obligatorio.',
            ], 400);
            return;
        }

        $productoModel  = new Producto($this->pdo);
        $productoActual = $productoModel->obtenerPorId($idProducto);

        if (!$productoActual) {
            $this->responderJson([
                'success' => false,
                'error'   => 'El producto solicitado no existe en el catálogo.',
            ], 404);
            return;
        }

        $estadoRecibido = $input['estado'] ?? ($input['estado_disponibilidad'] ?? ($input['disponibilidad'] ?? null));
        if ($estadoRecibido === null || (is_string($estadoRecibido) && trim($estadoRecibido) === '')) {
            $estaActivo  = in_array(
                mb_strtolower(trim((string)$productoActual['estado_disponibilidad']), 'UTF-8'),
                ['disponible', 'activo', '1'],
                true
            );
            $nuevoEstado = $estaActivo ? 'Inactivo' : 'Disponible';
        } elseif (is_bool($estadoRecibido)) {
            $nuevoEstado = $estadoRecibido ? 'Disponible' : 'Inactivo';
        } else {
            $nuevoEstado = (string)$estadoRecibido;
        }

        $actualizado       = $productoModel->cambiarDisponibilidad($idProducto, $nuevoEstado);
        $productoRefrescado = $productoModel->obtenerPorId($idProducto);

        $this->responderJson([
            'success'               => $actualizado,
            'id_producto'           => $idProducto,
            'estado_disponibilidad' => $productoRefrescado ? $productoRefrescado['estado_disponibilidad'] : $nuevoEstado,
            'producto'              => $productoRefrescado,
            'message'               => 'Disponibilidad del producto actualizada correctamente.',
        ], 200);
    }

    /**
     * `action=guardar_producto`: crea un nuevo producto o actualiza uno existente si se
     * incluye `id_producto`, soportando JSON y carga de imagen vía `multipart/form-data` (RF04).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function guardarProducto(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $datos = $this->prepararDatosProducto($input);

        if (!empty($datos['id_producto']) && (int)$datos['id_producto'] > 0) {
            $this->actualizarProducto($datos);
            return;
        }

        $productoModel = new Producto($this->pdo);
        $idProducto    = $productoModel->crear($datos);
        $producto      = $productoModel->obtenerPorId($idProducto);
        $total         = count($productoModel->obtenerTodos());

        $this->responderJson([
            'success'         => true,
            'id_producto'     => $idProducto,
            'producto'        => $producto,
            'total_productos' => $total,
            'message'         => 'Producto registrado exitosamente en el catálogo.',
        ], 200);
    }

    /**
     * `action=actualizar_producto`: edita un producto existente soportando JSON
     * y `multipart/form-data` con subida opcional de imagen a `public/img/` (RF04).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function actualizarProducto(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $datos      = $this->prepararDatosProducto($input);
        $rawId      = $datos['id_producto'] ?? ($datos['id'] ?? 0);
        $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);

        if ($idProducto <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar un identificador de producto válido para actualizar.',
            ], 400);
            return;
        }

        $productoModel  = new Producto($this->pdo);
        $productoActual = $productoModel->obtenerPorId($idProducto);
        if (!$productoActual) {
            $this->responderJson([
                'success' => false,
                'error'   => 'El producto que intenta actualizar no existe.',
            ], 404);
            return;
        }

        $actualizado        = $productoModel->actualizar($idProducto, $datos);
        $productoRefrescado = $productoModel->obtenerPorId($idProducto);

        $this->responderJson([
            'success'     => $actualizado,
            'id_producto' => $idProducto,
            'producto'    => $productoRefrescado,
            'message'     => 'Producto actualizado con éxito.',
        ], 200);
    }

    /**
     * `action=eliminar_producto`: elimina o desactiva de forma segura un producto del catálogo (RF04).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function eliminarProducto(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawId      = $input['id_producto'] ?? ($input['id'] ?? ($input['codigo'] ?? 0));
        $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);

        if ($idProducto <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Identificador de producto no válido.',
            ], 400);
            return;
        }

        $productoModel = new Producto($this->pdo);
        if (!$productoModel->obtenerPorId($idProducto)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'El producto especificado no existe.',
            ], 404);
            return;
        }

        $eliminado = $productoModel->eliminar($idProducto);
        $total     = count($productoModel->obtenerTodos());

        $this->responderJson([
            'success'         => $eliminado,
            'id_producto'     => $idProducto,
            'total_productos' => $total,
            'message'         => 'Producto eliminado del catálogo correctamente.',
        ], 200);
    }

    /**
     * `action=crear_categoria`: registra una nueva categoría en `categorias` (RF04).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function crearCategoria(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $nombreCategoria = trim((string)($input['nombre_categoria'] ?? ($input['nombre'] ?? '')));
        if ($nombreCategoria === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'El nombre de la categoría es obligatorio.',
            ], 400);
            return;
        }

        $categoriaModel = new Categoria($this->pdo);
        $idCategoria    = $categoriaModel->crear($nombreCategoria);
        $categoria      = $categoriaModel->obtenerPorId($idCategoria);

        $this->responderJson([
            'success'      => true,
            'id_categoria' => $idCategoria,
            'categoria'    => $categoria,
            'categorias'   => $categoriaModel->obtenerTodas(),
            'message'      => 'Categoría creada con éxito.',
        ], 200);
    }

    /**
     * `action=actualizar_categoria`: actualiza el nombre de una categoría existente.
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function actualizarCategoria(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idCategoria     = isset($input['id_categoria']) ? (int)$input['id_categoria'] : 0;
        $nombreCategoria = trim((string)($input['nombre_categoria'] ?? ($input['nombre'] ?? '')));

        if ($idCategoria <= 0 || $nombreCategoria === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar el identificador y el nuevo nombre de la categoría.',
            ], 400);
            return;
        }

        $categoriaModel = new Categoria($this->pdo);
        if (!$categoriaModel->obtenerPorId($idCategoria)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La categoría indicada no existe.',
            ], 404);
            return;
        }

        $actualizado = $categoriaModel->actualizar($idCategoria, $nombreCategoria);

        $this->responderJson([
            'success'      => $actualizado,
            'id_categoria' => $idCategoria,
            'categoria'    => $categoriaModel->obtenerPorId($idCategoria),
            'message'      => 'Categoría actualizada con éxito.',
        ], 200);
    }

    /**
     * `action=eliminar_categoria`: elimina una categoría sin productos vinculados.
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function eliminarCategoria(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idCategoria = isset($input['id_categoria']) ? (int)$input['id_categoria'] : 0;
        if ($idCategoria <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Identificador de categoría no válido.',
            ], 400);
            return;
        }

        $categoriaModel = new Categoria($this->pdo);
        if (!$categoriaModel->obtenerPorId($idCategoria)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La categoría solicitada no existe.',
            ], 404);
            return;
        }

        $eliminado = $categoriaModel->eliminar($idCategoria);

        $this->responderJson([
            'success'      => $eliminado,
            'id_categoria' => $idCategoria,
            'categorias'   => $categoriaModel->obtenerTodas(),
            'message'      => 'Categoría eliminada correctamente.',
        ], 200);
    }

    /**
     * `action=crear_mesa`: limpia prefijos textuales (ej. `"Mesa 9"`), valida unicidad
     * mediante `Mesa::crear` y retorna la mesa creada junto con el nuevo total de mesas
     * y la capacidad máxima del salón (RF05).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function crearMesa(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawNumero  = trim((string)($input['numero_mesa'] ?? ($input['nombre_mesa'] ?? '')));
        $soloDigito = preg_replace('/\D+/', '', $rawNumero);
        $numeroMesa = ($soloDigito !== null && $soloDigito !== '') ? (int)$soloDigito : 0;

        if ($numeroMesa <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar un número de mesa válido (ej. 9 o "Mesa 9").',
            ], 400);
            return;
        }

        $capacidad  = isset($input['capacidad']) && (int)$input['capacidad'] > 0 ? (int)$input['capacidad'] : 4;
        $estadoMesa = trim((string)($input['estado_mesa'] ?? ($input['estado'] ?? 'Disponible'))) ?: 'Disponible';

        $mesaModel   = new Mesa($this->pdo);
        $idNuevaMesa = $mesaModel->crear($numeroMesa, $capacidad, $estadoMesa);
        $mesaCreada  = $mesaModel->obtenerPorId($idNuevaMesa);
        $resumen     = $this->calcularResumenMesas($mesaModel);

        $this->responderJson([
            'success'          => true,
            'id_mesa'          => $idNuevaMesa,
            'numero_mesa'      => $mesaCreada ? $mesaCreada['numero_mesa'] : $numeroMesa,
            'capacidad'        => $mesaCreada ? $mesaCreada['capacidad'] : $capacidad,
            'estado_mesa'      => $mesaCreada ? $mesaCreada['estado_mesa'] : $mesaModel->normalizarEstado($estadoMesa),
            'mesa'             => $mesaCreada,
            'total_mesas'      => $resumen['total_mesas'],
            'capacidad_maxima' => $resumen['capacidad_maxima'],
            'message'          => 'Mesa registrada con éxito.',
        ], 200);
    }

    /**
     * `action=actualizar_mesa`: actualiza número, capacidad y estado de una mesa existente (RF05).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function actualizarMesa(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idReferencia = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['numero_mesa'] ?? null));
        if ($idReferencia === null || trim((string)$idReferencia) === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar el identificador de la mesa a actualizar.',
            ], 400);
            return;
        }

        $mesaModel  = new Mesa($this->pdo);
        $mesaActual = $mesaModel->obtenerPorId($idReferencia);
        if (!$mesaActual) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La mesa que intenta actualizar no existe.',
            ], 404);
            return;
        }

        $idMesaReal = $mesaActual['id_mesa'];

        if (isset($input['numero_mesa']) && trim((string)$input['numero_mesa']) !== '') {
            $digitos    = preg_replace('/\D+/', '', (string)$input['numero_mesa']);
            $numeroMesa = ($digitos !== null && $digitos !== '') ? (int)$digitos : $mesaActual['numero_mesa'];
        } else {
            $numeroMesa = $mesaActual['numero_mesa'];
        }

        $capacidad = isset($input['capacidad']) && (int)$input['capacidad'] > 0
            ? (int)$input['capacidad']
            : $mesaActual['capacidad'];

        $estadoMesa = isset($input['estado_mesa']) || isset($input['estado'])
            ? trim((string)($input['estado_mesa'] ?? $input['estado']))
            : $mesaActual['estado_mesa'];

        $actualizado     = $mesaModel->actualizar($idMesaReal, $numeroMesa, $capacidad, $estadoMesa);
        $mesaActualizada = $mesaModel->obtenerPorId($idMesaReal);
        $resumen         = $this->calcularResumenMesas($mesaModel);

        $this->responderJson([
            'success'          => $actualizado,
            'id_mesa'          => $idMesaReal,
            'estado_mesa'      => $mesaActualizada ? $mesaActualizada['estado_mesa'] : $mesaModel->normalizarEstado($estadoMesa),
            'mesa'             => $mesaActualizada,
            'total_mesas'      => $resumen['total_mesas'],
            'capacidad_maxima' => $resumen['capacidad_maxima'],
            'message'          => 'Datos de la mesa actualizados correctamente.',
        ], 200);
    }

    /**
     * `action=eliminar_mesa`: elimina una mesa sin órdenes activas y recalcula totales del salón (RF05).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function eliminarMesa(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawId  = $input['id_mesa'] ?? ($input['id'] ?? 0);
        $mesaModel = new Mesa($this->pdo);
        $mesaActual = $mesaModel->obtenerPorId($rawId);

        if (!$mesaActual) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La mesa especificada no existe.',
            ], 404);
            return;
        }

        $eliminado = $mesaModel->eliminar($mesaActual['id_mesa']);
        $resumen   = $this->calcularResumenMesas($mesaModel);

        $this->responderJson([
            'success'          => $eliminado,
            'id_mesa'          => $mesaActual['id_mesa'],
            'total_mesas'      => $resumen['total_mesas'],
            'capacidad_maxima' => $resumen['capacidad_maxima'],
            'message'          => 'Mesa eliminada del salón correctamente.',
        ], 200);
    }

    /**
     * `action=ajustar_inventario`: valida que la cantidad no sea negativa y actualiza
     * `cantidad_estimada` en `inventarios` (RF14).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function ajustarInventario(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawId         = $input['id_ingrediente'] ?? ($input['codigo_ingrediente'] ?? ($input['codigo'] ?? ($input['id'] ?? 0)));
        $idIngrediente = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);

        if ($idIngrediente <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe especificar el identificador del ingrediente a ajustar.',
            ], 400);
            return;
        }

        $rawCantidad = $input['cantidad_estimada'] ?? ($input['cantidad'] ?? null);
        if ($rawCantidad === null || $rawCantidad === '' || !is_numeric($rawCantidad)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe ingresar una cantidad numérica válida para el inventario.',
            ], 400);
            return;
        }

        $cantidad = round((float)$rawCantidad, 2);
        if ($cantidad < 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La cantidad en inventario no puede ser negativa.',
            ], 400);
            return;
        }

        $inventarioModel = new Inventario($this->pdo);
        if (!$inventarioModel->obtenerPorId($idIngrediente)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'El ingrediente indicado no existe en el inventario.',
            ], 404);
            return;
        }

        $actualizado = $inventarioModel->ajustarCantidad($idIngrediente, $cantidad);
        $ingrediente = $inventarioModel->obtenerPorId($idIngrediente);

        $this->responderJson([
            'success'           => $actualizado,
            'id_ingrediente'    => $idIngrediente,
            'cantidad_estimada' => $ingrediente ? $ingrediente['cantidad_estimada'] : $cantidad,
            'ingrediente'       => $ingrediente,
            'message'           => 'Stock del ingrediente ajustado correctamente.',
        ], 200);
    }

    /**
     * `action=crear_usuario`: registra un nuevo empleado cifrando su clave con `password_hash()`
     * y devuelve la cuenta creada con `id_usuario` real y `nombre_rol` (RF13, RNF06).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function crearUsuario(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $nombreCompleto = trim((string)($input['nombre_completo'] ?? ($input['nombre'] ?? '')));
        $nombreUsuario  = trim((string)($input['nombre_usuario'] ?? ($input['usuario'] ?? '')));
        $password       = (string)($input['password'] ?? ($input['contrasena'] ?? ''));
        $rol            = $input['rol'] ?? ($input['id_rol'] ?? ($input['nombre_rol'] ?? 'Mesero'));

        if ($nombreCompleto === '' || $nombreUsuario === '' || $password === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Nombre completo, nombre de usuario y contraseña son obligatorios.',
            ], 400);
            return;
        }

        $usuarioModel = new Usuario($this->pdo);
        $idUsuario    = $usuarioModel->crear($nombreCompleto, $nombreUsuario, $password, $rol);
        $nuevoUsuario = $usuarioModel->obtenerPorId($idUsuario);
        $total        = count($usuarioModel->obtenerTodos());

        $this->responderJson([
            'success'        => true,
            'id_usuario'     => $idUsuario,
            'usuario'        => $nuevoUsuario,
            'total_usuarios' => $total,
            'message'        => 'Cuenta de empleado registrada exitosamente.',
        ], 200);
    }

    /**
     * `action=actualizar_usuario`: actualiza los datos y rol (y opcionalmente contraseña)
     * de un empleado existente (RF13, RNF06).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function actualizarUsuario(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawId     = $input['id_usuario'] ?? ($input['id'] ?? 0);
        $idUsuario = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);

        if ($idUsuario <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar el identificador del empleado a actualizar.',
            ], 400);
            return;
        }

        $usuarioModel = new Usuario($this->pdo);
        if (!$usuarioModel->obtenerPorId($idUsuario)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La cuenta de empleado indicada no existe.',
            ], 404);
            return;
        }

        $actualizado        = $usuarioModel->actualizar($idUsuario, $input);
        $usuarioActualizado = $usuarioModel->obtenerPorId($idUsuario);

        $this->responderJson([
            'success'    => $actualizado,
            'id_usuario' => $idUsuario,
            'usuario'    => $usuarioActualizado,
            'message'    => 'Datos del empleado actualizados correctamente.',
        ], 200);
    }

    /**
     * `action=eliminar_usuario`: elimina la cuenta de un empleado impidiendo que el
     * administrador autenticado elimine su propia sesión en curso (RF13).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function eliminarUsuario(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $rawId     = $input['id_usuario'] ?? ($input['id'] ?? 0);
        $idUsuario = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);

        if ($idUsuario <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Identificador de empleado no válido.',
            ], 400);
            return;
        }

        if (isset($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] === $idUsuario) {
            $this->responderJson([
                'success' => false,
                'error'   => 'No puede eliminar su propia cuenta con una sesión activa en curso.',
            ], 400);
            return;
        }

        $usuarioModel = new Usuario($this->pdo);
        if (!$usuarioModel->obtenerPorId($idUsuario)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La cuenta de empleado solicitada no existe.',
            ], 404);
            return;
        }

        $eliminado = $usuarioModel->eliminar($idUsuario);
        $total     = count($usuarioModel->obtenerTodos());

        $this->responderJson([
            'success'        => $eliminado,
            'id_usuario'     => $idUsuario,
            'total_usuarios' => $total,
            'message'        => 'Cuenta de empleado eliminada exitosamente.',
        ], 200);
    }

    /**
     * `action=filtrar_ventas`: consulta KPIs de ventas (`cantidad_ventas`, `total_vendido`)
     * y los 5 productos más vendidos filtrados opcionalmente por `fecha_inicio` y `fecha_fin` (RF11, RF12).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function filtrarVentas(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $fechaInicio = $this->normalizarFechaFiltro($input['fecha_inicio'] ?? null);
        $fechaFin    = $this->normalizarFechaFiltro($input['fecha_fin'] ?? null);
        $limite      = isset($input['limite']) && (int)$input['limite'] > 0 ? (int)$input['limite'] : 5;

        if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio > $fechaFin) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        $pedidoModel          = new Pedido($this->pdo);
        $estadisticas         = $pedidoModel->obtenerEstadisticasVentas($fechaInicio, $fechaFin);
        $productosMasVendidos = $pedidoModel->obtenerProductosMasVendidos($limite, $fechaInicio, $fechaFin);

        $this->responderJson([
            'success'                => true,
            'fecha_inicio'           => $fechaInicio,
            'fecha_fin'              => $fechaFin,
            'estadisticas'           => $estadisticas,
            'productos_mas_vendidos' => $productosMasVendidos,
            'kpis'                   => $estadisticas,
            'top_productos'          => $productosMasVendidos,
        ], 200);
    }

    /**
     * Prepara los datos de un producto, procesando si corresponde una imagen subida
     * por `multipart/form-data` hacia `public/img/` y decodificando `recetas` en JSON.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function prepararDatosProducto(array $input): array
    {
        $datos = $input;

        if (isset($datos['recetas']) && is_string($datos['recetas']) && trim($datos['recetas']) !== '') {
            $decodificado = json_decode($datos['recetas'], true);
            if (is_array($decodificado)) {
                $datos['recetas'] = $decodificado;
            }
        }

        $archivoSubido = null;
        if (!empty($_FILES['fotografia']) && is_array($_FILES['fotografia']) && (int)($_FILES['fotografia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $archivoSubido = $_FILES['fotografia'];
        } elseif (!empty($_FILES['imagen']) && is_array($_FILES['imagen']) && (int)($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $archivoSubido = $_FILES['imagen'];
        }

        if ($archivoSubido !== null) {
            $codigoError = (int)($archivoSubido['error'] ?? UPLOAD_ERR_OK);
            if ($codigoError !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Error al recibir el archivo de imagen del producto.');
            }

            $nombreOriginal = (string)($archivoSubido['name'] ?? '');
            $extension      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $permitidas     = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($extension, $permitidas, true)) {
                throw new InvalidArgumentException('Formato de imagen no permitido. Use JPG, PNG, WEBP o GIF.');
            }

            $dirDestino = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'img';
            if (!is_dir($dirDestino) && !mkdir($dirDestino, 0775, true) && !is_dir($dirDestino)) {
                throw new RuntimeException('No se pudo preparar el directorio public/img/ para guardar la fotografía.');
            }

            $baseNombre    = preg_replace('/[^a-z0-9_-]+/i', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME)) ?: 'producto';
            $nombreDestino = strtolower(substr($baseNombre, 0, 40)) . '_' . uniqid() . '.' . $extension;
            $rutaCompleta  = $dirDestino . DIRECTORY_SEPARATOR . $nombreDestino;
            $tmpPath       = (string)($archivoSubido['tmp_name'] ?? '');

            if ($tmpPath === '' || !move_uploaded_file($tmpPath, $rutaCompleta)) {
                throw new RuntimeException('No fue posible almacenar la imagen subida en public/img/.');
            }

            $datos['fotografia'] = $nombreDestino;
        }

        return $datos;
    }

    /**
     * Calcula el total actual de mesas y la capacidad máxima combinada del salón.
     *
     * @param Mesa $mesaModel
     * @return array{total_mesas: int, capacidad_maxima: int}
     */
    private function calcularResumenMesas(Mesa $mesaModel): array
    {
        $mesas           = $mesaModel->obtenerTodas();
        $totalMesas      = count($mesas);
        $capacidadMaxima = !empty($mesas) ? (int)array_sum(array_column($mesas, 'capacidad')) : 0;

        return [
            'total_mesas'      => $totalMesas,
            'capacidad_maxima' => $capacidadMaxima,
        ];
    }

    /**
     * Normaliza fechas recibidas en `YYYY-MM-DD` o `DD/MM/YYYY` validando que sean fechas reales.
     *
     * @param mixed $valor
     * @return string|null
     */
    private function normalizarFechaFiltro($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string)$valor);
        if ($texto === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $m)) {
            if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                throw new InvalidArgumentException("La fecha '{$texto}' no es válida.");
            }
            return $texto;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $texto, $m)) {
            if (!checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
                throw new InvalidArgumentException("La fecha '{$texto}' no es válida.");
            }
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        throw new InvalidArgumentException("Formato de fecha no válido ('{$texto}'). Use YYYY-MM-DD.");
    }

    /**
     * Lee datos híbridos (`php://input` JSON, `$_POST` y `$_GET`) como respaldo
     * cuando un método público se invoca directamente sin parámetros.
     *
     * @return array<string, mixed>
     */
    private function leerEntradaActual(): array
    {
        $rawBody  = file_get_contents('php://input');
        $jsonBody = (is_string($rawBody) && trim($rawBody) !== '') ? json_decode($rawBody, true) : null;
        return is_array($jsonBody) ? array_merge($_GET, $_POST, $jsonBody) : array_merge($_GET, $_POST);
    }

    /**
     * Emite respuesta JSON con código HTTP.
     *
     * @param array<string, mixed> $payload
     * @param int $statusCode
     * @return void
     */
    private function responderJson(array $payload, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}

if (!defined('NIXGARU_NO_AUTORUN')) {
    $rawBody   = file_get_contents('php://input');
    $jsonBody  = (is_string($rawBody) && trim($rawBody) !== '') ? json_decode($rawBody, true) : null;
    $inputData = is_array($jsonBody) ? array_merge($_GET, $_POST, $jsonBody) : array_merge($_GET, $_POST);
    $accionReq = trim((string)($_GET['action'] ?? ($_POST['action'] ?? ($inputData['action'] ?? ''))));

    $adminController = new AdminController($pdo);
    $adminController->manejarPeticion($accionReq, $inputData);
}