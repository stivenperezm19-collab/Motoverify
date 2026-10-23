<?php
class VentaModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registra una nueva venta usando transacciones
    public function crearVenta($id_vendedor, $id_cliente, $subtotal, $impuestos, $total, $detalles) {
        // Iniciar transacción
        $this->conn->begin_transaction();

        try {
            // 1. Insertar la Venta
            $queryVenta = "INSERT INTO VENTA (id_vendedor, id_cliente, subtotal, impuestos, total, estado) VALUES (?, ?, ?, ?, ?, 'Completada')";
            $stmtVenta = $this->conn->prepare($queryVenta);
            $stmtVenta->bind_param("iiddd", $id_vendedor, $id_cliente, $subtotal, $impuestos, $total);
            
            if (!$stmtVenta->execute()) {
                throw new Exception("Error al insertar la cabecera de la venta.");
            }
            $id_venta = $stmtVenta->insert_id;
            $stmtVenta->close();

            // 2. Insertar los detalles y actualizar stock
            $queryDetalle = "INSERT INTO DETALLE_VENTA (id_venta, id_repuesto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmtDetalle = $this->conn->prepare($queryDetalle);

            $queryStock = "UPDATE REPUESTO SET stock = stock - ? WHERE id_repuesto = ? AND stock >= ?";
            $stmtStock = $this->conn->prepare($queryStock);

            foreach ($detalles as $detalle) {
                $id_repuesto = $detalle['id_repuesto'];
                $cantidad = $detalle['cantidad'];
                $precio = $detalle['precio'];
                $subtotal_detalle = $cantidad * $precio;

                // Insertar detalle
                $stmtDetalle->bind_param("iiidd", $id_venta, $id_repuesto, $cantidad, $precio, $subtotal_detalle);
                if (!$stmtDetalle->execute()) {
                    throw new Exception("Error al insertar el detalle del repuesto ID: " . $id_repuesto);
                }

                // Descontar stock
                $stmtStock->bind_param("iii", $cantidad, $id_repuesto, $cantidad);
                $stmtStock->execute();
                
                // Si affected_rows es 0, significa que no había stock suficiente
                if ($stmtStock->affected_rows === 0) {
                    throw new Exception("Stock insuficiente para el repuesto ID: " . $id_repuesto);
                }
            }

            $stmtDetalle->close();
            $stmtStock->close();

            // 3. Confirmar la transacción
            $this->conn->commit();
            return $id_venta;

        } catch (Exception $e) {
            // Revertir si algo falló
            $this->conn->rollback();
            // Retornar mensaje de error internamente si se requiere, o false
            return false;
        }
    }

    public function getHistorial($id_vendedor = null) {
        $query = "SELECT v.id_venta, v.fecha, v.total, v.estado, c.nombre as cliente, a.usuario as vendedor 
                  FROM VENTA v 
                  LEFT JOIN CLIENTE c ON v.id_cliente = c.id_cliente 
                  LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador ";
        
        if ($id_vendedor !== null) {
            $query .= " WHERE v.id_vendedor = ? ";
        }
        $query .= " ORDER BY v.fecha DESC";

        $stmt = $this->conn->prepare($query);
        if ($id_vendedor !== null) {
            $stmt->bind_param("i", $id_vendedor);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $ventas = [];
        while ($row = $result->fetch_assoc()) {
            $ventas[] = $row;
        }
        $stmt->close();
        return $ventas;
    }

    public function getDetalles($id_venta) {
        $query = "SELECT d.cantidad, d.precio_unitario, d.subtotal, r.nombre, r.codigo_interno 
                  FROM DETALLE_VENTA d 
                  INNER JOIN REPUESTO r ON d.id_repuesto = r.id_repuesto 
                  WHERE d.id_venta = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = [];
        while ($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        $stmt->close();
        return $detalles;
    }

    public function anularVenta($id_venta) {
        $this->conn->begin_transaction();
        try {
            // Check current status
            $check = $this->conn->prepare("SELECT estado FROM VENTA WHERE id_venta = ?");
            $check->bind_param("i", $id_venta);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("La venta no existe.");
            }
            $venta = $result->fetch_assoc();
            if ($venta['estado'] === 'Anulada') {
                throw new Exception("La venta ya ha sido anulada.");
            }
            $check->close();

            // Get details to restore stock
            $detalles = $this->getDetallesRaw($id_venta);

            $queryStock = "UPDATE REPUESTO SET stock = stock + ? WHERE id_repuesto = ?";
            $stmtStock = $this->conn->prepare($queryStock);

            foreach ($detalles as $detalle) {
                $stmtStock->bind_param("ii", $detalle['cantidad'], $detalle['id_repuesto']);
                $stmtStock->execute();
            }
            $stmtStock->close();

            // Mark as Anulada
            $update = $this->conn->prepare("UPDATE VENTA SET estado = 'Anulada' WHERE id_venta = ?");
            $update->bind_param("i", $id_venta);
            $update->execute();
            $update->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    private function getDetallesRaw($id_venta) {
        $query = "SELECT id_repuesto, cantidad, precio_unitario FROM DETALLE_VENTA WHERE id_venta = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = [];
        while ($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        $stmt->close();
        return $detalles;
    }

    public function modificarVenta($id_venta, $nuevas_cantidades) {
        $this->conn->begin_transaction();
        try {
            // Validar estado
            $check = $this->conn->prepare("SELECT estado FROM VENTA WHERE id_venta = ?");
            $check->bind_param("i", $id_venta);
            $check->execute();
            $resCheck = $check->get_result();
            if ($resCheck->num_rows === 0) throw new Exception("Venta no existe.");
            $estado = $resCheck->fetch_assoc()['estado'];
            if ($estado === 'Anulada') throw new Exception("No se puede modificar una venta anulada.");
            $check->close();

            $viejos = $this->getDetallesRaw($id_venta);
            
            $stmtUpdateDetalle = $this->conn->prepare("UPDATE DETALLE_VENTA SET cantidad = ?, subtotal = ? WHERE id_venta = ? AND id_repuesto = ?");
            $stmtRestarStock = $this->conn->prepare("UPDATE REPUESTO SET stock = stock - ? WHERE id_repuesto = ? AND stock >= ?");
            $stmtSumarStock = $this->conn->prepare("UPDATE REPUESTO SET stock = stock + ? WHERE id_repuesto = ?");

            $nuevoSubtotalVenta = 0;

            foreach ($viejos as $viejo) {
                $id_repuesto = $viejo['id_repuesto'];
                $vieja_cant = $viejo['cantidad'];
                $precio = $viejo['precio_unitario'];
                
                $nueva_cant = isset($nuevas_cantidades[$id_repuesto]) ? intval($nuevas_cantidades[$id_repuesto]) : $vieja_cant;
                if ($nueva_cant < 1) $nueva_cant = 1; // Mínimo 1 para modificar (0 = anular)

                if ($nueva_cant > $vieja_cant) {
                    $diff = $nueva_cant - $vieja_cant;
                    $stmtRestarStock->bind_param("iii", $diff, $id_repuesto, $diff);
                    $stmtRestarStock->execute();
                    if ($stmtRestarStock->affected_rows === 0) {
                        throw new Exception("Stock insuficiente para aumentar cantidad.");
                    }
                } else if ($nueva_cant < $vieja_cant) {
                    $diff = $vieja_cant - $nueva_cant;
                    $stmtSumarStock->bind_param("ii", $diff, $id_repuesto);
                    $stmtSumarStock->execute();
                }

                // Actualizar detalle
                $nuevoSubtotalDetalle = $nueva_cant * $precio;
                $stmtUpdateDetalle->bind_param("idii", $nueva_cant, $nuevoSubtotalDetalle, $id_venta, $id_repuesto);
                $stmtUpdateDetalle->execute();

                $nuevoSubtotalVenta += $nuevoSubtotalDetalle;
            }

            $impuestos = $nuevoSubtotalVenta * 0.19;
            $total = $nuevoSubtotalVenta + $impuestos;

            $stmtUpdateVenta = $this->conn->prepare("UPDATE VENTA SET subtotal = ?, impuestos = ?, total = ? WHERE id_venta = ?");
            $stmtUpdateVenta->bind_param("dddi", $nuevoSubtotalVenta, $impuestos, $total, $id_venta);
            $stmtUpdateVenta->execute();
            $stmtUpdateVenta->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Registra un pedido realizado directamente por un Cliente en la tienda virtual
     * @param int $id_cliente ID del cliente autenticado
     * @param float $subtotal
     * @param float $impuestos
     * @param float $total
     * @param array $detalles Repuestos, cantidades y precios
     * @param array $envio Datos de envío (direccion_envio, ciudad_envio, telefono_contacto, metodo_pago, notas)
     * @return int|false ID de la venta/pedido o false en error
     */
    public function crearPedidoCliente($id_cliente, $subtotal, $impuestos, $total, $detalles, $envio) {
        $this->conn->begin_transaction();

        try {
            $tipo_venta = 'Online';
            $estado = 'Completada';
            $estado_pedido = 'Pendiente';
            $metodo_pago = !empty($envio['metodo_pago']) ? $envio['metodo_pago'] : 'Contra Entrega';
            $direccion = $envio['direccion_envio'] ?? '';
            $ciudad = $envio['ciudad_envio'] ?? '';
            $telefono = $envio['telefono_contacto'] ?? '';
            $notas = $envio['notas'] ?? '';

            // 1. Insertar la cabecera del pedido (id_vendedor es NULL en compras online del cliente)
            $queryVenta = "INSERT INTO VENTA (id_vendedor, id_cliente, subtotal, impuestos, total, estado, tipo_venta, metodo_pago, direccion_envio, ciudad_envio, telefono_contacto, estado_pedido, notas) 
                           VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtVenta = $this->conn->prepare($queryVenta);
            if (!$stmtVenta) {
                throw new Exception("Error al preparar venta: " . $this->conn->error);
            }

            $stmtVenta->bind_param("idddssssssss", 
                $id_cliente, 
                $subtotal, 
                $impuestos, 
                $total, 
                $estado, 
                $tipo_venta, 
                $metodo_pago, 
                $direccion, 
                $ciudad, 
                $telefono, 
                $estado_pedido, 
                $notas
            );

            if (!$stmtVenta->execute()) {
                throw new Exception("Error al insertar pedido: " . $stmtVenta->error);
            }
            $id_venta = $stmtVenta->insert_id;
            $stmtVenta->close();

            // 2. Insertar detalles y descontar stock atómicamente
            $queryDetalle = "INSERT INTO DETALLE_VENTA (id_venta, id_repuesto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmtDetalle = $this->conn->prepare($queryDetalle);

            $queryStock = "UPDATE REPUESTO SET stock = stock - ? WHERE id_repuesto = ? AND stock >= ?";
            $stmtStock = $this->conn->prepare($queryStock);

            foreach ($detalles as $item) {
                $id_repuesto = (int)$item['id_repuesto'];
                $cantidad = (int)$item['cantidad'];
                $precio = (float)$item['precio'];
                $subtotal_item = $cantidad * $precio;

                // Descontar stock primero asegurando disponibilidad
                $stmtStock->bind_param("iii", $cantidad, $id_repuesto, $cantidad);
                $stmtStock->execute();

                if ($stmtStock->affected_rows === 0) {
                    throw new Exception("Stock insuficiente para el repuesto ID: " . $id_repuesto);
                }

                // Insertar detalle
                $stmtDetalle->bind_param("iiidd", $id_venta, $id_repuesto, $cantidad, $precio, $subtotal_item);
                if (!$stmtDetalle->execute()) {
                    throw new Exception("Error al guardar detalle del producto: " . $stmtDetalle->error);
                }
            }

            $stmtDetalle->close();
            $stmtStock->close();

            $this->conn->commit();
            return $id_venta;

        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Obtiene todos los pedidos de un cliente específico
     * @param int $id_cliente
     * @return array
     */
    public function getPedidosByCliente($id_cliente) {
        $query = "SELECT v.id_venta, v.fecha, v.subtotal, v.impuestos, v.total, 
                         v.estado, v.tipo_venta, v.metodo_pago, v.direccion_envio, 
                         v.ciudad_envio, v.telefono_contacto, v.estado_pedido, v.notas,
                         COUNT(d.id_detalle) as total_items
                  FROM VENTA v
                  LEFT JOIN DETALLE_VENTA d ON v.id_venta = d.id_venta
                  WHERE v.id_cliente = ?
                  GROUP BY v.id_venta
                  ORDER BY v.fecha DESC";

        $pedidos = [];
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("i", $id_cliente);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $pedidos[] = $row;
            }
            $stmt->close();
        }
        return $pedidos;
    }

    /**
     * Obtiene el detalle completo de un pedido con verificación de pertenencia al cliente
     * @param int $id_venta ID de la venta
     * @param int $id_cliente ID del cliente para seguridad (evita acceso a pedidos ajenos)
     * @return array|null Datos de la venta con sus detalles, o null si no pertenece al cliente
     */
    public function getPedidoDetalleCliente($id_venta, $id_cliente) {
        // 1. Obtener la cabecera verificando pertenencia estricta
        $queryVenta = "SELECT v.*, c.nombre as cliente_nombre, c.correo as cliente_correo, c.telefono as cliente_telefono
                       FROM VENTA v
                       JOIN CLIENTE c ON v.id_cliente = c.id_cliente
                       WHERE v.id_venta = ? AND v.id_cliente = ?";
        
        $pedido = null;
        if ($stmt = $this->conn->prepare($queryVenta)) {
            $stmt->bind_param("ii", $id_venta, $id_cliente);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 1) {
                $pedido = $res->fetch_assoc();
            }
            $stmt->close();
        }

        if (!$pedido) {
            return null; // Acceso no autorizado o venta inexistente
        }

        // 2. Obtener los productos comprados con fotos y marcas
        $queryDetalles = "SELECT d.id_detalle, d.id_repuesto, d.cantidad, d.precio_unitario, d.subtotal,
                                 r.nombre, r.codigo_interno, r.codigo_barras, r.referencia_fabricante,
                                 m.nombre as marca_nombre,
                                 i.ruta_archivo as imagen_url
                          FROM DETALLE_VENTA d
                          JOIN REPUESTO r ON d.id_repuesto = r.id_repuesto
                          LEFT JOIN MARCA m ON r.id_marca = m.id_marca
                          LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
                          LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
                          WHERE d.id_venta = ?";

        $detalles = [];
        if ($stmtD = $this->conn->prepare($queryDetalles)) {
            $stmtD->bind_param("i", $id_venta);
            $stmtD->execute();
            $resD = $stmtD->get_result();
            while ($rowD = $resD->fetch_assoc()) {
                $detalles[] = $rowD;
            }
            $stmtD->close();
        }

        $pedido['detalles'] = $detalles;
        return $pedido;
    }
}
?>
