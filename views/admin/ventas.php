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
    <style>
        body { background-color: #f8f9fa; }
        .cart-container { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 20px; }
        .search-results { max-height: 400px; overflow-y: auto; }
        .sidebar { background: #ffffff; border-right: 1px solid #eaeaea; height: 100vh; position: fixed; width: 250px; }
        .main-content { margin-left: 250px; padding: 20px; }
    </style>
</head>
<body>
    <div class="sidebar p-3">
        <h4>Motoverify</h4>
        <hr>
        <ul class="nav flex-column">
            <?php if($_SESSION['rol'] === 'Administrador'): ?>
                <li class="nav-item"><a class="nav-link text-dark" href="dashboard.php"><i class="fa fa-dashboard me-2"></i> Dashboard Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-primary fw-bold" href="ventas.php"><i class="fa fa-shopping-cart me-2"></i> Nueva Venta</a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="historial_ventas.php"><i class="fa fa-history me-2"></i> Mis Ventas</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="../../controllers/logout.php"><i class="fa fa-sign-out me-2"></i> Salir</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Punto de Venta</h2>
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
