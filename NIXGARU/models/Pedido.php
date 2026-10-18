<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Pedido
 * models/Pedido.php
 * ==============================================================================
 */

class Pedido {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca la orden pendiente de pago para una mesa específica junto con sus platillos
     * Consulta trasladada desde views/servicio/cierre_cuenta.php
     *
     * @param int|string $idMesa ID de la mesa
     * @return array
     */
    public function obtenerCuentaActivaPorMesa($idMesa): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                pr.nombre AS nombre_producto,
                dp.cantidadPed,
                dp.precio_unitario,
                (dp.cantidadPed * dp.precio_unitario) AS subtotal_linea,
                p.id_pedido,
                p.fecha_hora,
                p.id_mesero,
                u.nombre_completo AS nombre_mesero
            FROM pedidos p
            INNER JOIN detalles_pedido dp ON p.id_pedido = dp.id_pedido
            INNER JOIN productos pr ON dp.codigo_producto = pr.codigo_producto
            LEFT JOIN usuarios u ON p.id_mesero = u.id_usuario
            WHERE p.id_mesa = ?
              AND (p.estado_pedido != 'Pagado' OR p.estado_pedido IS NULL)
              AND p.id_pedido NOT IN (SELECT id_cuenta_p.id_pedido FROM cuentas id_cuenta_p WHERE id_cuenta_p.id_pedido IS NOT NULL)
            ORDER BY dp.id_detalle ASC
        ");
        $stmt->execute([$idMesa]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula la cantidad de ventas (conteo de órdenes pagadas) y el total vendido
     * (sumatoria de totales de cuentas pagadas) para poblar los KPIs
     *
     * @return array ['cantidad_ventas' => int, 'total_vendido' => float]
     */
    public function obtenerEstadisticasVentas(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(c.id_cuenta) AS cantidad_ventas,
                COALESCE(SUM(c.total), 0) AS total_vendido
            FROM cuentas c
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        return [
            'cantidad_ventas' => (int)($stats['cantidad_ventas'] ?? 0),
            'total_vendido' => (float)($stats['total_vendido'] ?? 0.00)
        ];
    }

    /**
     * Agrupa y suma las cantidades vendidas por producto en órdenes finalizadas,
     * ordenándolas de mayor a menor para el top solicitado
     *
     * @param int $limite Número máximo de productos a retornar (por defecto 5)
     * @return array
     */
    public function obtenerProductosMasVendidos(int $limite = 5): array {
        $limite = max(1, (int)$limite);
        $stmt = $this->pdo->prepare("
            SELECT 
                pr.codigo_producto,
                pr.nombre,
                pr.precio,
                COALESCE(SUM(dp.cantidadPed), 0) AS total_cantidad
            FROM detalles_pedido dp
            INNER JOIN productos pr ON dp.codigo_producto = pr.codigo_producto
            INNER JOIN pedidos p ON dp.id_pedido = p.id_pedido
            WHERE p.estado_pedido = 'Pagado' OR p.id_pedido IN (SELECT id_cuenta_p.id_pedido FROM cuentas id_cuenta_p WHERE id_cuenta_p.id_pedido IS NOT NULL)
            GROUP BY pr.codigo_producto, pr.nombre, pr.precio
            ORDER BY total_cantidad DESC
            LIMIT $limite
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo pedido o actualiza uno activo para una mesa,
     * reemplazando los productos en detalles_pedido
     *
     * @param int $idMesa
     * @param array $productos
     * @param int $idMesero
     * @param string $estadoPedido
     * @return int ID del pedido insertado o actualizado
     */
    public function guardarOrden(int $idMesa, array $productos, int $idMesero = 1, string $estadoPedido = 'En preparación'): int {
        // 1. Buscar si ya existe un pedido activo no pagado para esta mesa
        $stmtPedidoActivo = $this->pdo->prepare("
            SELECT id_pedido 
            FROM pedidos 
            WHERE id_mesa = ? 
              AND (estado_pedido != 'Pagado' OR estado_pedido IS NULL)
              AND id_pedido NOT IN (SELECT id_cuenta_p.id_pedido FROM cuentas id_cuenta_p WHERE id_cuenta_p.id_pedido IS NOT NULL)
            ORDER BY id_pedido DESC 
            LIMIT 1
        ");
        $stmtPedidoActivo->execute([$idMesa]);
        $pedidoExistente = $stmtPedidoActivo->fetch();

        if ($pedidoExistente) {
            $idPedido = (int)$pedidoExistente['id_pedido'];
            $stmtUpdPed = $this->pdo->prepare("UPDATE pedidos SET estado_pedido = ?, id_mesero = ?, fecha_hora = NOW() WHERE id_pedido = ?");
            $stmtUpdPed->execute([$estadoPedido, $idMesero, $idPedido]);

            $stmtDelDet = $this->pdo->prepare("DELETE FROM detalles_pedido WHERE id_pedido = ?");
            $stmtDelDet->execute([$idPedido]);
        } else {
            $stmtNuevoPed = $this->pdo->prepare("INSERT INTO pedidos (id_mesa, id_mesero, estado_pedido, fecha_hora) VALUES (?, ?, ?, NOW())");
            $stmtNuevoPed->execute([$idMesa, $idMesero, $estadoPedido]);
            $idPedido = (int)$this->pdo->lastInsertId();
        }

        // 2. Insertar cada producto en detalles_pedido
        $stmtDetalle = $this->pdo->prepare("
            INSERT INTO detalles_pedido (id_pedido, codigo_producto, cantidadPed, precio_unitario) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($productos as $prod) {
            $codigoProd = isset($prod['id']) ? trim($prod['id']) : (isset($prod['codigo']) ? trim($prod['codigo']) : '');
            $cantidad = isset($prod['qty']) ? (int)$prod['qty'] : (isset($prod['cantidad']) ? (int)$prod['cantidad'] : 1);
            $precioUnit = isset($prod['price']) ? (float)$prod['price'] : (isset($prod['precio']) ? (float)$prod['precio'] : 0.00);

            if ($cantidad > 0 && !empty($codigoProd)) {
                $stmtDetalle->execute([$idPedido, $codigoProd, $cantidad, $precioUnit]);
            }
        }

        return $idPedido;
    }

    /**
     * Cierra la cuenta de un pedido activo registrando el pago en la tabla cuentas
     * y actualizando el estado del pedido a 'Pagado'
     *
     * @param int $idMesa
     * @param float $montoRecibido
     * @param int $idCajero
     * @return array Resumen financiero de la cuenta
     */
    public function cerrarCuenta(int $idMesa, float $montoRecibido = 0.00, int $idCajero = 1): array {
        // Buscar pedido activo
        $stmtPedido = $this->pdo->prepare("
            SELECT id_pedido 
            FROM pedidos 
            WHERE id_mesa = ? 
              AND (estado_pedido != 'Pagado' OR estado_pedido IS NULL)
              AND id_pedido NOT IN (SELECT id_cuenta_p.id_pedido FROM cuentas id_cuenta_p WHERE id_cuenta_p.id_pedido IS NOT NULL)
            ORDER BY id_pedido DESC 
            LIMIT 1
        ");
        $stmtPedido->execute([$idMesa]);
        $pedidoActivo = $stmtPedido->fetch();

        $idPedido = $pedidoActivo ? (int)$pedidoActivo['id_pedido'] : 0;
        $subtotal = 0.00;
        $propina = 0.00;
        $total = 0.00;
        $cambio = 0.00;

        if ($idPedido > 0) {
            $stmtTotal = $this->pdo->prepare("
                SELECT COALESCE(SUM(cantidadPed * precio_unitario), 0) AS subtotal
                FROM detalles_pedido
                WHERE id_pedido = ?
            ");
            $stmtTotal->execute([$idPedido]);
            $resSubtotal = $stmtTotal->fetch();
            $subtotal = (float)$resSubtotal['subtotal'];
            $propina = round($subtotal * 0.10, 2);
            $total = $subtotal + $propina;

            if ($montoRecibido <= 0 || $montoRecibido < $total) {
                $montoRecibido = $total;
                $cambio = 0.00;
            } else {
                $cambio = round($montoRecibido - $total, 2);
            }

            $stmtCuenta = $this->pdo->prepare("
                INSERT INTO cuentas (id_pedido, subtotal, propina, total, efectivo_recibido, cambio, fecha_hora_pago, id_cajero)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmtCuenta->execute([$idPedido, $subtotal, $propina, $total, $montoRecibido, $cambio, $idCajero]);

            $stmtUpdPed = $this->pdo->prepare("UPDATE pedidos SET estado_pedido = 'Pagado' WHERE id_pedido = ?");
            $stmtUpdPed->execute([$idPedido]);
        }

        return [
            'id_pedido' => $idPedido,
            'subtotal' => $subtotal,
            'propina' => $propina,
            'total' => $total,
            'cambio' => $cambio
        ];
    }
}

