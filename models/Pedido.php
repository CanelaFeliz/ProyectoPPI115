<?php
/**
 * ==============================================================================
 * NIXGARU – Modelo: Pedido
 * models/Pedido.php
 * Gestiona pedidos, comandas, detalles de órdenes y su cierre financiero en cuentas
 * ==============================================================================
 */

require_once __DIR__ . '/Cuenta.php';

class Pedido {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca la orden pendiente de pago para una mesa específica junto con sus platillos
     *
     * @param int|string $idMesa ID numérico o identificador alfanumérico de la mesa ('M-01', '1')
     * @return array
     */
    public function obtenerCuentaActivaPorMesa($idMesa): array {
        $idInt = is_numeric($idMesa) ? (int)$idMesa : 0;
        $idStr = (string)$idMesa;
        $idFormatted = is_numeric($idMesa) ? ('M-' . str_pad((string)$idMesa, 2, '0', STR_PAD_LEFT)) : $idStr;

        $stmt = $this->pdo->prepare("
            SELECT 
                pr.codigo_producto,
                pr.nombre AS nombre_producto,
                dp.cantidadPed,
                dp.precio_unitario,
                (dp.cantidadPed * dp.precio_unitario) AS subtotal_linea,
                p.id_pedido,
                p.fecha_hora,
                p.id_mesero,
                u.nombre_completo AS nombre_mesero,
                m.numero_mesa,
                m.id_mesa
            FROM pedidos p
            INNER JOIN detalles_pedido dp ON p.id_pedido = dp.id_pedido
            INNER JOIN productos pr ON dp.codigo_producto = pr.codigo_producto
            INNER JOIN mesas m ON p.id_mesa = m.id_mesa
            LEFT JOIN usuarios u ON p.id_mesero = u.id_usuario
            WHERE (m.id_mesa = :id_int OR m.numero_mesa = :id_str OR m.numero_mesa = :id_formatted OR p.id_mesa = :id_int2)
              AND (p.estado_pedido != 'Pagado' OR p.estado_pedido IS NULL)
              AND NOT EXISTS (SELECT 1 FROM cuentas c WHERE c.id_pedido = p.id_pedido)
            ORDER BY dp.id_detalle ASC
        ");

        $stmt->bindValue(':id_int', $idInt, PDO::PARAM_INT);
        $stmt->bindValue(':id_str', $idStr, PDO::PARAM_STR);
        $stmt->bindValue(':id_formatted', $idFormatted, PDO::PARAM_STR);
        $stmt->bindValue(':id_int2', $idInt, PDO::PARAM_INT);
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
                COALESCE(SUM(dp.cantidadPed * dp.precio_unitario), 0) AS consumo_subtotal
            FROM pedidos p
            INNER JOIN detalles_pedido dp ON p.id_pedido = dp.id_pedido
            WHERE (p.estado_pedido != 'Pagado' OR p.estado_pedido IS NULL)
              AND NOT EXISTS (SELECT 1 FROM cuentas c WHERE c.id_pedido = p.id_pedido)
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
     * Calcula métricas financieras consolidadas a partir de la tabla `cuentas`
     *
     * @return array ['cantidad_ventas' => int, 'total_vendido' => float]
     */
    public function obtenerEstadisticasVentas(): array {
        $cuentaModel = new Cuenta($this->pdo);
        return $cuentaModel->obtenerEstadisticas();
    }

    /**
     * Agrupa y suma las cantidades vendidas por producto en órdenes pagadas/cerradas
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
            WHERE p.estado_pedido = 'Pagado' 
               OR EXISTS (SELECT 1 FROM cuentas c WHERE c.id_pedido = p.id_pedido)
            GROUP BY pr.codigo_producto, pr.nombre, pr.precio
            ORDER BY total_cantidad DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo pedido o actualiza uno activo para una mesa,
     * reemplazando los productos en `detalles_pedido`
     *
     * @param int $idMesa ID numérico de la mesa
     * @param array $productos Arreglo de platillos [{codigo/id, cantidad/qty, precio/price}]
     * @param int $idMesero ID del usuario mesero
     * @param string $estadoPedido 'En preparación', 'Recibido', etc.
     * @return int ID del pedido guardado
     */
    public function guardarOrden(
        int $idMesa, 
        array $productos, 
        int $idMesero = 1, 
        string $estadoPedido = 'En preparación'
    ): int {
        // 1. Buscar si ya existe un pedido activo no pagado para esta mesa
        $stmtPedidoActivo = $this->pdo->prepare("
            SELECT id_pedido 
            FROM pedidos 
            WHERE id_mesa = :id_mesa 
              AND (estado_pedido != 'Pagado' OR estado_pedido IS NULL)
              AND NOT EXISTS (SELECT 1 FROM cuentas c WHERE c.id_pedido = pedidos.id_pedido)
            ORDER BY id_pedido DESC 
            LIMIT 1
        ");
        $stmtPedidoActivo->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
        $stmtPedidoActivo->execute();
        $pedidoExistente = $stmtPedidoActivo->fetch();

        if ($pedidoExistente) {
            $idPedido = (int)$pedidoExistente['id_pedido'];

            $stmtUpdPed = $this->pdo->prepare("
                UPDATE pedidos 
                SET estado_pedido = :estado, 
                    id_mesero = :mesero, 
                    fecha_hora = NOW() 
                WHERE id_pedido = :id_pedido
            ");
            $stmtUpdPed->bindValue(':estado', $estadoPedido, PDO::PARAM_STR);
            $stmtUpdPed->bindValue(':mesero', $idMesero, PDO::PARAM_INT);
            $stmtUpdPed->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtUpdPed->execute();

            $stmtDelDet = $this->pdo->prepare("DELETE FROM detalles_pedido WHERE id_pedido = :id_pedido");
            $stmtDelDet->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtDelDet->execute();
        } else {
            $stmtNuevoPed = $this->pdo->prepare("
                INSERT INTO pedidos (id_mesa, id_mesero, estado_pedido, fecha_hora) 
                VALUES (:id_mesa, :id_mesero, :estado, NOW())
            ");
            $stmtNuevoPed->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $stmtNuevoPed->bindValue(':id_mesero', $idMesero, PDO::PARAM_INT);
            $stmtNuevoPed->bindValue(':estado', $estadoPedido, PDO::PARAM_STR);
            $stmtNuevoPed->execute();
            $idPedido = (int)$this->pdo->lastInsertId();
        }

        // 2. Insertar cada producto en detalles_pedido con los campos y tipos normalizados
        $stmtDetalle = $this->pdo->prepare("
            INSERT INTO detalles_pedido (id_pedido, codigo_producto, cantidadPed, precio_unitario) 
            VALUES (:id_pedido, :codigo_producto, :cantidadPed, :precio_unitario)
        ");

        // Cache de precios para evitar consultas redundantes
        $preciosCache = [];

        foreach ($productos as $prod) {
            $codigoProd = isset($prod['id']) ? trim((string)$prod['id']) : (isset($prod['codigo']) ? trim((string)$prod['codigo']) : '');
            $cantidad = isset($prod['qty']) ? (int)$prod['qty'] : (isset($prod['cantidad']) ? (int)$prod['cantidad'] : (isset($prod['cantidadPed']) ? (int)$prod['cantidadPed'] : 1));
            $precioUnit = isset($prod['price']) ? (float)$prod['price'] : (isset($prod['precio']) ? (float)$prod['precio'] : (isset($prod['precio_unitario']) ? (float)$prod['precio_unitario'] : 0.00));

            if ($cantidad > 0 && !empty($codigoProd)) {
                // Si el precio unitario no vino en la petición o es 0, consultarlo en el catálogo
                if ($precioUnit <= 0) {
                    if (!isset($preciosCache[$codigoProd])) {
                        $stmtPrecio = $this->pdo->prepare("SELECT precio FROM productos WHERE codigo_producto = :cod LIMIT 1");
                        $stmtPrecio->bindValue(':cod', $codigoProd, PDO::PARAM_STR);
                        $stmtPrecio->execute();
                        $preciosCache[$codigoProd] = (float)($stmtPrecio->fetchColumn() ?: 0.00);
                    }
                    $precioUnit = $preciosCache[$codigoProd];
                }

                $stmtDetalle->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':codigo_producto', $codigoProd, PDO::PARAM_STR);
                $stmtDetalle->bindValue(':cantidadPed', $cantidad, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':precio_unitario', number_format($precioUnit, 2, '.', ''), PDO::PARAM_STR);
                $stmtDetalle->execute();
            }
        }

        return $idPedido;
    }

    /**
     * Cierra la cuenta de un pedido activo registrando el pago normalizado en la tabla `cuentas`
     * y actualizando el estado de la orden a 'Pagado' en la tabla `pedidos`
     *
     * @param int $idMesa ID de la mesa
     * @param float $montoRecibido Dinero en efectivo recibido
     * @param int $idCajero ID del usuario que procesa el cobro
     * @param int|null $idPedidoDirecto ID de pedido explícito opcional
     * @return array Resumen financiero de la cuenta
     */
    public function cerrarCuenta(int $idMesa, float $montoRecibido = 0.00, int $idCajero = 1, ?int $idPedidoDirecto = null): array {
        $idPedido = 0;

        if ($idPedidoDirecto && $idPedidoDirecto > 0) {
            $idPedido = $idPedidoDirecto;
        } else {
            // Buscar pedido activo pendiente de pago para la mesa
            $stmtPedido = $this->pdo->prepare("
                SELECT id_pedido 
                FROM pedidos 
                WHERE id_mesa = :id_mesa 
                  AND (estado_pedido != 'Pagado' OR estado_pedido IS NULL)
                  AND NOT EXISTS (SELECT 1 FROM cuentas c WHERE c.id_pedido = pedidos.id_pedido)
                ORDER BY id_pedido DESC 
                LIMIT 1
            ");
            $stmtPedido->bindValue(':id_mesa', $idMesa, PDO::PARAM_INT);
            $stmtPedido->execute();
            $pedidoActivo = $stmtPedido->fetch();
            $idPedido = $pedidoActivo ? (int)$pedidoActivo['id_pedido'] : 0;
        }

        $idCuenta = 0;
        $subtotal = 0.00;
        $propina = 0.00;
        $total = 0.00;
        $cambio = 0.00;

        if ($idPedido > 0) {
            // Calcular el subtotal sumando (cantidadPed * precio_unitario) de los detalles
            $stmtTotal = $this->pdo->prepare("
                SELECT COALESCE(SUM(cantidadPed * precio_unitario), 0) AS subtotal
                FROM detalles_pedido
                WHERE id_pedido = :id_pedido
            ");
            $stmtTotal->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtTotal->execute();
            $resSubtotal = $stmtTotal->fetch();

            $subtotal = (float)$resSubtotal['subtotal'];
            $propina = round($subtotal * 0.10, 2);
            $total = round($subtotal + $propina, 2);

            if ($montoRecibido <= 0 || $montoRecibido < $total) {
                $montoRecibido = $total;
                $cambio = 0.00;
            } else {
                $cambio = round($montoRecibido - $total, 2);
            }

            // Registrar el cobro en la tabla cuentas
            $cuentaModel = new Cuenta($this->pdo);
            $idCuenta = $cuentaModel->registrar(
                $idPedido, 
                $subtotal, 
                $propina, 
                $total, 
                $montoRecibido, 
                $cambio, 
                $idCajero
            );

            // Actualizar el estado del pedido a 'Pagado'
            $stmtUpdPed = $this->pdo->prepare("
                UPDATE pedidos 
                SET estado_pedido = 'Pagado' 
                WHERE id_pedido = :id_pedido
            ");
            $stmtUpdPed->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtUpdPed->execute();
        }

        return [
            'id_pedido' => $idPedido,
            'id_cuenta' => $idCuenta,
            'subtotal' => $subtotal,
            'propina' => $propina,
            'total' => $total,
            'efectivo_recibido' => $montoRecibido,
            'cambio' => $cambio
        ];
    }
}
