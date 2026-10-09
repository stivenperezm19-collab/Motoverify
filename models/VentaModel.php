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
}
?>
