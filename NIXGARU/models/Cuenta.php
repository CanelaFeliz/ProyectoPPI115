<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Cuenta
 * models/Cuenta.php
 * Gestiona los registros financieros y cobros de órdenes en la tabla `cuentas`
 * ==============================================================================
 */

class Cuenta {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Inserta un nuevo registro de cobro/facturación en la tabla cuentas
     *
     * @param int $idPedido ID del pedido asociado
     * @param float $subtotal Monto base de consumo sin propina
     * @param float $propina Monto de propina calculada o voluntaria
     * @param float $total Monto total facturado (subtotal + propina)
     * @param float $efectivoRecibido Dinero recibido del cliente
     * @param float $cambio Cambio devuelto al cliente
     * @param int $idCajero ID del usuario con rol de cajero/administrador que procesa el pago
     * @return int ID de la cuenta creada
     */
    public function registrar(
        int $idPedido, 
        float $subtotal, 
        float $propina, 
        float $total, 
        float $efectivoRecibido, 
        float $cambio, 
        int $idCajero
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO cuentas (
                id_pedido, 
                subtotal, 
                propina, 
                total, 
                efectivo_recibido, 
                cambio, 
                fecha_hora_pago, 
                id_cajero
            ) VALUES (
                :id_pedido, 
                :subtotal, 
                :propina, 
                :total, 
                :efectivo_recibido, 
                :cambio, 
                NOW(), 
                :id_cajero
            )
        ");

        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmt->bindValue(':subtotal', number_format($subtotal, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':propina', number_format($propina, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':total', number_format($total, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':efectivo_recibido', number_format($efectivoRecibido, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':cambio', number_format($cambio, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':id_cajero', $idCajero, PDO::PARAM_INT);

        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Obtiene los datos de una cuenta por su ID con información del cajero
     *
     * @param int $idCuenta
     * @return array|false
     */
    public function obtenerPorId(int $idCuenta) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id_cuenta,
                c.id_pedido,
                c.subtotal,
                c.propina,
                c.total,
                c.efectivo_recibido,
                c.cambio,
                c.fecha_hora_pago,
                c.id_cajero,
                u.nombre_completo AS nombre_cajero,
                p.id_mesa
            FROM cuentas c
            INNER JOIN usuarios u ON c.id_cajero = u.id_usuario
            INNER JOIN pedidos p ON c.id_pedido = p.id_pedido
            WHERE c.id_cuenta = :id_cuenta
            LIMIT 1
        ");
        $stmt->bindValue(':id_cuenta', $idCuenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtiene la cuenta asociada a un pedido específico
     *
     * @param int $idPedido
     * @return array|false
     */
    public function obtenerPorPedido(int $idPedido) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id_cuenta,
                c.id_pedido,
                c.subtotal,
                c.propina,
                c.total,
                c.efectivo_recibido,
                c.cambio,
                c.fecha_hora_pago,
                c.id_cajero,
                u.nombre_completo AS nombre_cajero
            FROM cuentas c
            INNER JOIN usuarios u ON c.id_cajero = u.id_usuario
            WHERE c.id_pedido = :id_pedido
            ORDER BY c.id_cuenta DESC
            LIMIT 1
        ");
        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Calcula métricas financieras consolidadas (KPIs)
     *
     * @return array ['cantidad_ventas' => int, 'total_vendido' => float]
     */
    public function obtenerEstadisticas(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(id_cuenta) AS cantidad_ventas,
                COALESCE(SUM(total), 0) AS total_vendido
            FROM cuentas
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        return [
            'cantidad_ventas' => (int)($stats['cantidad_ventas'] ?? 0),
            'total_vendido' => (float)($stats['total_vendido'] ?? 0.00)
        ];
    }

    /**
     * Obtiene el historial reciente de cuentas finalizadas
     *
     * @param int $limite
     * @return array
     */
    public function obtenerHistorial(int $limite = 50): array {
        $limite = max(1, (int)$limite);
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id_cuenta,
                c.id_pedido,
                c.subtotal,
                c.propina,
                c.total,
                c.efectivo_recibido,
                c.cambio,
                c.fecha_hora_pago,
                c.id_cajero,
                u.nombre_completo AS nombre_cajero,
                m.numero_mesa
            FROM cuentas c
            INNER JOIN usuarios u ON c.id_cajero = u.id_usuario
            INNER JOIN pedidos p ON c.id_pedido = p.id_pedido
            INNER JOIN mesas m ON p.id_mesa = m.id_mesa
            ORDER BY c.fecha_hora_pago DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

