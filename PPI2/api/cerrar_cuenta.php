<?php
/**
 * ==============================================================================
 * API: Cerrar Cuenta y Liberar Mesa (api/cerrar_cuenta.php)
 * Registra el pago en la tabla `cuentas`, actualiza el estado del pedido a 'Pagado'
 * y cambia el estado de la mesa a 'Disponible' en `nixgaru_db`.
 * ==============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo JSON o POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$idMesa = isset($data['id_mesa']) ? (int)$data['id_mesa'] : 0;
$montoRecibido = isset($data['monto_recibido']) ? (float)$data['monto_recibido'] : 0.00;
$idCajero = isset($data['id_cajero']) ? (int)$data['id_cajero'] : 1;

if ($idMesa <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de mesa no válido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener id_mesa real de la tabla mesas
    $stmtMesa = $pdo->prepare("SELECT id_mesa, numero_mesa FROM mesas WHERE id_mesa = ? OR numero_mesa = ? LIMIT 1");
    $stmtMesa->execute([$idMesa, (string)$idMesa]);
    $mesaDb = $stmtMesa->fetch();

    if (!$mesaDb) {
        throw new Exception("Mesa no encontrada en la base de datos.");
    }
    $mesaRealId = (int)$mesaDb['id_mesa'];

    // 2. Buscar pedidos activos no pagados para esta mesa
    $stmtPedido = $pdo->prepare("
        SELECT id_pedido 
        FROM pedidos 
        WHERE id_mesa = ? 
          AND (estado_pedido != 'Pagado' OR estado_pedido IS NULL)
          AND id_pedido NOT IN (SELECT id_cuenta_p.id_pedido FROM cuentas id_cuenta_p WHERE id_cuenta_p.id_pedido IS NOT NULL)
        ORDER BY id_pedido DESC 
        LIMIT 1
    ");
    $stmtPedido->execute([$mesaRealId]);
    $pedidoActivo = $stmtPedido->fetch();

    $idPedido = $pedidoActivo ? (int)$pedidoActivo['id_pedido'] : 0;
    $subtotal = 0.00;
    $propina = 0.00;
    $total = 0.00;
    $cambio = 0.00;

    if ($idPedido > 0) {
        // Calcular subtotal de los consumos
        $stmtTotal = $pdo->prepare("
            SELECT COALESCE(SUM(cantidadPed * precio_unitario), 0) AS subtotal
            FROM detalles_pedido
            WHERE id_pedido = ?
        ");
        $stmtTotal->execute([$idPedido]);
        $resSubtotal = $stmtTotal->fetch();
        $subtotal = (float)$resSubtotal['subtotal'];
        $propina = round($subtotal * 0.10, 2);
        $total = $subtotal + $propina;

        // Si el monto recibido es menor al total, o 0, ajustar con el total exacto
        if ($montoRecibido <= 0 || $montoRecibido < $total) {
            $montoRecibido = $total;
            $cambio = 0.00;
        } else {
            $cambio = round($montoRecibido - $total, 2);
        }

        // 3. Insertar registro en tabla cuentas
        $stmtCuenta = $pdo->prepare("
            INSERT INTO cuentas (id_pedido, subtotal, propina, total, efectivo_recibido, cambio, fecha_hora_pago, id_cajero)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmtCuenta->execute([$idPedido, $subtotal, $propina, $total, $montoRecibido, $cambio, $idCajero]);

        // 4. Actualizar el estado del pedido a 'Pagado'
        $stmtUpdPed = $pdo->prepare("UPDATE pedidos SET estado_pedido = 'Pagado' WHERE id_pedido = ?");
        $stmtUpdPed->execute([$idPedido]);
    }

    // 5. Actualizar el estado de la mesa a 'Disponible'
    $stmtUpdMesa = $pdo->prepare("UPDATE mesas SET estado = 'Disponible' WHERE id_mesa = ?");
    $stmtUpdMesa->execute([$mesaRealId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id_mesa' => $mesaRealId,
        'id_pedido' => $idPedido,
        'subtotal' => $subtotal,
        'propina' => $propina,
        'total' => $total,
        'cambio' => $cambio,
        'message' => 'Cuenta cerrada con éxito y mesa liberada como Disponible'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => 'Error al cerrar cuenta: ' . $e->getMessage()
    ]);
}

