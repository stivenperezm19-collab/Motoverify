
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
            $estado_pago = 'Pendiente';
            $metodo_pago = !empty($envio['metodo_pago']) ? $envio['metodo_pago'] : 'Contra Entrega';
            $direccion = $envio['direccion_envio'] ?? '';
            $ciudad = $envio['ciudad_envio'] ?? '';
            $telefono = $envio['telefono_contacto'] ?? '';
            $notas = $envio['notas'] ?? '';

            // 1. Insertar la cabecera del pedido (id_vendedor es NULL en el registro inicial por el cliente)
            $queryVenta = "INSERT INTO VENTA (id_vendedor, id_cliente, subtotal, impuestos, total, estado, tipo_venta, metodo_pago, direccion_envio, ciudad_envio, telefono_contacto, estado_pedido, estado_pago, notas) 
                           VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtVenta = $this->conn->prepare($queryVenta);
            if (!$stmtVenta) {
                throw new Exception("Error al preparar venta: " . $this->conn->error);
            }

            $stmtVenta->bind_param("idddsssssssss", 
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
                $estado_pago,
                $notas
            );

            if (!$stmtVenta->execute()) {
                throw new Exception("Error al insertar pedido: " . $stmtVenta->error);
            }
            $id_venta = $stmtVenta->insert_id;
            $stmtVenta->close();

            // 2. Insertar detalles y reservar/descontar stock atómicamente
            $queryDetalle = "INSERT INTO DETALLE_VENTA (id_venta, id_repuesto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmtDetalle = $this->conn->prepare($queryDetalle);

            $queryStock = "UPDATE REPUESTO SET stock = stock - ? WHERE id_repuesto = ? AND stock >= ?";
            $stmtStock = $this->conn->prepare($queryStock);

            foreach ($detalles as $item) {
                $id_repuesto = (int)$item['id_repuesto'];
                $cantidad = (int)$item['cantidad'];
                $precio = (float)$item['precio'];
                $subtotal_item = $cantidad * $precio;

                // Descontar stock asegurando disponibilidad
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
     * Obtiene todos los pedidos de un cliente específico con información de vendedor y pago
     * @param int $id_cliente
     * @return array
     */
    public function getPedidosByCliente($id_cliente) {
        $query = "SELECT v.id_venta, v.fecha, v.subtotal, v.impuestos, v.total, 
                         v.estado, v.tipo_venta, v.metodo_pago, v.direccion_envio, 
                         v.ciudad_envio, v.telefono_contacto, v.estado_pedido, 
                         v.estado_pago, v.fecha_pago, v.notas, v.notas_vendedor,
                         a.usuario as vendedor_nombre,
                         COUNT(d.id_detalle) as total_items
                  FROM VENTA v
                  LEFT JOIN DETALLE_VENTA d ON v.id_venta = d.id_venta
                  LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador
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
        $queryVenta = "SELECT v.*, c.nombre as cliente_nombre, c.correo as cliente_correo, c.telefono as cliente_telefono,
                              a.usuario as vendedor_nombre, a.email as vendedor_email
                       FROM VENTA v
                       JOIN CLIENTE c ON v.id_cliente = c.id_cliente
                       LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador
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

    /**
     * Obtiene todos los pedidos realizados por clientes para el panel del vendedor
     * @param array $filtros ['estado_pedido' => string, 'search' => string]
     * @return array
     */
    public function getPedidosClientes($filtros = []) {
        $where = "WHERE v.tipo_venta = 'Online'";
        $params = [];
        $types = "";

        if (!empty($filtros['estado_pedido']) && $filtros['estado_pedido'] !== 'Todos') {
            $where .= " AND v.estado_pedido = ?";
            $params[] = $filtros['estado_pedido'];
            $types .= "s";
        }

        if (!empty($filtros['search'])) {
            $s = trim($filtros['search']);
            $idSearch = is_numeric($s) ? intval($s) : 0;
            $searchLike = "%" . $s . "%";

            if ($idSearch > 0) {
                $where .= " AND (v.id_venta = ? OR c.nombre LIKE ? OR c.documento LIKE ? OR c.telefono LIKE ?)";
                $params[] = $idSearch;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $types .= "isss";
            } else {
                $where .= " AND (c.nombre LIKE ? OR c.documento LIKE ? OR c.telefono LIKE ? OR c.correo LIKE ?)";
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $types .= "ssss";
            }
        }

        $query = "SELECT v.*, c.nombre as cliente_nombre, c.correo as cliente_correo, 
                         c.documento as cliente_doc, c.telefono as cliente_telefono,
                         a.usuario as vendedor_usuario,
                         COUNT(d.id_detalle) as total_items
                  FROM VENTA v
                  LEFT JOIN CLIENTE c ON v.id_cliente = c.id_cliente
                  LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador
                  LEFT JOIN DETALLE_VENTA d ON v.id_venta = d.id_venta
                  $where
                  GROUP BY v.id_venta
                  ORDER BY v.id_venta DESC";

        $pedidos = [];
        if ($stmt = $this->conn->prepare($query)) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
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
     * Obtiene el pedido completo para el Vendedor con el inventario actual de bodega para revisión
     * @param int $id_venta
     * @return array|null
     */
    public function getPedidoCompletoVendedor($id_venta) {
        $id_venta = (int)$id_venta;
        $queryVenta = "SELECT v.*, c.nombre as cliente_nombre, c.correo as cliente_correo, 
                              c.documento as cliente_doc, c.telefono as cliente_telefono,
                              a.usuario as vendedor_usuario, a.email as vendedor_email
                       FROM VENTA v
                       LEFT JOIN CLIENTE c ON v.id_cliente = c.id_cliente
                       LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador
                       WHERE v.id_venta = ?";
        
        $pedido = null;
        if ($stmt = $this->conn->prepare($queryVenta)) {
            $stmt->bind_param("i", $id_venta);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 1) {
                $pedido = $res->fetch_assoc();
            }
            $stmt->close();
        }

        if (!$pedido) return null;

        $queryDetalles = "SELECT d.id_detalle, d.id_repuesto, d.cantidad, d.precio_unitario, d.subtotal,
                                 r.nombre, r.codigo_interno, r.codigo_barras, r.stock as stock_actual, r.estado as estado_repuesto,
                                 m.nombre as marca_nombre, i.ruta_archivo as imagen_url
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

    /**
     * El vendedor inicia la preparación del pedido de repuestos
     * Asigna el vendedor al pedido y actualiza el estado a 'En Preparación'
     */
    public function iniciarPreparacionPedido($id_venta, $id_vendedor, $notas_vendedor = '') {
        $id_venta = (int)$id_venta;
        $id_vendedor = (int)$id_vendedor;
        $notas_vendedor = trim($notas_vendedor);

        $query = "UPDATE VENTA 
                  SET estado_pedido = 'En Preparación',
                      id_vendedor = ?,
                      notas_vendedor = IF(? != '', ?, notas_vendedor)
                  WHERE id_venta = ? AND estado_pedido = 'Pendiente'";
        
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("issi", $id_vendedor, $notas_vendedor, $notas_vendedor, $id_venta);
            $res = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        }
        return false;
    }

    /**
     * El vendedor finaliza la preparación física de los repuestos y marca el pedido como 'Listo para Pago'
     * En este momento, el cliente podrá proceder a realizar el pago.
     */
    public function marcarListoParaPago($id_venta, $id_vendedor, $notas_vendedor = '') {
        $id_venta = (int)$id_venta;
        $id_vendedor = (int)$id_vendedor;
        $notas_vendedor = trim($notas_vendedor);

        $query = "UPDATE VENTA 
                  SET estado_pedido = 'Listo para Pago',
                      id_vendedor = COALESCE(id_vendedor, ?),
                      notas_vendedor = IF(? != '', ?, notas_vendedor)
                  WHERE id_venta = ? AND estado_pedido IN ('Pendiente', 'En Preparación')";
        
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("issi", $id_vendedor, $notas_vendedor, $notas_vendedor, $id_venta);
            $res = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        }
        return false;
    }

    /**
     * El cliente realiza o confirma el pago del pedido preparado
     * @param int $id_venta ID del pedido
     * @param int $id_cliente ID del cliente autenticado
     * @param string $metodo_pago Método seleccionado (Contra Entrega, Transferencia, etc.)
     * @param string $comprobante Número o comprobante de pago
     * @return bool
     */
    public function procesarPagoCliente($id_venta, $id_cliente, $metodo_pago, $comprobante = '') {
        $id_venta = (int)$id_venta;
        $id_cliente = (int)$id_cliente;
        $metodo_pago = trim($metodo_pago);
        $comprobante = trim($comprobante);

        // Permitir pago cuando el pedido esté en 'Listo para Pago'
        $query = "UPDATE VENTA 
                  SET estado_pedido = 'Pagado',
                      estado_pago = 'Pagado',
                      fecha_pago = NOW(),
                      metodo_pago = IF(? != '', ?, metodo_pago),
                      comprobante_pago = IF(? != '', ?, comprobante_pago)
                  WHERE id_venta = ? AND id_cliente = ? AND estado != 'Cancelada' AND estado_pedido = 'Listo para Pago'";
        
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("ssssii", $metodo_pago, $metodo_pago, $comprobante, $comprobante, $id_venta, $id_cliente);
            $res = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        }
        return false;
    }

    /**
     * El vendedor despacha el pedido (cambio a 'En Camino')
     */
    public function despacharPedido($id_venta, $id_vendedor, $notas_envio = '') {
        $id_venta = (int)$id_venta;
        $id_vendedor = (int)$id_vendedor;
        $notas_envio = trim($notas_envio);

        $query = "UPDATE VENTA 
                  SET estado_pedido = 'En Camino',
                      id_vendedor = COALESCE(id_vendedor, ?),
                      notas_vendedor = IF(? != '', ?, notas_vendedor)
                  WHERE id_venta = ? AND estado_pedido IN ('Pagado', 'Listo para Pago')";
        
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("issi", $id_vendedor, $notas_envio, $notas_envio, $id_venta);
            $res = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        }
        return false;
    }

    /**
     * El vendedor o cajero registra el pago en caja del pedido en el establecimiento
     * @param int $id_venta ID del pedido
     * @param int $id_vendedor ID del vendedor o cajero
     * @param string $metodo_pago (Efectivo en Caja, Tarjeta / Datáfono, Transferencia en Caja)
     * @param string $comprobante
     * @param string $notas
     * @return bool
     */
    public function registrarPagoCaja($id_venta, $id_vendedor, $metodo_pago = 'Efectivo en Caja', $comprobante = '', $notas = '') {
        $id_venta = (int)$id_venta;
        $id_vendedor = (int)$id_vendedor;
        $metodo_pago = trim($metodo_pago);
        $comprobante = trim($comprobante);
        $notas = trim($notas);

        $query = "UPDATE VENTA 
                  SET estado_pedido = 'Pagado',
                      estado_pago = 'Pagado',
                      fecha_pago = NOW(),
                      id_vendedor = COALESCE(id_vendedor, ?),
                      metodo_pago = IF(? != '', ?, metodo_pago),
                      comprobante_pago = IF(? != '', ?, comprobante_pago),
                      notas_vendedor = IF(? != '', CONCAT(COALESCE(notas_vendedor, ''), ' [Pago en Caja: ', ?, ']'), notas_vendedor)
                  WHERE id_venta = ? AND estado != 'Cancelada' AND estado_pedido IN ('Pendiente', 'En Preparación', 'Listo para Pago')";
        
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("issssssi", $id_vendedor, $metodo_pago, $metodo_pago, $comprobante, $comprobante, $notas, $notas, $id_venta);
            $res = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        }
        return false;
    }

    /**
     * El vendedor marca el pedido como entregado al cliente en el mostrador del establecimiento
     */
    public function entregarPedido($id_venta, $id_vendedor) {
        $id_venta = (int)$id_venta;
        $id_vendedor = (int)$id_vendedor;

        $query = "UPDATE VENTA 
                  SET estado_pedido = 'Entregado',
                      estado_pago = 'Pagado',
                      id_vendedor = COALESCE(id_vendedor, ?)
                  WHERE id_venta = ? AND estado_pedido IN ('Pagado', 'Listo para Pago', 'En Camino')";
        
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("ii", $id_vendedor, $id_venta);
            $res = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        }
        return false;
    }

    /**
     * Cancela o rechaza el pedido y restaura el stock reservado
     */
    public function cancelarPedidoConReversion($id_venta, $motivo = '') {
        $id_venta = (int)$id_venta;
        $motivo = trim($motivo);

        $this->conn->begin_transaction();
        try {
            $check = $this->conn->prepare("SELECT estado, estado_pedido FROM VENTA WHERE id_venta = ?");
            $check->bind_param("i", $id_venta);
            $check->execute();
            $res = $check->get_result();
            if ($res->num_rows === 0) throw new Exception("El pedido no existe.");
            $row = $res->fetch_assoc();
            if ($row['estado'] === 'Cancelada' || $row['estado_pedido'] === 'Cancelado') {
                throw new Exception("El pedido ya está cancelado.");
            }
            if ($row['estado_pedido'] === 'Entregado') {
                throw new Exception("Un pedido entregado no puede ser cancelado.");
            }
            $check->close();

            // Restaurar stock de repuestos
            $detalles = $this->getDetallesRaw($id_venta);
            $stmtStock = $this->conn->prepare("UPDATE REPUESTO SET stock = stock + ? WHERE id_repuesto = ?");
            foreach ($detalles as $d) {
                $stmtStock->bind_param("ii", $d['cantidad'], $d['id_repuesto']);
                $stmtStock->execute();
            }
            $stmtStock->close();

            // Actualizar estado de venta y pedido
            $notaMotivo = !empty($motivo) ? " [Cancelado: $motivo]" : "";
            $stmtUp = $this->conn->prepare("UPDATE VENTA 
                                           SET estado = 'Cancelada', 
                                               estado_pedido = 'Cancelado',
                                               notas_vendedor = CONCAT(COALESCE(notas_vendedor, ''), ?)
                                           WHERE id_venta = ?");
            $stmtUp->bind_param("si", $notaMotivo, $id_venta);
            $stmtUp->execute();
            $stmtUp->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Cuenta los pedidos de clientes por estado para métricas y badges en la interfaz del vendedor
     * @return array
     */
    public function contarPedidosPendientesVendedor() {
        $query = "SELECT 
                    SUM(CASE WHEN estado_pedido = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado_pedido = 'En Preparación' THEN 1 ELSE 0 END) as en_preparacion,
                    SUM(CASE WHEN estado_pedido = 'Listo para Pago' THEN 1 ELSE 0 END) as listos_pago,
                    SUM(CASE WHEN estado_pedido = 'Pagado' THEN 1 ELSE 0 END) as pagados,
                    SUM(CASE WHEN estado_pedido IN ('Pendiente', 'En Preparación') THEN 1 ELSE 0 END) as atencion_requerida
                  FROM VENTA WHERE tipo_venta = 'Online' AND estado != 'Cancelada'";
        
        $res = $this->conn->query($query);
        if ($res && $row = $res->fetch_assoc()) {
            return [
                'pendientes' => (int)($row['pendientes'] ?? 0),
                'en_preparacion' => (int)($row['en_preparacion'] ?? 0),
                'listos_pago' => (int)($row['listos_pago'] ?? 0),
                'pagados' => (int)($row['pagados'] ?? 0),
                'atencion_requerida' => (int)($row['atencion_requerida'] ?? 0)
            ];
        }
        return ['pendientes' => 0, 'en_preparacion' => 0, 'listos_pago' => 0, 'pagados' => 0, 'atencion_requerida' => 0];
    }
}
?>
