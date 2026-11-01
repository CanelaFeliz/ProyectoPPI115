<?php
/**
 * ==============================================================================
 * NIXGARU – Sistema de Gestión de Cafetería (MVC)
 * Modelo: Pedido (`models/Pedido.php`)
 * Tablas: `pedidos`, `detalle_pedidos`, `productos`, `mesas`, `usuarios`, `recetas`, `inventarios`
 * ==============================================================================
 */

require_once __DIR__ . '/Mesa.php';
require_once __DIR__ . '/Inventario.php';

class Pedido
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna un arreglo asociativo `[id_mesa => float_subtotal]` sumando `dp.subtotal`
     * de las órdenes activas en salón para pintar el consumo en `mesas.php`.
     *
     * @return array<int, float>
     */
    public function obtenerConsumosActivosPorMesa(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_mesa,
                COALESCE(SUM(dp.subtotal), 0.00) AS consumo_subtotal
            FROM pedidos p
            INNER JOIN detalle_pedidos dp ON p.id_pedido = dp.id_pedido
            WHERE p.id_mesa IS NOT NULL
              AND LOWER(TRIM(p.estado_pedido)) NOT IN ('pagado', 'finalizado', 'cancelado', 'entregado')
            GROUP BY p.id_mesa
        ");
        $stmt->execute();

        $consumos = [];
        while ($row = $stmt->fetch()) {
            $consumos[(int)$row['id_mesa']] = round((float)$row['consumo_subtotal'], 2);
        }

        return $consumos;
    }

    /**
     * Resuelve el `id_mesa` real y retorna las líneas de productos de la orden activa
     * en esa mesa junto con los metadatos financieros y de atención (RF07, RF09, RF10).
     *
     * @param int|string $idMesa
     * @return array<int, array<string, mixed>>
     */
    public function obtenerCuentaActivaPorMesa($idMesa): array
    {
        $mesaModel = new Mesa($this->pdo);
        $mesaInfo  = $mesaModel->obtenerPorId($idMesa);
        if (!$mesaInfo) {
            return [];
        }

        $idMesaReal = $mesaInfo['id_mesa'];

        // Localizar el pedido activo más reciente asignado a la mesa física
        $stmtPed = $this->pdo->prepare("
            SELECT id_pedido
            FROM pedidos
            WHERE id_mesa = :id_mesa
              AND LOWER(TRIM(estado_pedido)) NOT IN ('pagado', 'finalizado', 'cancelado', 'entregado')
            ORDER BY id_pedido DESC
            LIMIT 1
        ");
        $stmtPed->bindValue(':id_mesa', $idMesaReal, PDO::PARAM_INT);
        $stmtPed->execute();
        $idPedidoActivo = (int)($stmtPed->fetchColumn() ?: 0);

        if ($idPedidoActivo <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                p.id_pedido,
                p.fecha_hora,
                p.estado_pedido,
                p.porcentaje_propina,
                p.total_pagar,
                p.metodo_pago,
                p.id_mesa,
                m.numero_mesa,
                p.id_usuario,
                COALESCE(u.nombre_completo, 'Personal de servicio') AS nombre_completo,
                COALESCE(p.tipo_orden, 'Consumo en local') AS tipo_orden,
                COALESCE(p.monto_abonado, 0.00) AS monto_abonado,
                COALESCE(p.saldo_pendiente, p.total_pagar) AS saldo_pendiente,
                dp.id_detalle,
                dp.id_producto,
                pr.nombre_producto,
                pr.precio,
                dp.cantidad,
                dp.subtotal
            FROM pedidos p
            INNER JOIN detalle_pedidos dp ON p.id_pedido = dp.id_pedido
            INNER JOIN productos pr ON dp.id_producto = pr.id_producto
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            WHERE p.id_pedido = :id_pedido
            ORDER BY dp.id_detalle ASC
        ");
        $stmt->bindValue(':id_pedido', $idPedidoActivo, PDO::PARAM_INT);
        $stmt->execute();

        $filas = $stmt->fetchAll();
        foreach ($filas as &$fila) {
            $fila['id_pedido']          = (int)$fila['id_pedido'];
            $fila['porcentaje_propina'] = round((float)$fila['porcentaje_propina'], 2);
            $fila['total_pagar']        = round((float)$fila['total_pagar'], 2);
            $fila['id_mesa']            = $fila['id_mesa'] !== null ? (int)$fila['id_mesa'] : null;
            $fila['numero_mesa']        = $fila['numero_mesa'] !== null ? (int)$fila['numero_mesa'] : $mesaInfo['numero_mesa'];
            $fila['id_usuario']         = $fila['id_usuario'] !== null ? (int)$fila['id_usuario'] : null;
            $fila['monto_abonado']      = round((float)$fila['monto_abonado'], 2);
            $fila['saldo_pendiente']    = round((float)$fila['saldo_pendiente'], 2);
            $fila['id_detalle']         = (int)$fila['id_detalle'];
            $fila['id_producto']        = (int)$fila['id_producto'];
            $fila['precio']             = round((float)$fila['precio'], 2);
            $fila['cantidad']           = (int)$fila['cantidad'];
            $fila['subtotal']           = round((float)$fila['subtotal'], 2);
        }
        unset($fila);

        return $filas;
    }

    /**
     * Calcula los KPIs de ventas sobre órdenes pagadas o finalizadas, aplicando
     * opcionalmente un rango de fechas (RF11).
     *
     * @param string|null $fechaInicio Formato YYYY-MM-DD
     * @param string|null $fechaFin Formato YYYY-MM-DD
     * @return array{cantidad_ventas: int, total_vendido: float}
     */
    public function obtenerEstadisticasVentas(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $inicio = $fechaInicio !== null ? trim($fechaInicio) : '';
        $fin    = $fechaFin !== null ? trim($fechaFin) : '';

        $sql = "
            SELECT
                COUNT(id_pedido) AS cantidad_ventas,
                COALESCE(SUM(total_pagar), 0.00) AS total_vendido
            FROM pedidos
            WHERE LOWER(TRIM(estado_pedido)) IN ('pagado', 'finalizado')
        ";

        if ($inicio !== '' && $fin !== '') {
            $sql .= " AND DATE(fecha_hora) BETWEEN :inicio AND :fin";
        } elseif ($inicio !== '') {
            $sql .= " AND DATE(fecha_hora) >= :inicio";
        } elseif ($fin !== '') {
            $sql .= " AND DATE(fecha_hora) <= :fin";
        }

        $stmt = $this->pdo->prepare($sql);
        if ($inicio !== '') {
            $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        }
        if ($fin !== '') {
            $stmt->bindValue(':fin', $fin, PDO::PARAM_STR);
        }
        $stmt->execute();

        $stats = $stmt->fetch() ?: [];

        return [
            'cantidad_ventas' => (int)($stats['cantidad_ventas'] ?? 0),
            'total_vendido'   => round((float)($stats['total_vendido'] ?? 0.00), 2),
        ];
    }

    /**
     * Agrupa las ventas de órdenes pagadas/finalizadas por producto y retorna el ranking
     * de mayor demanda (RF12).
     *
     * @param int $limite
     * @param string|null $fechaInicio Formato YYYY-MM-DD
     * @param string|null $fechaFin Formato YYYY-MM-DD
     * @return array<int, array{id_producto: int, nombre_producto: string, precio: float, total_cantidad: int, total_ingresos: float}>
     */
    public function obtenerProductosMasVendidos(int $limite = 5, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $limiteSeguro = max(1, $limite);
        $inicio       = $fechaInicio !== null ? trim($fechaInicio) : '';
        $fin          = $fechaFin !== null ? trim($fechaFin) : '';

        $sql = "
            SELECT
                pr.id_producto,
                pr.nombre_producto,
                pr.precio,
                COALESCE(SUM(dp.cantidad), 0) AS total_cantidad,
                COALESCE(SUM(dp.subtotal), 0.00) AS total_ingresos
            FROM detalle_pedidos dp
            INNER JOIN productos pr ON dp.id_producto = pr.id_producto
            INNER JOIN pedidos p ON dp.id_pedido = p.id_pedido
            WHERE LOWER(TRIM(p.estado_pedido)) IN ('pagado', 'finalizado')
        ";

        if ($inicio !== '' && $fin !== '') {
            $sql .= " AND DATE(p.fecha_hora) BETWEEN :inicio AND :fin";
        } elseif ($inicio !== '') {
            $sql .= " AND DATE(p.fecha_hora) >= :inicio";
        } elseif ($fin !== '') {
            $sql .= " AND DATE(p.fecha_hora) <= :fin";
        }

        $sql .= "
            GROUP BY pr.id_producto, pr.nombre_producto, pr.precio
            ORDER BY total_cantidad DESC, total_ingresos DESC
            LIMIT :limite
        ";

        $stmt = $this->pdo->prepare($sql);
        if ($inicio !== '') {
            $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        }
        if ($fin !== '') {
            $stmt->bindValue(':fin', $fin, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limiteSeguro, PDO::PARAM_INT);
        $stmt->execute();

        $filas = $stmt->fetchAll();
        foreach ($filas as &$fila) {
            $fila['id_producto']     = (int)$fila['id_producto'];
            $fila['nombre_producto'] = (string)$fila['nombre_producto'];
            $fila['precio']          = round((float)$fila['precio'], 2);
            $fila['total_cantidad']  = (int)$fila['total_cantidad'];
            $fila['total_ingresos']  = round((float)$fila['total_ingresos'], 2);
        }
        unset($fila);

        return $filas;
    }

    /**
     * Crea o actualiza transaccionalmente la orden activa de una mesa (incluyendo órdenes
     * web de retiro asignadas a mesa según RF07 y RF09), recalculando propina (10%),
     * `total_pagar`, `saldo_pendiente` y actualizando el estado de la mesa en `mesas`.
     *
     * @param int|string $idMesa
     * @param array<int, array<string, mixed>> $productos
     * @param int|null $idUsuario
     * @param string $estadoPedido
     * @param string $estadoMesa
     * @param bool $reemplazarItems
     * @return array{id_pedido: int, id_mesa: int, subtotal: float, propina: float, total_pagar: float, monto_abonado: float, saldo_pendiente: float}
     */
    public function guardarOrdenMesa(
        $idMesa,
        array $productos,
        ?int $idUsuario = null,
        string $estadoPedido = 'En preparación',
        string $estadoMesa = 'En proceso',
        bool $reemplazarItems = false
    ): array {
        if (empty($productos)) {
            throw new InvalidArgumentException('La orden debe contener al menos un producto.');
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $mesaModel = new Mesa($this->pdo);
            $mesaInfo  = $mesaModel->obtenerPorId($idMesa);
            if (!$mesaInfo) {
                throw new RuntimeException('La mesa especificada no existe en el sistema.');
            }
            $idMesaReal = $mesaInfo['id_mesa'];

            $idUsuarioValido = $this->resolverUsuarioValido($idUsuario);

            // 1. Verificar si ya existe un pedido activo en la mesa
            $stmtActivo = $this->pdo->prepare("
                SELECT id_pedido, porcentaje_propina, COALESCE(monto_abonado, 0.00) AS monto_abonado
                FROM pedidos
                WHERE id_mesa = :id_mesa
                  AND LOWER(TRIM(estado_pedido)) NOT IN ('pagado', 'finalizado', 'cancelado', 'entregado')
                ORDER BY id_pedido DESC
                LIMIT 1
                FOR UPDATE
            ");
            $stmtActivo->bindValue(':id_mesa', $idMesaReal, PDO::PARAM_INT);
            $stmtActivo->execute();
            $pedidoExistente = $stmtActivo->fetch();

            $montoAbonado = 0.00;

            if ($pedidoExistente) {
                $idPedido     = (int)$pedidoExistente['id_pedido'];
                $montoAbonado = round((float)$pedidoExistente['monto_abonado'], 2);

                $stmtUpdEnc = $this->pdo->prepare("
                    UPDATE pedidos
                    SET estado_pedido = :estado_pedido,
                        id_usuario    = COALESCE(:id_usuario, id_usuario)
                    WHERE id_pedido = :id_pedido
                ");
                $stmtUpdEnc->bindValue(':estado_pedido', trim($estadoPedido), PDO::PARAM_STR);
                if ($idUsuarioValido !== null) {
                    $stmtUpdEnc->bindValue(':id_usuario', $idUsuarioValido, PDO::PARAM_INT);
                } else {
                    $stmtUpdEnc->bindValue(':id_usuario', null, PDO::PARAM_NULL);
                }
                $stmtUpdEnc->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                $stmtUpdEnc->execute();

                if ($reemplazarItems) {
                    $stmtDel = $this->pdo->prepare("DELETE FROM detalle_pedidos WHERE id_pedido = :id_pedido");
                    $stmtDel->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                    $stmtDel->execute();
                }
            } else {
                // 2. Insertar nuevo encabezado en `pedidos`
                $stmtIns = $this->pdo->prepare("
                    INSERT INTO pedidos (
                        fecha_hora,
                        estado_pedido,
                        porcentaje_propina,
                        total_pagar,
                        metodo_pago,
                        id_mesa,
                        id_usuario,
                        tipo_orden,
                        monto_abonado,
                        saldo_pendiente
                    ) VALUES (
                        NOW(),
                        :estado_pedido,
                        10.00,
                        0.00,
                        'Efectivo',
                        :id_mesa,
                        :id_usuario,
                        'Consumo en local',
                        0.00,
                        0.00
                    )
                ");
                $stmtIns->bindValue(':estado_pedido', trim($estadoPedido), PDO::PARAM_STR);
                $stmtIns->bindValue(':id_mesa', $idMesaReal, PDO::PARAM_INT);
                if ($idUsuarioValido !== null) {
                    $stmtIns->bindValue(':id_usuario', $idUsuarioValido, PDO::PARAM_INT);
                } else {
                    $stmtIns->bindValue(':id_usuario', null, PDO::PARAM_NULL);
                }
                $stmtIns->execute();
                $idPedido = (int)$this->pdo->lastInsertId();
            }

            // 3. Gestionar líneas en `detalle_pedidos`
            $stmtBuscarLinea = $this->pdo->prepare("
                SELECT id_detalle, cantidad
                FROM detalle_pedidos
                WHERE id_pedido = :id_pedido
                  AND id_producto = :id_producto
                LIMIT 1
            ");
            $stmtActualizarLinea = $this->pdo->prepare("
                UPDATE detalle_pedidos
                SET cantidad = :cantidad,
                    subtotal = :subtotal
                WHERE id_detalle = :id_detalle
            ");
            $stmtInsertarLinea = $this->pdo->prepare("
                INSERT INTO detalle_pedidos (cantidad, subtotal, id_pedido, id_producto)
                VALUES (:cantidad, :subtotal, :id_pedido, :id_producto)
            ");

            $lineasProcesadas = 0;
            foreach ($productos as $item) {
                $rawId      = $item['id_producto'] ?? ($item['id'] ?? ($item['codigo'] ?? 0));
                $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);
                $cantidad   = (int)($item['cantidad'] ?? ($item['qty'] ?? 0));

                if ($idProducto <= 0 || $cantidad <= 0) {
                    continue;
                }

                $precioUnitario = $this->obtenerPrecioProducto($idProducto, $item['precio'] ?? ($item['price'] ?? null));

                if (!$reemplazarItems && $pedidoExistente) {
                    $stmtBuscarLinea->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                    $stmtBuscarLinea->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
                    $stmtBuscarLinea->execute();
                    $lineaActual = $stmtBuscarLinea->fetch();

                    if ($lineaActual) {
                        $nuevaCantidad = (int)$lineaActual['cantidad'] + $cantidad;
                        $nuevoSubLinea = round($nuevaCantidad * $precioUnitario, 2);

                        $stmtActualizarLinea->bindValue(':cantidad', $nuevaCantidad, PDO::PARAM_INT);
                        $stmtActualizarLinea->bindValue(':subtotal', number_format($nuevoSubLinea, 2, '.', ''), PDO::PARAM_STR);
                        $stmtActualizarLinea->bindValue(':id_detalle', (int)$lineaActual['id_detalle'], PDO::PARAM_INT);
                        $stmtActualizarLinea->execute();
                        $lineasProcesadas++;
                        continue;
                    }
                }

                $subtotalLinea = round($cantidad * $precioUnitario, 2);
                $stmtInsertarLinea->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
                $stmtInsertarLinea->bindValue(':subtotal', number_format($subtotalLinea, 2, '.', ''), PDO::PARAM_STR);
                $stmtInsertarLinea->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                $stmtInsertarLinea->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtInsertarLinea->execute();
                $lineasProcesadas++;
            }

            if ($lineasProcesadas === 0) {
                throw new InvalidArgumentException('Ninguno de los productos enviados tiene cantidad o identificador válido.');
            }

            // Recalcular subtotal, propina (10%), total_pagar y saldo_pendiente (RF09)
            $stmtSuma = $this->pdo->prepare("
                SELECT COALESCE(SUM(subtotal), 0.00)
                FROM detalle_pedidos
                WHERE id_pedido = :id_pedido
            ");
            $stmtSuma->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtSuma->execute();

            $nuevoSubtotal  = round((float)$stmtSuma->fetchColumn(), 2);
            $propina        = round($nuevoSubtotal * 0.10, 2);
            $totalPagar     = round($nuevoSubtotal + $propina, 2);
            $saldoPendiente = max(0.00, round($totalPagar - $montoAbonado, 2));

            $stmtUpdTotales = $this->pdo->prepare("
                UPDATE pedidos
                SET porcentaje_propina = 10.00,
                    total_pagar        = :total_pagar,
                    monto_abonado      = :monto_abonado,
                    saldo_pendiente    = :saldo_pendiente
                WHERE id_pedido = :id_pedido
            ");
            $stmtUpdTotales->bindValue(':total_pagar', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
            $stmtUpdTotales->bindValue(':monto_abonado', number_format($montoAbonado, 2, '.', ''), PDO::PARAM_STR);
            $stmtUpdTotales->bindValue(':saldo_pendiente', number_format($saldoPendiente, 2, '.', ''), PDO::PARAM_STR);
            $stmtUpdTotales->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtUpdTotales->execute();

            // 4. Actualizar el estado de la mesa en `mesas`
            $mesaModel->actualizarEstado($idMesaReal, $estadoMesa);

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return [
                'id_pedido'       => $idPedido,
                'id_mesa'         => $idMesaReal,
                'subtotal'        => $nuevoSubtotal,
                'propina'         => $propina,
                'total_pagar'     => $totalPagar,
                'monto_abonado'   => $montoAbonado,
                'saldo_pendiente' => $saldoPendiente,
            ];
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Wrapper de compatibilidad hacia `guardarOrdenMesa` que retorna directamente `id_pedido`.
     *
     * @param int|string $idMesa
     * @param array<int, array<string, mixed>> $productos
     * @param int|null $idUsuario
     * @param string $estadoPedido
     * @return int
     */
    public function guardarOrden(
        $idMesa,
        array $productos,
        ?int $idUsuario = 4,
        string $estadoPedido = 'En preparación'
    ): int {
        $resultado = $this->guardarOrdenMesa($idMesa, $productos, $idUsuario, $estadoPedido, 'En proceso', false);
        return $resultado['id_pedido'];
    }

    /**
     * Crea en transacción PDO un pedido desde la Aplicación Web del cliente (RF15)
     * con `id_mesa = NULL` e `id_usuario = NULL`.
     *
     * @param array<string, mixed> $datosCliente
     * @param array<int, array<string, mixed>> $productos
     * @return array<string, mixed>
     */
    public function crearPedidoWeb(array $datosCliente, array $productos): array
    {
        if (empty($productos)) {
            throw new InvalidArgumentException('El carrito del pedido web no puede estar vacío.');
        }

        $nombreCliente   = trim((string)($datosCliente['nombre_cliente'] ?? ($datosCliente['nombre'] ?? '')));
        $apellidoCliente = trim((string)($datosCliente['apellido_cliente'] ?? ($datosCliente['apellido'] ?? '')));
        $telefonoCliente = trim((string)($datosCliente['telefono_cliente'] ?? ($datosCliente['telefono'] ?? '')));
        $correoCliente   = trim((string)($datosCliente['correo_cliente'] ?? ($datosCliente['correo'] ?? ($datosCliente['email'] ?? ''))));

        if ($nombreCliente === '') {
            throw new InvalidArgumentException('El nombre del cliente es obligatorio para registrar el pedido web.');
        }

        $tipoRaw   = mb_strtolower(trim((string)($datosCliente['tipo_orden'] ?? 'Retiro en el local')), 'UTF-8');
        $esDomicilio = str_contains($tipoRaw, 'domicilio') || str_contains($tipoRaw, 'envio') || str_contains($tipoRaw, 'envío');
        $tipoOrden = $esDomicilio ? 'Envío a domicilio' : 'Retiro en el local';

        $municipio       = $esDomicilio ? (trim((string)($datosCliente['municipio'] ?? '')) ?: 'San Salvador') : null;
        $direccion       = $esDomicilio ? trim((string)($datosCliente['direccion'] ?? '')) : null;
        $puntoReferencia = $esDomicilio ? trim((string)($datosCliente['punto_referencia'] ?? '')) : null;

        if ($esDomicilio && ($direccion === null || $direccion === '')) {
            throw new InvalidArgumentException('La dirección de entrega es obligatoria para pedidos a domicilio.');
        }

        $metodoRaw  = mb_strtolower(trim((string)($datosCliente['metodo_pago'] ?? 'Efectivo')), 'UTF-8');
        $pagoTarjeta = str_contains($metodoRaw, 'tarjeta')
            || str_contains($metodoRaw, 'linea')
            || str_contains($metodoRaw, 'línea')
            || str_contains($metodoRaw, 'online')
            || !empty($datosCliente['pagado_en_linea']);
        $metodoPago = $pagoTarjeta ? 'Tarjeta' : 'Efectivo';

        $estadoPedido = trim((string)($datosCliente['estado_pedido'] ?? 'Pendiente')) ?: 'Pendiente';

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $lineasPreparadas = [];
            $subtotal         = 0.00;

            foreach ($productos as $item) {
                $rawId      = $item['id_producto'] ?? ($item['id'] ?? ($item['codigo'] ?? 0));
                $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D+/', '', (string)$rawId);
                $cantidad   = (int)($item['cantidad'] ?? ($item['qty'] ?? 0));

                if ($idProducto <= 0 || $cantidad <= 0) {
                    continue;
                }

                $precioUnit    = $this->obtenerPrecioProducto($idProducto, $item['precio'] ?? ($item['price'] ?? null));
                $subtotalLinea = round($cantidad * $precioUnit, 2);
                $subtotal     += $subtotalLinea;

                $lineasPreparadas[] = [
                    'id_producto' => $idProducto,
                    'cantidad'    => $cantidad,
                    'subtotal'    => $subtotalLinea,
                ];
            }

            if (empty($lineasPreparadas)) {
                throw new InvalidArgumentException('No se encontraron productos válidos en el pedido web.');
            }

            $subtotal          = round($subtotal, 2);
            $porcentajePropina = 10.00;
            $propina           = round($subtotal * 0.10, 2);
            $totalPagar        = round($subtotal + $propina, 2);
            $montoAbonado      = $pagoTarjeta ? $totalPagar : 0.00;
            $saldoPendiente    = max(0.00, round($totalPagar - $montoAbonado, 2));

            $stmtPed = $this->pdo->prepare("
                INSERT INTO pedidos (
                    fecha_hora,
                    estado_pedido,
                    porcentaje_propina,
                    total_pagar,
                    metodo_pago,
                    id_mesa,
                    id_usuario,
                    nombre_cliente,
                    apellido_cliente,
                    telefono_cliente,
                    correo_cliente,
                    tipo_orden,
                    municipio,
                    direccion,
                    punto_referencia,
                    monto_abonado,
                    saldo_pendiente
                ) VALUES (
                    NOW(),
                    :estado_pedido,
                    :porcentaje_propina,
                    :total_pagar,
                    :metodo_pago,
                    NULL,
                    NULL,
                    :nombre_cliente,
                    :apellido_cliente,
                    :telefono_cliente,
                    :correo_cliente,
                    :tipo_orden,
                    :municipio,
                    :direccion,
                    :punto_referencia,
                    :monto_abonado,
                    :saldo_pendiente
                )
            ");
            $stmtPed->bindValue(':estado_pedido', $estadoPedido, PDO::PARAM_STR);
            $stmtPed->bindValue(':porcentaje_propina', number_format($porcentajePropina, 2, '.', ''), PDO::PARAM_STR);
            $stmtPed->bindValue(':total_pagar', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
            $stmtPed->bindValue(':metodo_pago', $metodoPago, PDO::PARAM_STR);
            $stmtPed->bindValue(':nombre_cliente', $nombreCliente, PDO::PARAM_STR);
            $stmtPed->bindValue(':apellido_cliente', $apellidoCliente !== '' ? $apellidoCliente : null, $apellidoCliente !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtPed->bindValue(':telefono_cliente', $telefonoCliente !== '' ? $telefonoCliente : null, $telefonoCliente !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtPed->bindValue(':correo_cliente', $correoCliente !== '' ? $correoCliente : null, $correoCliente !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtPed->bindValue(':tipo_orden', $tipoOrden, PDO::PARAM_STR);
            $stmtPed->bindValue(':municipio', $municipio, $municipio !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtPed->bindValue(':direccion', $direccion, $direccion !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtPed->bindValue(':punto_referencia', $puntoReferencia !== null && $puntoReferencia !== '' ? $puntoReferencia : null, ($puntoReferencia !== null && $puntoReferencia !== '') ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtPed->bindValue(':monto_abonado', number_format($montoAbonado, 2, '.', ''), PDO::PARAM_STR);
            $stmtPed->bindValue(':saldo_pendiente', number_format($saldoPendiente, 2, '.', ''), PDO::PARAM_STR);
            $stmtPed->execute();

            $idPedido = (int)$this->pdo->lastInsertId();

            $stmtDet = $this->pdo->prepare("
                INSERT INTO detalle_pedidos (cantidad, subtotal, id_pedido, id_producto)
                VALUES (:cantidad, :subtotal, :id_pedido, :id_producto)
            ");
            foreach ($lineasPreparadas as $linea) {
                $stmtDet->bindValue(':cantidad', $linea['cantidad'], PDO::PARAM_INT);
                $stmtDet->bindValue(':subtotal', number_format($linea['subtotal'], 2, '.', ''), PDO::PARAM_STR);
                $stmtDet->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                $stmtDet->bindValue(':id_producto', $linea['id_producto'], PDO::PARAM_INT);
                $stmtDet->execute();
            }

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return [
                'id_pedido'          => $idPedido,
                'tipo_orden'         => $tipoOrden,
                'estado_pedido'      => $estadoPedido,
                'metodo_pago'        => $metodoPago,
                'subtotal'           => $subtotal,
                'porcentaje_propina' => $porcentajePropina,
                'propina'            => $propina,
                'total_pagar'        => $totalPagar,
                'monto_abonado'      => $montoAbonado,
                'saldo_pendiente'    => $saldoPendiente,
            ];
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lista los pedidos originados en la App Web (`Envío a domicilio` y `Retiro en el local`)
     * incluyendo sus líneas de productos y datos logísticos para la bandeja del Cajero (RF16).
     *
     * @param string|null $tipoOrden
     * @param string|null $estado
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPedidosWeb(?string $tipoOrden = null, ?string $estado = null): array
    {
        $sql = "
            SELECT
                p.id_pedido,
                p.fecha_hora,
                p.estado_pedido,
                p.porcentaje_propina,
                p.total_pagar,
                p.metodo_pago,
                p.id_mesa,
                m.numero_mesa,
                p.id_usuario,
                u.nombre_completo AS usuario_asignado,
                p.nombre_cliente,
                p.apellido_cliente,
                p.telefono_cliente,
                p.correo_cliente,
                p.tipo_orden,
                p.municipio,
                p.direccion,
                p.punto_referencia,
                COALESCE(p.monto_abonado, 0.00) AS monto_abonado,
                COALESCE(p.saldo_pendiente, p.total_pagar) AS saldo_pendiente
            FROM pedidos p
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.tipo_orden IN ('Envío a domicilio', 'Retiro en el local')
        ";

        $params = [];

        if ($tipoOrden !== null && trim($tipoOrden) !== '' && mb_strtolower(trim($tipoOrden), 'UTF-8') !== 'todos') {
            $tipoLim = mb_strtolower(trim($tipoOrden), 'UTF-8');
            if (str_contains($tipoLim, 'domicilio') || str_contains($tipoLim, 'envio') || str_contains($tipoLim, 'envío')) {
                $params[':tipo_orden'] = 'Envío a domicilio';
            } elseif (str_contains($tipoLim, 'retiro')) {
                $params[':tipo_orden'] = 'Retiro en el local';
            } else {
                $params[':tipo_orden'] = trim($tipoOrden);
            }
            $sql .= " AND LOWER(TRIM(p.tipo_orden)) = LOWER(:tipo_orden)";
        }

        if ($estado !== null && trim($estado) !== '' && mb_strtolower(trim($estado), 'UTF-8') !== 'todos') {
            $params[':estado_pedido'] = trim($estado);
            $sql .= " AND LOWER(TRIM(p.estado_pedido)) = LOWER(:estado_pedido)";
        }

        $sql .= " ORDER BY p.fecha_hora DESC, p.id_pedido DESC";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $stmt->execute();

        $pedidos = $stmt->fetchAll();
        if (empty($pedidos)) {
            return [];
        }

        // Cargar todos los productos de los pedidos listados en una sola consulta preparada
        $idsPedidos   = array_map(static fn(array $p): int => (int)$p['id_pedido'], $pedidos);
        $placeholders = implode(', ', array_fill(0, count($idsPedidos), '?'));

        $stmtDet = $this->pdo->prepare("
            SELECT
                dp.id_detalle,
                dp.id_pedido,
                dp.id_producto,
                pr.nombre_producto,
                pr.precio,
                dp.cantidad,
                dp.subtotal
            FROM detalle_pedidos dp
            INNER JOIN productos pr ON dp.id_producto = pr.id_producto
            WHERE dp.id_pedido IN ({$placeholders})
            ORDER BY dp.id_detalle ASC
        ");
        foreach ($idsPedidos as $idx => $idPed) {
            $stmtDet->bindValue($idx + 1, $idPed, PDO::PARAM_INT);
        }
        $stmtDet->execute();

        $detallesPorPedido = [];
        $subtotalesPedido  = [];
        while ($det = $stmtDet->fetch()) {
            $pid = (int)$det['id_pedido'];
            $sub = round((float)$det['subtotal'], 2);
            $detallesPorPedido[$pid][] = [
                'id_detalle'      => (int)$det['id_detalle'],
                'id_producto'     => (int)$det['id_producto'],
                'nombre_producto' => (string)$det['nombre_producto'],
                'precio'          => round((float)$det['precio'], 2),
                'cantidad'        => (int)$det['cantidad'],
                'subtotal'        => $sub,
            ];
            $subtotalesPedido[$pid] = round(($subtotalesPedido[$pid] ?? 0.00) + $sub, 2);
        }

        foreach ($pedidos as &$pedido) {
            $pid                          = (int)$pedido['id_pedido'];
            $pedido['id_pedido']          = $pid;
            $pedido['porcentaje_propina'] = round((float)$pedido['porcentaje_propina'], 2);
            $pedido['subtotal']           = $subtotalesPedido[$pid] ?? 0.00;
            $pedido['total_pagar']        = round((float)$pedido['total_pagar'], 2);
            $pedido['id_mesa']            = $pedido['id_mesa'] !== null ? (int)$pedido['id_mesa'] : null;
            $pedido['numero_mesa']        = $pedido['numero_mesa'] !== null ? (int)$pedido['numero_mesa'] : null;
            $pedido['id_usuario']         = $pedido['id_usuario'] !== null ? (int)$pedido['id_usuario'] : null;
            $pedido['monto_abonado']      = round((float)$pedido['monto_abonado'], 2);
            $pedido['saldo_pendiente']    = round((float)$pedido['saldo_pendiente'], 2);
            $pedido['productos']          = $detallesPorPedido[$pid] ?? [];
        }
        unset($pedido);

        return $pedidos;
    }

    /**
     * Verifica en transacción PDO que la mesa esté `'Disponible'`, vincula `id_mesa`
     * e `id_usuario` al pedido web de retiro y cambia `estado_mesa` a `'En proceso'` (RF17).
     *
     * @param int $idPedido
     * @param int $idMesa
     * @param int|null $idUsuario
     * @return bool
     */
    public function asignarMesaPedidoWeb(int $idPedido, int $idMesa, ?int $idUsuario = null): bool
    {
        if ($idPedido <= 0 || $idMesa <= 0) {
            throw new InvalidArgumentException('Pedido y mesa son requeridos para la asignación.');
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmtMesa = $this->pdo->prepare("
                SELECT id_mesa, numero_mesa, estado_mesa
                FROM mesas
                WHERE id_mesa = :id_mesa OR numero_mesa = :numero_mesa
                ORDER BY CASE WHEN id_mesa = :id_prioridad THEN 0 ELSE 1 END ASC
                LIMIT 1
                FOR UPDATE
            ");
            $stmtMesa->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $stmtMesa->bindValue(':numero_mesa', $idMesa, PDO::PARAM_INT);
            $stmtMesa->bindValue(':id_prioridad', $idMesa, PDO::PARAM_INT);
            $stmtMesa->execute();
            $mesa = $stmtMesa->fetch();

            if (!$mesa) {
                throw new RuntimeException('La mesa seleccionada no existe.');
            }

            $estadoMesaActual = mb_strtolower(trim((string)$mesa['estado_mesa']), 'UTF-8');
            if (!in_array($estadoMesaActual, ['disponible', 'libre'], true)) {
                throw new RuntimeException("La Mesa {$mesa['numero_mesa']} no se encuentra disponible actualmente.");
            }

            $stmtPedido = $this->pdo->prepare("
                SELECT id_pedido, estado_pedido, tipo_orden
                FROM pedidos
                WHERE id_pedido = :id_pedido
                LIMIT 1
                FOR UPDATE
            ");
            $stmtPedido->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtPedido->execute();
            $pedido = $stmtPedido->fetch();

            if (!$pedido) {
                throw new RuntimeException('El pedido web indicado no existe.');
            }

            $idMesaReal      = (int)$mesa['id_mesa'];
            $idUsuarioValido = $this->resolverUsuarioValido($idUsuario);
            $estadoPedActual = mb_strtolower(trim((string)$pedido['estado_pedido']), 'UTF-8');
            $nuevoEstadoPed  = in_array($estadoPedActual, ['pendiente', 'recibido'], true)
                ? 'En preparación'
                : (string)$pedido['estado_pedido'];

            $stmtUpdPed = $this->pdo->prepare("
                UPDATE pedidos
                SET id_mesa       = :id_mesa,
                    id_usuario    = COALESCE(:id_usuario, id_usuario),
                    estado_pedido = :estado_pedido
                WHERE id_pedido = :id_pedido
            ");
            $stmtUpdPed->bindValue(':id_mesa', $idMesaReal, PDO::PARAM_INT);
            if ($idUsuarioValido !== null) {
                $stmtUpdPed->bindValue(':id_usuario', $idUsuarioValido, PDO::PARAM_INT);
            } else {
                $stmtUpdPed->bindValue(':id_usuario', null, PDO::PARAM_NULL);
            }
            $stmtUpdPed->bindValue(':estado_pedido', $nuevoEstadoPed, PDO::PARAM_STR);
            $stmtUpdPed->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtUpdPed->execute();

            $mesaModel = new Mesa($this->pdo);
            $mesaModel->actualizarEstado($idMesaReal, 'En proceso');

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return true;
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Actualiza el estado operativo de un pedido (`estado_pedido`) (RF08, RF16).
     *
     * @param int $idPedido
     * @param string $nuevoEstado
     * @param int|null $idUsuario
     * @return bool
     */
    public function actualizarEstadoPedido(int $idPedido, string $nuevoEstado, ?int $idUsuario = null): bool
    {
        $estadoLimpio = trim($nuevoEstado);
        if ($idPedido <= 0 || $estadoLimpio === '') {
            return false;
        }

        $idUsuarioValido = $this->resolverUsuarioValido($idUsuario);

        if ($idUsuarioValido !== null) {
            $stmt = $this->pdo->prepare("
                UPDATE pedidos
                SET estado_pedido = :estado_pedido,
                    id_usuario    = :id_usuario
                WHERE id_pedido = :id_pedido
            ");
            $stmt->bindValue(':id_usuario', $idUsuarioValido, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE pedidos
                SET estado_pedido = :estado_pedido
                WHERE id_pedido = :id_pedido
            ");
        }

        $stmt->bindValue(':estado_pedido', $estadoLimpio, PDO::PARAM_STR);
        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Ejecuta en transacción PDO el cierre financiero de la cuenta:
     * localiza el pedido activo de la mesa (o por `$idPedido`), actualiza `estado_pedido = 'Pagado'`,
     * `metodo_pago`, `saldo_pendiente = 0.00`, descuenta insumos en `inventarios` según `recetas` (RF14)
     * y libera la mesa física (`estado_mesa = 'Disponible'`) (RF10).
     *
     * Soporta tanto la firma canónica `($idMesa, ?int $idPedido, string $metodoPago, ?int $idCajero)`
     * como la invocación previa de `ServicioController` de 5 argumentos.
     *
     * @param int|string|null $idMesa
     * @param int|float|null $idPedido
     * @param string|int $metodoPago
     * @param int|null $idCajero
     * @param string|null $metodoPagoLegacy
     * @return array<string, mixed>
     */
    public function cerrarCuenta(
        $idMesa,
        int|float|null $idPedido = null,
        string|int $metodoPago = 'Efectivo',
        ?int $idCajero = null,
        ?string $metodoPagoLegacy = null
    ): array {
        $montoRecibido = 0.00;

        if (func_num_args() >= 5 || is_float($idPedido) || is_int($metodoPago)) {
            $montoRecibido  = (float)($idPedido ?? 0.00);
            $idCajeroReal   = is_int($metodoPago) ? $metodoPago : null;
            $idPedidoReal   = $idCajero;
            $metodoPagoReal = trim((string)($metodoPagoLegacy ?? 'Efectivo')) ?: 'Efectivo';
        } else {
            $idPedidoReal   = $idPedido !== null ? (int)$idPedido : null;
            $metodoPagoReal = trim((string)$metodoPago) ?: 'Efectivo';
            $idCajeroReal   = $idCajero;
        }

        $transaccionPropia = !$this->pdo->inTransaction();
        if ($transaccionPropia) {
            $this->pdo->beginTransaction();
        }

        try {
            $mesaModel  = new Mesa($this->pdo);
            $mesaInfo   = ($idMesa !== null && $idMesa !== '' && $idMesa !== 0) ? $mesaModel->obtenerPorId($idMesa) : null;
            $idMesaReal = $mesaInfo ? $mesaInfo['id_mesa'] : 0;

            $pedido = null;
            if ($idPedidoReal !== null && $idPedidoReal > 0) {
                $stmtPed = $this->pdo->prepare("
                    SELECT *
                    FROM pedidos
                    WHERE id_pedido = :id_pedido
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmtPed->bindValue(':id_pedido', $idPedidoReal, PDO::PARAM_INT);
                $stmtPed->execute();
                $pedido = $stmtPed->fetch() ?: null;

                if ($pedido && $idMesaReal <= 0 && $pedido['id_mesa'] !== null) {
                    $idMesaReal = (int)$pedido['id_mesa'];
                }
            } elseif ($idMesaReal > 0) {
                $stmtPed = $this->pdo->prepare("
                    SELECT *
                    FROM pedidos
                    WHERE id_mesa = :id_mesa
                      AND LOWER(TRIM(estado_pedido)) NOT IN ('pagado', 'finalizado', 'cancelado')
                    ORDER BY id_pedido DESC
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmtPed->bindValue(':id_mesa', $idMesaReal, PDO::PARAM_INT);
                $stmtPed->execute();
                $pedido = $stmtPed->fetch() ?: null;
            }

            if (!$pedido) {
                if ($transaccionPropia) {
                    $this->pdo->commit();
                }
                return [
                    'id_pedido'          => 0,
                    'id_mesa'            => $idMesaReal > 0 ? $idMesaReal : null,
                    'subtotal'           => 0.00,
                    'porcentaje_propina' => 10.00,
                    'propina'            => 0.00,
                    'total'              => 0.00,
                    'total_pagar'        => 0.00,
                    'monto_abonado'      => 0.00,
                    'saldo_pendiente'    => 0.00,
                    'metodo_pago'        => $metodoPagoReal,
                    'efectivo_recibido'  => 0.00,
                    'cambio'             => 0.00,
                ];
            }

            $idPedidoEncontrado = (int)$pedido['id_pedido'];
            $yaPagado           = in_array(mb_strtolower(trim((string)$pedido['estado_pedido']), 'UTF-8'), ['pagado', 'finalizado'], true);

            $stmtSub = $this->pdo->prepare("
                SELECT COALESCE(SUM(subtotal), 0.00)
                FROM detalle_pedidos
                WHERE id_pedido = :id_pedido
            ");
            $stmtSub->bindValue(':id_pedido', $idPedidoEncontrado, PDO::PARAM_INT);
            $stmtSub->execute();

            $subtotal          = round((float)$stmtSub->fetchColumn(), 2);
            $porcentajePropina = isset($pedido['porcentaje_propina']) && (float)$pedido['porcentaje_propina'] > 0
                ? round((float)$pedido['porcentaje_propina'], 2)
                : 10.00;
            $propina           = round($subtotal * ($porcentajePropina / 100), 2);
            $totalPagar        = round($subtotal + $propina, 2);
            $abonadoPrevio     = round((float)($pedido['monto_abonado'] ?? 0.00), 2);
            $saldoACobrar      = max(0.00, round($totalPagar - $abonadoPrevio, 2));

            if ($montoRecibido <= 0 || $montoRecibido < $saldoACobrar) {
                $efectivoRecibido = $saldoACobrar;
                $cambio           = 0.00;
            } else {
                $efectivoRecibido = round($montoRecibido, 2);
                $cambio           = round($efectivoRecibido - $saldoACobrar, 2);
            }

            $idCajeroValido = $this->resolverUsuarioValido($idCajeroReal);

            if (!$yaPagado) {
                $stmtUpd = $this->pdo->prepare("
                    UPDATE pedidos
                    SET estado_pedido      = 'Pagado',
                        porcentaje_propina = :porcentaje_propina,
                        total_pagar        = :total_pagar,
                        metodo_pago        = :metodo_pago,
                        monto_abonado      = :monto_abonado,
                        saldo_pendiente    = 0.00,
                        id_usuario         = COALESCE(:id_cajero, id_usuario)
                    WHERE id_pedido = :id_pedido
                ");
                $stmtUpd->bindValue(':porcentaje_propina', number_format($porcentajePropina, 2, '.', ''), PDO::PARAM_STR);
                $stmtUpd->bindValue(':total_pagar', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
                $stmtUpd->bindValue(':metodo_pago', $metodoPagoReal, PDO::PARAM_STR);
                $stmtUpd->bindValue(':monto_abonado', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
                if ($idCajeroValido !== null) {
                    $stmtUpd->bindValue(':id_cajero', $idCajeroValido, PDO::PARAM_INT);
                } else {
                    $stmtUpd->bindValue(':id_cajero', null, PDO::PARAM_NULL);
                }
                $stmtUpd->bindValue(':id_pedido', $idPedidoEncontrado, PDO::PARAM_INT);
                $stmtUpd->execute();

                // Descuento automático de insumos en `inventarios` según `recetas` (RF14)
                $inventarioModel = new Inventario($this->pdo);
                $inventarioModel->descontarPorPedido($idPedidoEncontrado);
            }

            // Liberar la mesa física dejándola en 'Disponible' (RF10)
            if ($idMesaReal > 0) {
                $mesaModel->actualizarEstado($idMesaReal, 'Disponible');
            }

            if ($transaccionPropia) {
                $this->pdo->commit();
            }

            return [
                'id_pedido'          => $idPedidoEncontrado,
                'id_mesa'            => $idMesaReal > 0 ? $idMesaReal : null,
                'subtotal'           => $subtotal,
                'porcentaje_propina' => $porcentajePropina,
                'propina'            => $propina,
                'total'              => $totalPagar,
                'total_pagar'        => $totalPagar,
                'monto_abonado'      => $totalPagar,
                'saldo_pendiente'    => 0.00,
                'metodo_pago'        => $metodoPagoReal,
                'efectivo_recibido'  => $efectivoRecibido,
                'cambio'             => $cambio,
            ];
        } catch (Throwable $e) {
            if ($transaccionPropia && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene el precio unitario oficial del producto en `productos` con respaldo al valor provisto.
     *
     * @param int $idProducto
     * @param mixed $precioFallback
     * @return float
     */
    private function obtenerPrecioProducto(int $idProducto, $precioFallback = null): float
    {
        $stmt = $this->pdo->prepare("
            SELECT precio
            FROM productos
            WHERE id_producto = :id_producto
            LIMIT 1
        ");
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();

        $precioBD = $stmt->fetchColumn();
        if ($precioBD !== false) {
            return round((float)$precioBD, 2);
        }

        return $precioFallback !== null ? max(0.00, round((float)$precioFallback, 2)) : 0.00;
    }

    /**
     * Verifica que el `id_usuario` exista en la tabla `usuarios` antes de asignarlo como llave foránea.
     *
     * @param int|null $idUsuario
     * @return int|null
     */
    private function resolverUsuarioValido(?int $idUsuario): ?int
    {
        if ($idUsuario === null || $idUsuario <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT id_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false ? $idUsuario : null;
    }
}
