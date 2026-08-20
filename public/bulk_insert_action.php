<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['repuestos']) || !is_array($input['repuestos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

$repuestos = $input['repuestos'];

if (count($repuestos) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No hay repuestos para guardar.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO repuestos (codigo_barras, codigo, nombre, id_marca, modelo, creado_en)
        VALUES (:codigo_barras, :codigo, :nombre, :id_marca, :modelo, NOW())
    ");

    $count = 0;
    foreach ($repuestos as $rep) {
        $codigo_barras = !empty($rep['codigo_barras']) ? $rep['codigo_barras'] : null;
        $codigo = !empty($rep['codigo']) ? $rep['codigo'] : null; // Fallback
        
        // Si no hay codigo_barras pero hay codigo, lo usamos como codigo_barras o viceversa dependiendo de la BD.
        if (empty($codigo) && !empty($codigo_barras)) $codigo = $codigo_barras;
        if (empty($codigo_barras) && !empty($codigo)) $codigo_barras = $codigo;

        $nombre = !empty($rep['nombre']) ? $rep['nombre'] : 'Repuesto Desconocido';
        $id_marca = !empty($rep['id_marca']) ? intval($rep['id_marca']) : null;
        $modelo = !empty($rep['modelo']) ? $rep['modelo'] : null;

        // Skip si no hay id_marca (asumiendo que es clave foránea obligatoria)
        if (!$id_marca) {
            continue;
        }

        $stmt->execute([
            ':codigo_barras' => $codigo_barras,
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':id_marca' => $id_marca,
            ':modelo' => $modelo
        ]);
        $count++;
    }

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => "Se guardaron $count repuestos correctamente."]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
