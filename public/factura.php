<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado.");
}
require_once '../config/database.php';

$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_venta <= 0) {
    die("Factura no válida.");
}

// Obtener datos de la venta y el cliente
$queryVenta = "SELECT v.*, c.nombre as cliente_nombre, c.documento as cliente_doc, c.telefono, c.correo, a.usuario as vendedor 
               FROM VENTA v 
               LEFT JOIN CLIENTE c ON v.id_cliente = c.id_cliente 
               LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador
               WHERE v.id_venta = $id_venta";
$resVenta = $conn->query($queryVenta);

if (!$resVenta || $resVenta->num_rows === 0) {
    die("Venta no encontrada.");
}
$venta = $resVenta->fetch_assoc();

// Obtener detalles
$queryDetalle = "SELECT vd.cantidad, vd.precio_unitario, vd.subtotal, r.nombre, r.codigo_interno, r.codigo_barras 
                 FROM DETALLE_VENTA vd 
                 JOIN REPUESTO r ON vd.id_repuesto = r.id_repuesto 
                 WHERE vd.id_venta = $id_venta";
$resDetalle = $conn->query($queryDetalle);
$detalles = [];
if ($resDetalle) {
    while ($row = $resDetalle->fetch_assoc()) {
        $detalles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura de Venta #<?= str_pad($id_venta, 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .invoice-container { background: #fff; max-width: 800px; margin: 40px auto; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .invoice-header { border-bottom: 2px solid #004ba8; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-height: 80px; }
        .invoice-title { font-weight: 800; color: #004ba8; font-size: 2rem; }
        .table th { background-color: #f8f9fa; color: #333; font-weight: bold; }
        .total-row { font-size: 1.2rem; font-weight: bold; color: #004ba8; }
        .text-muted { color: #6c757d !important; }
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .invoice-container { box-shadow: none; margin: 0; padding: 20px; max-width: 100%; border: none; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-center mt-3 btn-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimir / Guardar como PDF
        </button>
        <button class="btn btn-secondary ms-2" onclick="window.close()">Cerrar</button>
    </div>

    <div class="invoice-container">
        <div class="row invoice-header align-items-center">
            <div class="col-sm-6">
                <!-- Para evitar error si no carga desde localhost, usamos ruta absoluta o base64 para PDF -->
                <img src="assets/images/logo_motoverify.png" alt="Motoverify" class="logo">
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h1 class="invoice-title m-0">FACTURA</h1>
                <p class="m-0 text-muted">N° <?= str_pad($id_venta, 5, '0', STR_PAD_LEFT) ?></p>
                <p class="m-0 text-muted">Fecha: <?= date('d/m/Y h:i A', strtotime($venta['fecha'])) ?></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <h5 class="fw-bold mb-2 text-dark">Información de la Empresa</h5>
                <p class="mb-1 fw-semibold">MOTOVERIFY</p>
                <p class="mb-1 text-muted">NIT: 900.123.456-7</p>
                <p class="mb-1 text-muted">Vendedor: <?= htmlspecialchars($venta['vendedor']) ?></p>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h5 class="fw-bold mb-2 text-dark">Facturar a:</h5>
                <p class="mb-1 fw-semibold"><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final') ?></p>
                <?php if(!empty($venta['cliente_doc']) && $venta['cliente_doc'] !== '000000000'): ?>
                    <p class="mb-1 text-muted">Doc/NIT: <?= htmlspecialchars($venta['cliente_doc']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-4">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 10%;">Cant.</th>
                        <th style="width: 45%;">Descripción del Repuesto</th>
                        <th class="text-end" style="width: 20%;">Precio Unit.</th>
                        <th class="text-end" style="width: 25%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($detalles as $d): ?>
                        <tr>
                            <td class="text-center"><?= $d['cantidad'] ?></td>
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($d['nombre']) ?></span><br>
                                <small class="text-muted">Ref: <?= htmlspecialchars($d['codigo_interno']) ?></small>
                            </td>
                            <td class="text-end">$<?= number_format($d['precio_unitario'], 2) ?></td>
                            <td class="text-end">$<?= number_format($d['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-sm-5">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-end text-muted fw-bold">Subtotal:</td>
                        <td class="text-end fw-bold">$<?= number_format($venta['subtotal'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="text-end text-muted fw-bold">IVA (19%):</td>
                        <td class="text-end fw-bold">$<?= number_format($venta['impuestos'], 2) ?></td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-end fw-bold text-dark fs-5">TOTAL:</td>
                        <td class="text-end total-row">$<?= number_format($venta['total'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-5 text-center text-muted">
            <p class="mb-0 fw-semibold">¡Gracias por su compra en Motoverify!</p>
            <small>Los repuestos eléctricos no tienen garantía. Conserve esta factura para reclamos.</small>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script>
    // Iniciar impresión automáticamente si se le pasa el parámetro ?print=1
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('print')) {
        setTimeout(() => {
            window.print();
        }, 500);
    }
</script>
</body>
</html>
