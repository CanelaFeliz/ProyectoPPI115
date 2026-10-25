<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Cuenta (Wrapper de compatibilidad sobre `Pedidos`)
 * models/Cuenta.php
 * En nixgaru_db, los datos financieros del cierre se almacenan directamente
 * en la tabla `Pedidos` (porcentaje_propina, total_pagar, metodo_pago, estado_pedido)
 * ==============================================================================
 */

class Cuenta {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registra el cierre financiero de un pedido directamente en la tabla `Pedidos`
     */
    public function registrar(
        int $idPedido, 
        float $subtotal, 
        float $propina, 
        float $totalPagar, 
        float $efectivoRecibido = 0.00, 
        float $cambio = 0.00, 
        int $idUsuario = 3,
        string $metodoPago = 'Efectivo'
    ): int {
        $porcentajePropina = $subtotal > 0 ? round(($propina / $subtotal) * 100, 2) : 10.00;

        $stmt = $this->pdo->prepare("
            UPDATE Pedidos 
            SET estado_pedido = 'Pagado',
                porcentaje_propina = :porcentaje_propina,
                total_pagar = :total_pagar,
                metodo_pago = :metodo_pago
            WHERE id_pedido = :id_pedido
        ");
        $stmt->bindValue(':porcentaje_propina', number_format($porcentajePropina, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':total_pagar', number_format($totalPagar, 2, '.', ''), PDO::PARAM_STR);
        $stmt->bindValue(':metodo_pago', trim($metodoPago), PDO::PARAM_STR);
        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmt->execute();

        return $idPedido;
    }

    /**
     * Obtiene un pedido pagado por su id_pedido
     */
    public function obtenerPorId(int $idPedido) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id_pedido,
                p.fecha_hora,
                p.estado_pedido,
                p.porcentaje_propina,
                p.total_pagar,
                p.metodo_pago,
                p.id_mesa,
                p.id_usuario,
                u.nombre_completo,
                m.numero_mesa
            FROM Pedidos p
            INNER JOIN Usuarios u ON p.id_usuario = u.id_usuario
            INNER JOIN Mesas m ON p.id_mesa = m.id_mesa
            WHERE p.id_pedido = :id_pedido
            LIMIT 1
        ");
        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Alias para obtener por id_pedido
     */
    public function obtenerPorPedido(int $idPedido) {
        return $this->obtenerPorId($idPedido);
    }

    /**
     * Calcula métricas financieras consolidadas desde la tabla `Pedidos`
     */
    public function obtenerEstadisticas(): array {
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
     * Obtiene el historial reciente de pedidos pagados desde `Pedidos`
     */
    public function obtenerHistorial(int $limite = 50): array {
        $limite = max(1, (int)$limite);
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id_pedido,
                p.fecha_hora,
                p.estado_pedido,
                p.porcentaje_propina,
                p.total_pagar,
                p.metodo_pago,
                p.id_mesa,
                p.id_usuario,
                u.nombre_completo,
                m.numero_mesa
            FROM Pedidos p
            INNER JOIN Usuarios u ON p.id_usuario = u.id_usuario
            INNER JOIN Mesas m ON p.id_mesa = m.id_mesa
            WHERE p.estado_pedido = 'Pagado'
            ORDER BY p.fecha_hora DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
