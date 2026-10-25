<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Pedido
 * models/Pedido.php
 * Gestiona órdenes y detalles transaccionales utilizando exclusivamente las tablas
 * `Pedidos`, `Detalle_Pedidos`, `Productos`, `Mesas`, `Usuarios`, `Recetas` e `Inventarios`
 * ==============================================================================
 */

class Pedido {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca la orden pendiente de pago para una mesa específica junto con sus platillos
     *
     * @param int|string $idMesa ID numérico o número de la mesa
     * @return array
     */
    public function obtenerCuentaActivaPorMesa($idMesa): array {
        $idInt = is_numeric($idMesa) ? (int)$idMesa : (int)preg_replace('/\D/', '', (string)$idMesa);

        $stmt = $this->pdo->prepare("
            SELECT 
                dp.id_detalle,
                dp.id_producto,
                pr.nombre_producto,
                pr.precio,
                dp.cantidad,
                dp.subtotal,
                p.id_pedido,
                p.fecha_hora,
                p.estado_pedido,
                p.porcentaje_propina,
                p.total_pagar,
                p.metodo_pago,
                p.id_usuario,
                u.nombre_completo,
                m.id_mesa,
                m.numero_mesa
            FROM Pedidos p
            INNER JOIN Detalle_Pedidos dp ON p.id_pedido = dp.id_pedido
            INNER JOIN Productos pr ON dp.id_producto = pr.id_producto
            INNER JOIN Mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN Usuarios u ON p.id_usuario = u.id_usuario
            WHERE (m.id_mesa = :id_mesa OR m.numero_mesa = :numero_mesa)
              AND p.estado_pedido != 'Pagado'
            ORDER BY dp.id_detalle ASC
        ");

        $stmt->bindValue(':id_mesa', $idInt, PDO::PARAM_INT);
        $stmt->bindValue(':numero_mesa', $idInt, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Consulta el consumo subtotal activo agrupado por mesa para pintar el plano del salón
     *
     * @return array Mapa asociativo [id_mesa => consumo_subtotal]
     */
    public function obtenerConsumosActivosPorMesa(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id_mesa,
                COALESCE(SUM(dp.subtotal), 0) AS consumo_subtotal
            FROM Pedidos p
            INNER JOIN Detalle_Pedidos dp ON p.id_pedido = dp.id_pedido
            WHERE p.estado_pedido != 'Pagado'
            GROUP BY p.id_mesa
        ");
        $stmt->execute();

        $consumos = [];
        while ($row = $stmt->fetch()) {
            $consumos[(int)$row['id_mesa']] = (float)$row['consumo_subtotal'];
        }
        return $consumos;
    }

    /**
     * Calcula métricas financieras consolidadas a partir de las órdenes pagadas en `Pedidos`
     *
     * @return array ['cantidad_ventas' => int, 'total_vendido' => float]
     */
    public function obtenerEstadisticasVentas(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(id_pedido) AS cantidad_ventas,
                COALESCE(SUM(total_pagar), 0) AS total_vendido
            FROM Pedidos
            WHERE estado_pedido = 'Pagado'
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        return [
            'cantidad_ventas' => (int)($stats['cantidad_ventas'] ?? 0),
            'total_vendido'   => (float)($stats['total_vendido'] ?? 0.00)
        ];
    }

    /**
     * Agrupa y suma las cantidades vendidas por producto en órdenes con estado_pedido = 'Pagado'
     *
     * @param int $limite Número máximo de productos a retornar (por defecto 5)
     * @return array
     */
    public function obtenerProductosMasVendidos(int $limite = 5): array {
        $limite = max(1, (int)$limite);
        $stmt = $this->pdo->prepare("
            SELECT 
                pr.id_producto,
                pr.nombre_producto,
                pr.precio,
                COALESCE(SUM(dp.cantidad), 0) AS total_cantidad,
                COALESCE(SUM(dp.subtotal), 0) AS total_ingresos
            FROM Detalle_Pedidos dp
            INNER JOIN Productos pr ON dp.id_producto = pr.id_producto
            INNER JOIN Pedidos p ON dp.id_pedido = p.id_pedido
            WHERE p.estado_pedido = 'Pagado'
            GROUP BY pr.id_producto, pr.nombre_producto, pr.precio
            ORDER BY total_cantidad DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo pedido o actualiza uno activo para una mesa en `Pedidos`,
     * reemplazando sus líneas de detalle en `Detalle_Pedidos`
     *
     * @param int $idMesa ID numérico de la mesa
     * @param array $productos Arreglo de platillos [{id/id_producto, qty/cantidad, price/precio}]
     * @param int $idUsuario ID del usuario que registra la orden
     * @param string $estadoPedido 'En preparación', 'Recibido', etc.
     * @return int ID del pedido guardado (id_pedido)
     */
    public function guardarOrden(
        int $idMesa, 
        array $productos, 
        int $idUsuario = 4, 
        string $estadoPedido = 'En preparación'
    ): int {
        // Verificar que el id_usuario exista en Usuarios; en caso contrario usar el primer usuario válido
        $stmtUsr = $this->pdo->prepare("SELECT id_usuario FROM Usuarios WHERE id_usuario = :id_usuario LIMIT 1");
        $stmtUsr->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtUsr->execute();
        if (!$stmtUsr->fetchColumn()) {
            $idUsuario = (int)($this->pdo->query("SELECT id_usuario FROM Usuarios ORDER BY id_usuario ASC LIMIT 1")->fetchColumn() ?: 1);
        }

        // 1. Buscar si ya existe un pedido activo no pagado para esta mesa
        $stmtPedidoActivo = $this->pdo->prepare("
            SELECT id_pedido 
            FROM Pedidos 
            WHERE id_mesa = :id_mesa 
              AND estado_pedido != 'Pagado'
            ORDER BY id_pedido DESC 
            LIMIT 1
        ");
        $stmtPedidoActivo->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        $stmtPedidoActivo->execute();
        $pedidoExistente = $stmtPedidoActivo->fetch();

        if ($pedidoExistente) {
            $idPedido = (int)$pedidoExistente['id_pedido'];

            $stmtUpdPed = $this->pdo->prepare("
                UPDATE Pedidos 
                SET estado_pedido = :estado_pedido, 
                    id_usuario = :id_usuario, 
                    fecha_hora = NOW() 
                WHERE id_pedido = :id_pedido
            ");
            $stmtUpdPed->bindValue(':estado_pedido', $estadoPedido, PDO::PARAM_STR);
            $stmtUpdPed->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtUpdPed->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtUpdPed->execute();

            $stmtDelDet = $this->pdo->prepare("DELETE FROM Detalle_Pedidos WHERE id_pedido = :id_pedido");
            $stmtDelDet->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtDelDet->execute();
        } else {
            $stmtNuevoPed = $this->pdo->prepare("
                INSERT INTO Pedidos (
                    fecha_hora, 
                    estado_pedido, 
                    porcentaje_propina, 
                    total_pagar, 
                    metodo_pago, 
                    id_mesa, 
                    id_usuario
                ) VALUES (
                    NOW(), 
                    :estado_pedido, 
                    10.00, 
                    0.00, 
                    'Efectivo', 
                    :id_mesa, 
                    :id_usuario
                )
            ");
            $stmtNuevoPed->bindValue(':estado_pedido', $estadoPedido, PDO::PARAM_STR);
            $stmtNuevoPed->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $stmtNuevoPed->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtNuevoPed->execute();
            $idPedido = (int)$this->pdo->lastInsertId();
        }

        // 2. Insertar cada producto en Detalle_Pedidos y acumular el subtotal de la orden
        $stmtDetalle = $this->pdo->prepare("
            INSERT INTO Detalle_Pedidos (cantidad, subtotal, id_pedido, id_producto) 
            VALUES (:cantidad, :subtotal, :id_pedido, :id_producto)
        ");

        $preciosCache = [];
        $subtotalAcumulado = 0.00;

        foreach ($productos as $prod) {
            $rawId = $prod['id_producto'] ?? ($prod['id'] ?? ($prod['codigo'] ?? 0));
            $idProducto = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D/', '', (string)$rawId);
            $cantidad = (int)($prod['cantidad'] ?? ($prod['qty'] ?? 1));
            $precioUnit = (float)($prod['precio'] ?? ($prod['price'] ?? 0.00));

            if ($cantidad > 0 && $idProducto > 0) {
                if ($precioUnit <= 0) {
                    if (!isset($preciosCache[$idProducto])) {
                        $stmtPrecio = $this->pdo->prepare("SELECT precio FROM Productos WHERE id_producto = :id_producto LIMIT 1");
                        $stmtPrecio->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
                        $stmtPrecio->execute();
                        $preciosCache[$idProducto] = (float)($stmtPrecio->fetchColumn() ?: 0.00);
                    }
                    $precioUnit = $preciosCache[$idProducto];
                }

                $subtotalLinea = round($cantidad * $precioUnit, 2);
                $subtotalAcumulado += $subtotalLinea;

                $stmtDetalle->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':subtotal', number_format($subtotalLinea, 2, '.', ''), PDO::PARAM_STR);
                $stmtDetalle->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtDetalle->execute();
            }
        }

        // 3. Actualizar total_pagar (subtotal + 10% propina) en Pedidos
        $porcentajePropina = 10.00;
        $totalPagar = round($subtotalAcumulado * (1 + ($porcentajePropina / 100)), 2);

        $stmtUpdTotal = $this->pdo->prepare("
            UPDATE Pedidos 
            SET porcentaje_propina = :porcentaje_propina,
                total_pagar = :total_pagar
            WHERE id_pedido = :id_pedido
        ");
        $stmtUpdTotal->bindValue(':porcentaje_propina', number_format($porcentajePropina, 2, '.', ''), PDO::PARAM_STR);
        $stmtUpdTotal->bindValue(':total_pagar', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
        $stmtUpdTotal->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmtUpdTotal->execute();

        return $idPedido;
    }

    /**
     * Cierra la cuenta de un pedido activo actualizando `Pedidos` (estado_pedido = 'Pagado',
     * porcentaje_propina, total_pagar, metodo_pago) y descontando insumos en `Inventarios` según `Recetas`
     *
     * @param int $idMesa ID de la mesa
     * @param float $montoRecibido Dinero en efectivo recibido
     * @param int $idUsuario ID del usuario que procesa el cobro
     * @param int|null $idPedidoDirecto ID de pedido explícito opcional
     * @param string $metodoPago Método de pago ('Efectivo')
     * @return array Resumen financiero del pedido cerrado
     */
    public function cerrarCuenta(
        int $idMesa, 
        float $montoRecibido = 0.00, 
        int $idUsuario = 3, 
        ?int $idPedidoDirecto = null,
        string $metodoPago = 'Efectivo'
    ): array {
        $idPedido = 0;

        if ($idPedidoDirecto && $idPedidoDirecto > 0) {
            $idPedido = $idPedidoDirecto;
        } else {
            $stmtPedido = $this->pdo->prepare("
                SELECT id_pedido 
                FROM Pedidos 
                WHERE id_mesa = :id_mesa 
                  AND estado_pedido != 'Pagado'
                ORDER BY id_pedido DESC 
                LIMIT 1
            ");
            $stmtPedido->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $stmtPedido->execute();
            $pedidoActivo = $stmtPedido->fetch();
            $idPedido = $pedidoActivo ? (int)$pedidoActivo['id_pedido'] : 0;
        }

        $subtotal = 0.00;
        $porcentajePropina = 10.00;
        $propina = 0.00;
        $totalPagar = 0.00;
        $cambio = 0.00;

        if ($idPedido > 0) {
            // Calcular el subtotal sumando la columna subtotal de Detalle_Pedidos
            $stmtTotal = $this->pdo->prepare("
                SELECT COALESCE(SUM(subtotal), 0) AS subtotal
                FROM Detalle_Pedidos
                WHERE id_pedido = :id_pedido
            ");
            $stmtTotal->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtTotal->execute();
            $resSubtotal = $stmtTotal->fetch();

            $subtotal = (float)($resSubtotal['subtotal'] ?? 0.00);
            $propina = round($subtotal * ($porcentajePropina / 100), 2);
            $totalPagar = round($subtotal + $propina, 2);

            if ($montoRecibido <= 0 || $montoRecibido < $totalPagar) {
                $montoRecibido = $totalPagar;
                $cambio = 0.00;
            } else {
                $cambio = round($montoRecibido - $totalPagar, 2);
            }

            // Actualizar el pedido a 'Pagado' con sus totales finales en la tabla Pedidos
            $stmtUpdPed = $this->pdo->prepare("
                UPDATE Pedidos 
                SET estado_pedido = 'Pagado',
                    porcentaje_propina = :porcentaje_propina,
                    total_pagar = :total_pagar,
                    metodo_pago = :metodo_pago
                WHERE id_pedido = :id_pedido
            ");
            $stmtUpdPed->bindValue(':porcentaje_propina', number_format($porcentajePropina, 2, '.', ''), PDO::PARAM_STR);
            $stmtUpdPed->bindValue(':total_pagar', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
            $stmtUpdPed->bindValue(':metodo_pago', trim($metodoPago), PDO::PARAM_STR);
            $stmtUpdPed->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtUpdPed->execute();

            // Descontar automáticamente del inventario según las recetas de los productos vendidos
            $stmtDescInv = $this->pdo->prepare("
                UPDATE Inventarios i
                INNER JOIN Recetas r ON i.id_ingrediente = r.id_ingrediente
                INNER JOIN Detalle_Pedidos dp ON r.id_producto = dp.id_producto
                SET i.cantidad_estimada = GREATEST(0, i.cantidad_estimada - (r.cantidad_necesaria * dp.cantidad))
                WHERE dp.id_pedido = :id_pedido
            ");
            $stmtDescInv->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtDescInv->execute();
        }

        return [
            'id_pedido'          => $idPedido,
            'subtotal'           => $subtotal,
            'porcentaje_propina' => $porcentajePropina,
            'propina'            => $propina,
            'total'              => $totalPagar,
            'total_pagar'        => $totalPagar,
            'metodo_pago'        => $metodoPago,
            'efectivo_recibido'  => $montoRecibido,
            'cambio'             => $cambio
        ];
    }
}
