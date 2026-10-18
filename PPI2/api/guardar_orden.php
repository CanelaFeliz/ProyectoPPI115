<?php
/**
 * ==============================================================================
 * API: Guardar Orden en Base de Datos (api/guardar_orden.php)
 * Inserta pedido en `pedidos` y detalles en `detalles_pedido`,
 * y actualiza el estado de la mesa a 'En proceso'.
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
$productos = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];
$idMesero = isset($data['id_mesero']) ? (int)$data['id_mesero'] : 1;
$estadoMesa = isset($data['estado_mesa']) ? trim($data['estado_mesa']) : 'En proceso';
$estadoPedido = isset($data['estado_pedido']) ? trim($data['estado_pedido']) : 'En preparación';

if ($idMesa <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de mesa no válido']);
    exit;
}

if (empty($productos)) {
    echo json_encode(['success' => false, 'error' => 'No hay productos en la orden']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener el id_mesa real de la tabla mesas (buscando por id_mesa o numero_mesa)
    $stmtMesa = $pdo->prepare("SELECT id_mesa FROM mesas WHERE id_mesa = ? OR numero_mesa = ? LIMIT 1");
    $stmtMesa->execute([$idMesa, (string)$idMesa]);
    $mesaDb = $stmtMesa->fetch();

    if (!$mesaDb) {
        $stmtInsertMesa = $pdo->prepare("INSERT INTO mesas (id_mesa, numero_mesa, capacidad, estado) VALUES (?, ?, 4, ?)");
        $stmtInsertMesa->execute([$idMesa, (string)$idMesa, $estadoMesa]);
        $mesaRealId = $idMesa;
    } else {
        $mesaRealId = (int)$mesaDb['id_mesa'];
        $stmtUpdateMesa = $pdo->prepare("UPDATE mesas SET estado = ? WHERE id_mesa = ?");
        $stmtUpdateMesa->execute([$estadoMesa, $mesaRealId]);
    }

    // 2. Verificar si ya existe un pedido activo no pagado para esta mesa
    $stmtPedidoActivo = $pdo->prepare("
        SELECT id_pedido 
        FROM pedidos 
        WHERE id_mesa = ? 
          AND (estado_pedido != 'Pagado' OR estado_pedido IS NULL)
          AND id_pedido NOT IN (SELECT id_cuenta_p.id_pedido FROM cuentas id_cuenta_p WHERE id_cuenta_p.id_pedido IS NOT NULL)
        ORDER BY id_pedido DESC 
        LIMIT 1
    ");
    $stmtPedidoActivo->execute([$mesaRealId]);
    $pedidoExistente = $stmtPedidoActivo->fetch();

    if ($pedidoExistente) {
        $idPedido = (int)$pedidoExistente['id_pedido'];
        // Actualizar pedido existente
        $stmtUpdPed = $pdo->prepare("UPDATE pedidos SET estado_pedido = ?, id_mesero = ?, fecha_hora = NOW() WHERE id_pedido = ?");
        $stmtUpdPed->execute([$estadoPedido, $idMesero, $idPedido]);

        // Reemplazar detalles para mantener sincronizado el carrito exacto
        $stmtDelDet = $pdo->prepare("DELETE FROM detalles_pedido WHERE id_pedido = ?");
        $stmtDelDet->execute([$idPedido]);
    } else {
        // Crear nuevo pedido
        $stmtNuevoPed = $pdo->prepare("INSERT INTO pedidos (id_mesa, id_mesero, estado_pedido, fecha_hora) VALUES (?, ?, ?, NOW())");
        $stmtNuevoPed->execute([$mesaRealId, $idMesero, $estadoPedido]);
        $idPedido = (int)$pdo->lastInsertId();
    }

    // 3. Insertar cada producto en detalles_pedido
    $stmtDetalle = $pdo->prepare("
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

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id_pedido' => $idPedido,
        'id_mesa' => $mesaRealId,
        'message' => 'Orden guardada y enviada a cocina con éxito'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar la orden: ' . $e->getMessage()
    ]);
}

