<?php
session_start();
// Validación estricta de Vendedor (o Administrador si lo permitimos)
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'Vendedor' && $_SESSION['rol'] !== 'Administrador')) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../config/database.php';

// Obtener catálogo para el buscador
$repuestos = [];
$res = $conn->query("SELECT r.id_repuesto, r.nombre, r.codigo_interno, r.precio, r.stock, m.nombre as marca FROM REPUESTO r LEFT JOIN MARCA m ON r.id_marca = m.id_marca WHERE r.stock > 0");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $repuestos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta - Motoverify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }
        .topbar-global { height: 110px; background: #ffffff; border-bottom: 1px solid #eaeaea; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        .topbar-logo { display: inline-flex; align-items: center; height: 150%; text-decoration: none; }
        .topbar-logo img { max-height: 90px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 30px; background-color: #f8f9fc; }
        .cart-container { background: white; border-radius: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; border: 1px solid #f0f1f3; }
        .search-results { max-height: 400px; overflow-y: auto; }
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
            <a href="perfil.php" class="d-flex align-items-center justify-content-center bg-light text-secondary rounded-circle text-decoration-none" style="width: 45px; height: 45px; font-size: 1.2rem; border: 1px solid #e0e0e0; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='#f8f9fa'">
                <i class="fa-regular fa-user"></i>
            </a>
        </div>
    </header>

    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Hero Banner -->
        <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center justify-content-between">
            <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                <h2 class="fw-bold mb-2 d-flex align-items-center gap-3">
                    <i class="fa-solid fa-cart-shopping" style="font-size: 1.4rem; opacity: .8;"></i>
                    Punto de Venta
                </h2>
                <p class="mb-0 opacity-75" style="font-size: 1rem;">Crea nuevas facturas de repuestos rápidamente.</p>
            </div>
            <div class="hero-gears">
                <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
            </div>
        </div>
        <div class="row">
            <div class="col-md-7">
                <div class="cart-container mb-3">
                    <h5>Buscar Repuesto</h5>
                    <input type="text" id="buscador" class="form-control mb-3" placeholder="Buscar por código, nombre o marca..." onkeyup="filtrarRepuestos()">
                    <div class="search-results list-group" id="lista-repuestos">
                        <?php foreach($repuestos as $r): ?>
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center repuesto-item" 
                                onclick="agregarAlCarrito(<?= $r['id_repuesto'] ?>, '<?= addslashes($r['nombre']) ?>', <?= $r['precio'] ?>, <?= $r['stock'] ?>)">
                                <div>
                                    <span class="fw-bold"><?= $r['codigo_interno'] ?></span> - <?= $r['nombre'] ?> (<?= $r['marca'] ?>)
                                </div>
                                <span class="badge bg-primary rounded-pill">$<?= number_format($r['precio'], 2) ?> | Stock: <?= $r['stock'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="cart-container">
                    <h5>Carrito de Compras</h5>
                    <table class="table table-sm mt-3">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cant</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart-body">
                            <!-- Productos se agregan aquí -->
                        </tbody>
                    </table>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span id="txt-subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>IVA (19%):</span>
                        <span id="txt-iva">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
                        <span>Total:</span>
                        <span id="txt-total">$0.00</span>
                    </div>
                    <button class="btn btn-success w-100 mt-4 py-2" onclick="procesarVenta()">Registrar Venta</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let carrito = [];

        function filtrarRepuestos() {
            let filtro = document.getElementById('buscador').value.toLowerCase();
            let items = document.querySelectorAll('.repuesto-item');
            items.forEach(item => {
                let texto = item.innerText.toLowerCase();
                if(texto.includes(filtro)) item.style.display = '';
                else item.style.display = 'none';
            });
        }

        function agregarAlCarrito(id, nombre, precio, stock) {
            let existe = carrito.find(p => p.id_repuesto === id);
            if(existe) {
                if(existe.cantidad < stock) {
                    existe.cantidad++;
                } else {
                    Swal.fire('Atención', 'Stock máximo alcanzado', 'warning');
                }
            } else {
                carrito.push({ id_repuesto: id, nombre: nombre, precio: parseFloat(precio), cantidad: 1, stock: parseInt(stock) });
            }
            renderCarrito();
        }

        function eliminarDelCarrito(id) {
            carrito = carrito.filter(p => p.id_repuesto !== id);
            renderCarrito();
        }

        function renderCarrito() {
            let tbody = document.getElementById('cart-body');
            tbody.innerHTML = '';
            let subtotal = 0;

            carrito.forEach(p => {
                let sub_item = p.cantidad * p.precio;
                subtotal += sub_item;
                tbody.innerHTML += `
                    <tr>
                        <td style="font-size: 0.9rem;">${p.nombre}</td>
                        <td>${p.cantidad}</td>
                        <td>$${sub_item.toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(${p.id_repuesto})"><i class="fa fa-trash"></i></button></td>
                    </tr>
                `;
            });

            let iva = subtotal * 0.19;
            let total = subtotal + iva;

            document.getElementById('txt-subtotal').innerText = '$' + subtotal.toFixed(2);
            document.getElementById('txt-iva').innerText = '$' + iva.toFixed(2);
            document.getElementById('txt-total').innerText = '$' + total.toFixed(2);
        }

        function procesarVenta() {
            if(carrito.length === 0) {
                Swal.fire('Error', 'El carrito está vacío', 'error');
                return;
            }

            Swal.fire({
                title: '¿Confirmar venta?',
                text: "Se descontará el stock de los productos.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../controllers/venta_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'guardar_venta', carrito: carrito })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire('¡Éxito!', 'Venta registrada (ID: ' + data.id_venta + ')', 'success')
                            .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
