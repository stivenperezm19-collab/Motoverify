<?php
session_start();
// Validación estricta de Vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Vendedor') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../config/database.php';

// Filtros
$f_year = $_GET['f_year'] ?? '';
$f_month = $_GET['f_month'] ?? '';
$f_total = $_GET['f_total'] ?? '';

$ventas = [];
try {
    $query = "SELECT v.id_venta, v.fecha, v.total, v.estado, a.usuario AS vendedor, a.email
              FROM VENTA v
              LEFT JOIN ADMINISTRADOR a ON v.id_vendedor = a.id_administrador
              WHERE 1=1";
              
    $params = [];
    $types = "";
    $query .= " AND v.id_vendedor = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
    
    if ($f_year !== '') {
        $query .= " AND YEAR(v.fecha) = ?";
        $params[] = $f_year;
        $types .= "i";
    }
    if ($f_month !== '') {
        $query .= " AND MONTH(v.fecha) = ?";
        $params[] = $f_month;
        $types .= "i";
    }
    if ($f_total !== '') {
        $query .= " AND v.total >= ?";
        $params[] = $f_total;
        $types .= "d";
    }
    
    $query .= " ORDER BY v.fecha DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $ventas[] = $row;
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas - Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fbfbfd; color: #333; overflow-x: hidden; }
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 40px 30px; background-color: #f8f9fc; }
        .content-card { background: #fff; border-radius: 14px; padding: 25px; border: 1px solid #f0f1f3; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
            /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #0640aa 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }
    </style>
</head>
<body>
    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="../../public/assets/images/logo_motoverify.png" alt="Motoverify">
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="perfil.php" class="d-flex align-items-center me-3 bg-light rounded-pill px-3 py-1 border border-secondary-subtle text-decoration-none" style="transition: all 0.2s; cursor: pointer;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='#f8f9fa'">
                <i class="fa-solid fa-circle-user text-primary fs-4 me-2"></i>
                <span class="text-dark fw-bold" style="font-size: 0.95rem;"><?= ucfirst(explode('@', $_SESSION['email'])[0]) ?></span>
            </a>
            <a href="../../controllers/logout.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="fa fa-sign-out me-2"></i>Salir</a>
        </div>
    </header>

    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Hero Banner -->
        <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center justify-content-between">
            <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                <h2 class="fw-bold mb-2 d-flex align-items-center gap-3">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 1.4rem; opacity: .8;"></i>
                    Mi Historial de Ventas
                </h2>
                <p class="mb-0 opacity-75" style="font-size: 1rem;">Consulta el registro de las ventas que has realizado.</p>
            </div>
            <div class="hero-gears">
                <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
            </div>
        </div>

                <div class="content-card mb-4">
            <form method="GET" action="historial_ventas.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Año</label>
                    <select name="f_year" class="form-select">
                        <option value="">Todos</option>
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= $currentYear - 5; $i--) {
                            $sel = ($f_year == $i) ? 'selected' : '';
                            echo "<option value='$i' $sel>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Mes</label>
                    <select name="f_month" class="form-select">
                        <option value="">Todos</option>
                        <?php 
                        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
                        foreach($meses as $num => $nombre) {
                            $sel = ($f_month == $num) ? 'selected' : '';
                            echo "<option value='$num' $sel>$nombre</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Valor Mínimo ($)</label>
                    <input type="number" step="0.01" name="f_total" class="form-control" placeholder="Ej: 50.00" value="<?= htmlspecialchars($f_total) ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-filter me-2"></i>Filtrar</button>
                    <a href="historial_ventas.php" class="btn btn-outline-secondary fw-bold" title="Limpiar"><i class="fa-solid fa-xmark"></i></a>
                </div>
            </form>
        </div>

        <div class="content-card">
            <table id="ventasTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha y Hora</th>
                        <th>Estado</th>
                        <th>Total ($)</th>
                        <th class="text-center">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ventas as $v): ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= str_pad($v['id_venta'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= date('d/m/Y h:i A', strtotime($v['fecha'])) ?></td>
                            <td>
                                <?php if (($v['estado'] ?? 'Completada') === 'Anulada'): ?>
                                    <span class="badge bg-danger">Anulada</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Completada</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold <?= (($v['estado'] ?? 'Completada') === 'Anulada') ? 'text-muted text-decoration-line-through' : 'text-success' ?>">$<?= number_format($v['total'], 2) ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(<?= $v['id_venta'] ?>)">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </button>
                                    <a href="../../public/factura.php?id=<?= $v['id_venta'] ?>&print=1" target="_blank" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-file-pdf"></i> Factura
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Detalle Venta -->
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detalle de Venta #<span id="detalle_id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detalle_body">
                            <!-- Detalles cargados por JS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th class="text-end fw-bold text-success fs-5" id="detalle_total"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#ventasTable').DataTable({
                order: [[1, 'desc']], // Ordenar por fecha descendente
                language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
            });
        });

        function verDetalle(id_venta) {
            fetch(`../../controllers/venta_action.php?action=get_detalle&id_venta=${id_venta}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        document.getElementById('detalle_id').innerText = id_venta.toString().padStart(5, '0');
                        const tbody = document.getElementById('detalle_body');
                        tbody.innerHTML = '';
                        let total = 0;
                        
                        data.detalle.forEach(item => {
                            const subtotal = item.cantidad * item.precio_unitario;
                            total += subtotal;
                            tbody.innerHTML += `
                                <tr>
                                    <td>${item.codigo_barras || item.codigo_interno}</td>
                                    <td>${item.nombre}</td>
                                    <td class="text-center">${item.cantidad}</td>
                                    <td class="text-end">$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                                    <td class="text-end">$${subtotal.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        document.getElementById('detalle_total').innerText = '$' + total.toFixed(2);
                        new bootstrap.Modal(document.getElementById('detalleModal')).show();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => console.error(err));
        }
    </script>
</body>
</html>
