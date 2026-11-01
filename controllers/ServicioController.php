<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Controlador: ServicioController (`controllers/ServicioController.php`)
 * Descripción: Orquesta el flujo operativo híbrido de salón y pedidos web:
 *              registro/actualización de comandas en mesa, recepción de órdenes
 *              web, consulta en tiempo real de cuentas activas, gestión de
 *              estados de mesa/pedido, asignación de mesas a retiros web y
 *              cierre financiero con descuento automático de inventario
 *              (RF06, RF07, RF08, RF09, RF10, RF14, RF15, RF16, RF17).
 * ==============================================================================
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Mesa.php';

class ServicioController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enruta la petición entrante según `action`.
     *
     * @param string $action
     * @param array<string, mixed> $input
     * @return void
     */
    public function manejarPeticion(string $action, array $input): void
    {
        try {
            switch ($action) {
                case 'guardar_orden':
                    $this->guardarOrden($input);
                    break;

                case 'actualizar_orden':
                    $this->actualizarOrden($input);
                    break;

                case 'obtener_cuenta_mesa':
                    $this->obtenerCuentaMesa($input);
                    break;

                case 'cambiar_estado_mesa':
                    $this->cambiarEstadoMesa($input);
                    break;

                case 'cerrar_cuenta':
                    $this->cerrarCuenta($input);
                    break;

                case 'obtener_pedidos_web':
                    $this->obtenerPedidosWeb($input);
                    break;

                case 'asignar_mesa_web':
                    $this->asignarMesaWeb($input);
                    break;

                case 'cambiar_estado_pedido':
                    $this->cambiarEstadoPedido($input);
                    break;

                default:
                    $this->responderJson([
                        'success' => false,
                        'error'   => 'Acción operativa no válida o no especificada.',
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
            error_log('[NIXGARU][ServicioController][PDOException] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error de base de datos en la operación de servicio.'], 500);
        } catch (Throwable $e) {
            error_log('[NIXGARU][ServicioController][Throwable] ' . $e->getMessage());
            $this->responderJson(['success' => false, 'error' => 'Error interno en el controlador de servicio.'], 500);
        }
    }

    /**
     * `action=guardar_orden` (`POST`): soporta el modelo operativo híbrido:
     * - Flujo 1: Pedido Web del Cliente (RF15) cuando `tipo_orden` es web o vienen datos de cliente sin `id_mesa`.
     * - Flujo 2: Orden Presencial o Modificación de Cuenta en Mesa (RF06, RF07, RF09).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function guardarOrden(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $productos = isset($input['productos']) && is_array($input['productos']) ? $input['productos'] : [];
        if (empty($productos)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'No hay productos válidos en la orden enviada.',
            ], 400);
            return;
        }

        $pedidoModel = new Pedido($this->pdo);

        $datosCliente = isset($input['cliente']) && is_array($input['cliente'])
            ? array_merge($input, $input['cliente'])
            : $input;

        $tipoOrdenRaw = mb_strtolower(trim((string)($datosCliente['tipo_orden'] ?? '')), 'UTF-8');
        $esTipoWeb    = in_array(
            $tipoOrdenRaw,
            ['envío a domicilio', 'envio a domicilio', 'domicilio', 'retiro en el local', 'retiro'],
            true
        );

        $idMesa  = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['mesa'] ?? null));
        $sinMesa = ($idMesa === null || $idMesa === '' || $idMesa === 0 || $idMesa === '0');

        $tieneDatosCliente = !empty($datosCliente['nombre_cliente'])
            || !empty($datosCliente['telefono_cliente'])
            || !empty($datosCliente['correo_cliente']);

        // Flujo 1: Pedido Web del Cliente (RF15)
        if ($esTipoWeb || ($tieneDatosCliente && $sinMesa)) {
            $resultado = $pedidoModel->crearPedidoWeb($datosCliente, $productos);

            $this->responderJson([
                'success'   => true,
                'id_pedido' => $resultado['id_pedido'],
                'pedido'    => $resultado,
                'message'   => 'Pedido web registrado con éxito.',
            ], 200);
            return;
        }

        // Flujo 2: Orden Presencial o Modificación de Cuenta en Mesa (RF06, RF07, RF09)
        if ($sinMesa) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Identificador de mesa no válido o vacío.',
            ], 400);
            return;
        }

        $idUsuario = isset($_SESSION['id_usuario'])
            ? (int)$_SESSION['id_usuario']
            : (isset($input['id_usuario']) ? (int)$input['id_usuario'] : null);

        $estadoPedido    = trim((string)($input['estado_pedido'] ?? 'En preparación')) ?: 'En preparación';
        $estadoMesa      = trim((string)($input['estado_mesa'] ?? 'En proceso')) ?: 'En proceso';
        $reemplazarItems = !empty($input['reemplazar_items']);

        $resultado = $pedidoModel->guardarOrdenMesa(
            $idMesa,
            $productos,
            $idUsuario,
            $estadoPedido,
            $estadoMesa,
            $reemplazarItems
        );

        $this->responderJson([
            'success'   => true,
            'id_pedido' => $resultado['id_pedido'],
            'id_mesa'   => $resultado['id_mesa'],
            'orden'     => $resultado,
            'message'   => 'Orden guardada y enviada a cocina con éxito.',
        ], 200);
    }

    /**
     * `action=actualizar_orden` (`POST`): actualiza el detalle completo de la cuenta activa
     * de una mesa (`reemplazarItems = true`), recalculando `subtotal`, propina (10%),
     * `total_pagar` y `saldo_pendiente` descontando cualquier `monto_abonado` previo (RF07, RF09).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function actualizarOrden(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idMesa    = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['mesa'] ?? null));
        $productos = isset($input['productos']) && is_array($input['productos']) ? $input['productos'] : [];

        if ($idMesa === null || $idMesa === '' || $idMesa === 0 || $idMesa === '0') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe especificar la mesa cuya orden desea actualizar.',
            ], 400);
            return;
        }

        if (empty($productos)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La orden actualizada debe contener al menos un producto.',
            ], 400);
            return;
        }

        $idUsuario = isset($_SESSION['id_usuario'])
            ? (int)$_SESSION['id_usuario']
            : (isset($input['id_usuario']) ? (int)$input['id_usuario'] : null);

        $estadoPedido = trim((string)($input['estado_pedido'] ?? 'En preparación')) ?: 'En preparación';
        $estadoMesa   = trim((string)($input['estado_mesa'] ?? 'En proceso')) ?: 'En proceso';

        $pedidoModel = new Pedido($this->pdo);
        $resultado   = $pedidoModel->guardarOrdenMesa(
            $idMesa,
            $productos,
            $idUsuario,
            $estadoPedido,
            $estadoMesa,
            true
        );

        $this->responderJson([
            'success'   => true,
            'id_pedido' => $resultado['id_pedido'],
            'id_mesa'   => $resultado['id_mesa'],
            'orden'     => $resultado,
            'message'   => 'Orden de la mesa actualizada correctamente.',
        ], 200);
    }

    /**
     * `action=obtener_cuenta_mesa` (`GET`): consulta en tiempo real la cuenta activa
     * de una mesa desde MySQL calculando `subtotal`, `propina`, `total`, `monto_abonado`
     * y `saldo_pendiente` (RF06, RF07, RF09).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function obtenerCuentaMesa(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idMesa = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['mesa'] ?? null));
        if ($idMesa === null || trim((string)$idMesa) === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar el identificador o número de mesa.',
            ], 400);
            return;
        }

        $mesaModel = new Mesa($this->pdo);
        $mesaInfo  = $mesaModel->obtenerPorId($idMesa);
        if (!$mesaInfo) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La mesa solicitada no existe.',
            ], 404);
            return;
        }

        $pedidoModel = new Pedido($this->pdo);
        $detalles    = $pedidoModel->obtenerCuentaActivaPorMesa($mesaInfo['id_mesa']);

        $subtotal          = 0.00;
        $porcentajePropina = 10.00;
        $idPedido          = 0;
        $estadoPedido      = null;
        $tipoOrden         = 'Consumo en local';
        $fechaHora         = null;
        $atendidoPor       = null;
        $montoAbonado      = 0.00;

        foreach ($detalles as $item) {
            $subtotal += (float)$item['subtotal'];
            if ($idPedido === 0 && !empty($item['id_pedido'])) {
                $idPedido          = (int)$item['id_pedido'];
                $estadoPedido      = (string)$item['estado_pedido'];
                $tipoOrden         = (string)($item['tipo_orden'] ?? 'Consumo en local');
                $fechaHora         = (string)$item['fecha_hora'];
                $atendidoPor       = (string)($item['nombre_completo'] ?? 'Personal de servicio');
                $porcentajePropina = isset($item['porcentaje_propina']) && (float)$item['porcentaje_propina'] > 0
                    ? (float)$item['porcentaje_propina']
                    : 10.00;
                $montoAbonado      = round((float)($item['monto_abonado'] ?? 0.00), 2);
            }
        }

        $subtotal       = round($subtotal, 2);
        $propina        = round($subtotal * ($porcentajePropina / 100), 2);
        $total          = round($subtotal + $propina, 2);
        $saldoPendiente = max(0.00, round($total - $montoAbonado, 2));

        $this->responderJson([
            'success'         => true,
            'mesa'            => $mesaInfo,
            'id_pedido'       => $idPedido > 0 ? $idPedido : null,
            'estado_pedido'   => $estadoPedido,
            'tipo_orden'      => $tipoOrden,
            'fecha_hora'      => $fechaHora,
            'atendido_por'    => $atendidoPor,
            'detalles'        => $detalles,
            'subtotal'        => $subtotal,
            'propina'         => $propina,
            'total'           => $total,
            'total_pagar'     => $total,
            'monto_abonado'   => $montoAbonado,
            'saldo_pendiente' => $saldoPendiente,
        ], 200);
    }

    /**
     * `action=cambiar_estado_mesa` (`POST`): cambia el estado operativo de una mesa (RF08).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function cambiarEstadoMesa(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idMesa     = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['numero_mesa'] ?? null));
        $estadoMesa = trim((string)($input['estado_mesa'] ?? ($input['estado'] ?? '')));

        if ($idMesa === null || trim((string)$idMesa) === '' || $estadoMesa === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe indicar la mesa y el nuevo estado operativo.',
            ], 400);
            return;
        }

        $mesaModel = new Mesa($this->pdo);
        $mesaInfo  = $mesaModel->obtenerPorId($idMesa);
        if (!$mesaInfo) {
            $this->responderJson([
                'success' => false,
                'error'   => 'La mesa indicada no existe.',
            ], 404);
            return;
        }

        $actualizado     = $mesaModel->actualizarEstado($mesaInfo['id_mesa'], $estadoMesa);
        $mesaActualizada = $mesaModel->obtenerPorId($mesaInfo['id_mesa']);

        $this->responderJson([
            'success'     => $actualizado,
            'id_mesa'     => $mesaInfo['id_mesa'],
            'estado_mesa' => $mesaActualizada ? $mesaActualizada['estado_mesa'] : $mesaModel->normalizarEstado($estadoMesa),
            'mesa'        => $mesaActualizada,
        ], 200);
    }

    /**
     * `action=cerrar_cuenta` (`POST`): liquida `saldo_pendiente = 0.00`, marca
     * `estado_pedido = 'Pagado'`, descuenta automáticamente los insumos del inventario
     * según `recetas` (RF14) y libera la mesa dejándola en `'Disponible'` (RF10).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function cerrarCuenta(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idMesa   = $input['id_mesa'] ?? ($input['mesa_id'] ?? ($input['mesa'] ?? null));
        $idPedido = isset($input['id_pedido']) && $input['id_pedido'] !== '' && $input['id_pedido'] !== null
            ? (int)$input['id_pedido']
            : null;

        if (($idMesa === null || trim((string)$idMesa) === '' || $idMesa === 0) && ($idPedido === null || $idPedido <= 0)) {
            $this->responderJson([
                'success' => false,
                'error'   => 'No se especificó la mesa ni el pedido para procesar el cierre de cuenta.',
            ], 400);
            return;
        }

        $metodoRaw  = mb_strtolower(trim((string)($input['metodo_pago'] ?? 'Efectivo')), 'UTF-8');
        $metodoPago = str_contains($metodoRaw, 'tarjeta') ? 'Tarjeta' : 'Efectivo';

        $idCajero = isset($_SESSION['id_usuario'])
            ? (int)$_SESSION['id_usuario']
            : (int)($input['id_cajero'] ?? ($input['id_usuario'] ?? 1));

        $pedidoModel = new Pedido($this->pdo);
        $resultado   = $pedidoModel->cerrarCuenta($idMesa, $idPedido, $metodoPago, $idCajero);

        if (empty($resultado['id_pedido'])) {
            $this->responderJson([
                'success' => false,
                'error'   => 'No se encontró un pedido activo pendiente de cobro para la mesa indicada.',
            ], 404);
            return;
        }

        // Recalcular efectivo recibido y cambio si se envió `monto_recibido` desde caja
        $saldoCobrado = (float)($resultado['efectivo_recibido'] ?? 0.00);
        if (isset($input['monto_recibido']) && is_numeric($input['monto_recibido'])) {
            $montoRecibido = round((float)$input['monto_recibido'], 2);
            if ($montoRecibido >= $saldoCobrado) {
                $resultado['efectivo_recibido'] = $montoRecibido;
                $resultado['cambio']            = round($montoRecibido - $saldoCobrado, 2);
            }
        }

        $this->responderJson(array_merge([
            'success' => true,
            'message' => 'Cobro procesado con éxito y mesa liberada.',
            'cierre'  => $resultado,
        ], $resultado), 200);
    }

    /**
     * `action=obtener_pedidos_web` (`GET`): retorna los pedidos web filtrados opcionalmente
     * por `tipo_orden` y `estado` para la bandeja del Cajero (RF16).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function obtenerPedidosWeb(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $tipoOrden = isset($input['tipo_orden']) ? trim((string)$input['tipo_orden']) : null;
        $estado    = isset($input['estado'])
            ? trim((string)$input['estado'])
            : (isset($input['estado_pedido']) ? trim((string)$input['estado_pedido']) : null);

        $pedidoModel = new Pedido($this->pdo);
        $pedidos     = $pedidoModel->obtenerPedidosWeb($tipoOrden, $estado);

        $this->responderJson([
            'success' => true,
            'total'   => count($pedidos),
            'pedidos' => $pedidos,
        ], 200);
    }

    /**
     * `action=asignar_mesa_web` (`POST`): asigna una mesa física disponible a un pedido
     * web de retiro en local y cambia el estado de la mesa a `'En proceso'` (RF17).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function asignarMesaWeb(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idPedido = isset($input['id_pedido']) ? (int)$input['id_pedido'] : 0;
        $rawMesa  = $input['id_mesa'] ?? ($input['mesa_id'] ?? 0);
        $idMesa   = is_numeric($rawMesa)
            ? (int)$rawMesa
            : (int)preg_replace('/\D+/', '', (string)$rawMesa);

        if ($idPedido <= 0 || $idMesa <= 0) {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe proporcionar un pedido web y una mesa válidos para la asignación.',
            ], 400);
            return;
        }

        $idUsuario = isset($_SESSION['id_usuario'])
            ? (int)$_SESSION['id_usuario']
            : (isset($input['id_usuario']) ? (int)$input['id_usuario'] : null);

        $pedidoModel = new Pedido($this->pdo);
        $asignado    = $pedidoModel->asignarMesaPedidoWeb($idPedido, $idMesa, $idUsuario);

        $this->responderJson([
            'success'   => $asignado,
            'id_pedido' => $idPedido,
            'id_mesa'   => $idMesa,
            'message'   => 'Mesa asignada exitosamente al pedido web.',
        ], 200);
    }

    /**
     * `action=cambiar_estado_pedido` (`POST`): actualiza el estado operativo de un pedido (RF08, RF16).
     *
     * @param array<string, mixed> $input
     * @return void
     */
    public function cambiarEstadoPedido(array $input = []): void
    {
        if (empty($input)) {
            $input = $this->leerEntradaActual();
        }

        $idPedido     = isset($input['id_pedido']) ? (int)$input['id_pedido'] : 0;
        $estadoPedido = trim((string)($input['estado_pedido'] ?? ($input['estado'] ?? '')));

        if ($idPedido <= 0 || $estadoPedido === '') {
            $this->responderJson([
                'success' => false,
                'error'   => 'Debe especificar el identificador del pedido y el nuevo estado.',
            ], 400);
            return;
        }

        $idUsuario = isset($_SESSION['id_usuario'])
            ? (int)$_SESSION['id_usuario']
            : (isset($input['id_usuario']) ? (int)$input['id_usuario'] : null);

        $pedidoModel = new Pedido($this->pdo);
        $actualizado = $pedidoModel->actualizarEstadoPedido($idPedido, $estadoPedido, $idUsuario);

        $this->responderJson([
            'success'       => $actualizado,
            'id_pedido'     => $idPedido,
            'estado_pedido' => $estadoPedido,
            'message'       => 'Estado del pedido actualizado correctamente.',
        ], 200);
    }

    /**
     * Lee datos híbridos (`php://input` JSON, `$_POST` y `$_GET`) como respaldo
     * cuando un método público se invoca directamente sin pasar `$input`.
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

    $servicioController = new ServicioController($pdo);
    $servicioController->manejarPeticion($accionReq, $inputData);
}