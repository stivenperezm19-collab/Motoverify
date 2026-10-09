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
}
?>
