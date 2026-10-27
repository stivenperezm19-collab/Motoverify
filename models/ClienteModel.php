<?php
/**
 * Modelo para la entidad CLIENTE
 * Gestiona el registro, autenticación, perfil y consultas de clientes en Motoverify.
 */
class ClienteModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtiene un cliente por su ID único
     * @param int $id ID del cliente
     * @return array|null Datos del cliente o null si no se encuentra
     */
    public function getById($id) {
        $query = "SELECT c.id_cliente, c.nombre, c.documento, c.telefono, c.correo, 
                         c.direccion, c.ciudad, c.id_rol, r.nombre_rol, c.fecha_registro, c.estado 
                  FROM CLIENTE c
                  LEFT JOIN ROL r ON c.id_rol = r.id_rol 
                  WHERE c.id_cliente = ?";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $cliente = $result->fetch_assoc();
                $stmt->close();
                return $cliente;
            }
            $stmt->close();
        }
        return null;
    }

    /**
     * Obtiene un cliente por su correo electrónico (incluye hash de clave para autenticación)
     * @param string $correo Correo del cliente
     * @return array|null Datos del cliente con clave_hash o null
     */
    public function getByEmail($correo) {
        $query = "SELECT c.id_cliente, c.nombre, c.documento, c.telefono, c.correo, 
                         c.clave_hash, c.direccion, c.ciudad, c.id_rol, r.nombre_rol, c.estado 
                  FROM CLIENTE c
                  LEFT JOIN ROL r ON c.id_rol = r.id_rol 
                  WHERE c.correo = ?";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $cliente = $result->fetch_assoc();
                $stmt->close();
                return $cliente;
            }
            $stmt->close();
        }
        return null;
    }

    /**
     * Verifica si un correo ya se encuentra registrado
     * @param string $correo
     * @param int $exclude_id ID a ignorar (útil al editar perfil)
     * @return bool
     */
    public function emailExists($correo, $exclude_id = 0) {
        $query = "SELECT id_cliente FROM CLIENTE WHERE correo = ?";
        if ($exclude_id > 0) {
            $query .= " AND id_cliente != ?";
        }
        if ($stmt = $this->conn->prepare($query)) {
            if ($exclude_id > 0) {
                $stmt->bind_param("si", $correo, $exclude_id);
            } else {
                $stmt->bind_param("s", $correo);
            }
            $stmt->execute();
            $stmt->store_result();
            $exists = ($stmt->num_rows > 0);
            $stmt->close();
            return $exists;
        }
        return false;
    }

    /**
     * Registra un nuevo cliente con rol 'Cliente' (id_rol = 3) y estado 'Activo'
     * @return int|false ID del nuevo cliente o false si hubo error
     */
    public function create($nombre, $correo, $passwordHash, $documento = null, $telefono = null, $direccion = null, $ciudad = null) {
        $id_rol = 3; // Rol Cliente garantizado en el backend (evita escalado de privilegios)
        $estado = 'Activo';

        $query = "INSERT INTO CLIENTE (nombre, correo, clave_hash, documento, telefono, direccion, ciudad, id_rol, estado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("sssssssis", $nombre, $correo, $passwordHash, $documento, $telefono, $direccion, $ciudad, $id_rol, $estado);
            if ($stmt->execute()) {
                $insert_id = $stmt->insert_id;
                $stmt->close();
                return $insert_id;
            }
            $stmt->close();
        }
        return false;
    }

    /**
     * Actualiza la información de perfil de un cliente
     * @return bool
     */
    public function updateProfile($id, $nombre, $telefono, $documento, $direccion, $ciudad, $passwordHash = null) {
        if ($passwordHash) {
            $query = "UPDATE CLIENTE SET nombre = ?, telefono = ?, documento = ?, direccion = ?, ciudad = ?, clave_hash = ? WHERE id_cliente = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("ssssssi", $nombre, $telefono, $documento, $direccion, $ciudad, $passwordHash, $id);
                $res = $stmt->execute();
                $stmt->close();
                return $res;
            }
        } else {
            $query = "UPDATE CLIENTE SET nombre = ?, telefono = ?, documento = ?, direccion = ?, ciudad = ? WHERE id_cliente = ?";
            if ($stmt = $this->conn->prepare($query)) {
                $stmt->bind_param("sssssi", $nombre, $telefono, $documento, $direccion, $ciudad, $id);
                $res = $stmt->execute();
                $stmt->close();
                return $res;
            }
        }
        return false;
    }

    /**
     * Obtiene estadísticas resumidas de compras para el panel del cliente
     * @param int $id_cliente
     * @return array
     */
    public function getEstadisticas($id_cliente) {
        $stats = [
            'total_pedidos' => 0,
            'total_gastado' => 0.00,
            'pedidos_pendientes' => 0,
            'pedidos_entregados' => 0
        ];

        $query = "SELECT 
                    COUNT(*) as total_pedidos,
                    COALESCE(SUM(total), 0) as total_gastado,
                    SUM(CASE WHEN estado_pedido IN ('Pendiente', 'En Preparación', 'Listo para Pago', 'Pagado', 'Confirmado', 'En Camino') THEN 1 ELSE 0 END) as pedidos_pendientes,
                    SUM(CASE WHEN estado_pedido = 'Entregado' THEN 1 ELSE 0 END) as pedidos_entregados
                  FROM VENTA 
                  WHERE id_cliente = ? AND estado != 'Cancelada'";

        if ($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("i", $id_cliente);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stats['total_pedidos'] = (int)$row['total_pedidos'];
                $stats['total_gastado'] = (float)$row['total_gastado'];
                $stats['pedidos_pendientes'] = (int)$row['pedidos_pendientes'];
                $stats['pedidos_entregados'] = (int)$row['pedidos_entregados'];
            }
            $stmt->close();
        }
        return $stats;
    }
}
?>
