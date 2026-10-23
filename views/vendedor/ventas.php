<?php
session_start();
// Validación estricta de Vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Vendedor') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../config/database.php';
require_once '../../models/MarcaModel.php';
require_once '../../models/CategoriaModel.php';
require_once '../../models/RepuestoModel.php';

$marcaModel = new MarcaModel($conn);
$catModel = new CategoriaModel($conn);
$repModel = new RepuestoModel($conn);

require_once '../../models/RepuestoModel.php';

$repModel = new RepuestoModel($conn);


// Obtener catálogo para el buscador
$repuestos = [];
$repuestos = $repModel->getForPos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta - Motoverify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); padding: 30px; display: flex; flex-direction: column; background-color: #fbfbfd; }
        .cart-container { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; border: 1px solid #eaeaea; }
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
                <!-- Datos del Cliente -->
                <div class="cart-container mb-3">
                    <h5 class="mb-3 text-secondary"><i class="fa-solid fa-user-tag me-2"></i>Datos del Cliente</h5>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" id="cliente_doc" class="form-control form-control-sm" placeholder="Documento (CC/NIT)">
                        </div>
                        <div class="col-md-8">
                            <input type="text" id="cliente_nombre" class="form-control form-control-sm" placeholder="Nombre completo del cliente (Opcional)">
                        </div>
                    </div>
                </div>

                <!-- Agregar Producto -->
                <div class="cart-container mb-3 position-relative">
                    <h5 class="mb-3 text-secondary"><i class="fa-solid fa-box-open me-2"></i>Agregar Producto</h5>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85rem;">Buscar Repuesto</label>
                            <input type="text" id="buscador" class="form-control" placeholder="Buscar por código, nombre o marca..." onkeyup="filtrarRepuestos()" onfocus="document.getElementById('lista-repuestos').style.display='block'">
                            
                            <!-- Dropdown flotante de resultados -->
                            <div class="search-results list-group shadow position-absolute w-100" id="lista-repuestos" style="z-index: 1000; display: none; max-height: 300px; overflow-y: auto; top: 100%;">
                                <?php foreach($repuestos as $r): 
                                    $precio_js = floatval($r['precio'] ?? 0);
                                    $stock_js = intval($r['stock'] ?? 0);
                                    $nombre_js = htmlspecialchars(addslashes($r['nombre']), ENT_QUOTES);
                                ?>
                                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center repuesto-item" 
                                        onclick="seleccionarRepuesto(<?= $r['id_repuesto'] ?>, '<?= $nombre_js ?>', <?= $precio_js ?>, <?= $stock_js ?>)">
                                        <div>
                                            <span class="fw-bold"><?= htmlspecialchars($r['codigo_interno']) ?></span> - <?= htmlspecialchars($r['nombre']) ?> (<?= htmlspecialchars($r['marca']) ?>)
                                        </div>
                                        <span class="badge bg-primary rounded-pill">$<?= number_format($precio_js, 2) ?> | Stock: <?= $stock_js ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85rem;">Cant</label>
                            <input type="number" id="cantidad_add" class="form-control" value="1" min="1">
                        </div>
                        
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100 fw-bold" onclick="agregarProductoForm()">Agregar</button>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-2 bg-light rounded text-center border d-none" id="producto_seleccionado_alert">
                        <span class="fw-bold text-primary" id="producto_seleccionado_texto"></span>
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
                    <button class="btn btn-success w-100 mt-4 py-2 fw-bold" onclick="procesarVenta()">Registrar Venta</button>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let carrito = [];

        let repuestoSeleccionado = null;

        function filtrarRepuestos() {
            let filtro = document.getElementById('buscador').value.toLowerCase();
            let items = document.querySelectorAll('.repuesto-item');
            document.getElementById('lista-repuestos').style.display = 'block';
            let hayResultados = false;
            
            items.forEach(item => {
                let texto = item.innerText.toLowerCase();
                if(texto.includes(filtro)) {
                    item.style.display = '';
                    hayResultados = true;
                } else {
                    item.style.display = 'none';
                }
            });
            if(filtro === '') {
                document.getElementById('lista-repuestos').style.display = 'none';
            }
        }

        function seleccionarRepuesto(id, nombre, precio, stock) {
            repuestoSeleccionado = { id_repuesto: id, nombre: nombre, precio: precio, stock: stock };
            
            document.getElementById('buscador').value = nombre;
            document.getElementById('lista-repuestos').style.display = 'none';
            document.getElementById('cantidad_add').value = 1;
            
            let alertBox = document.getElementById('producto_seleccionado_alert');
            document.getElementById('producto_seleccionado_texto').innerText = `Seleccionado: ${nombre} ($${precio.toFixed(2)})`;
            alertBox.classList.remove('d-none');
            
            // Foco en cantidad
            document.getElementById('cantidad_add').focus();
        }

        function agregarProductoForm() {
            if (!repuestoSeleccionado) {
                Swal.fire('Atención', 'Primero debes buscar y seleccionar un repuesto de la lista.', 'warning');
                return;
            }
            
            let cantidad = parseInt(document.getElementById('cantidad_add').value);
            if (isNaN(cantidad) || cantidad <= 0) {
                Swal.fire('Error', 'La cantidad debe ser mayor a 0.', 'error');
                return;
            }

            agregarAlCarrito(repuestoSeleccionado.id_repuesto, repuestoSeleccionado.nombre, repuestoSeleccionado.precio, repuestoSeleccionado.stock, cantidad);
            
            // Limpiar formulario
            repuestoSeleccionado = null;
            document.getElementById('buscador').value = '';
            document.getElementById('cantidad_add').value = 1;
            document.getElementById('producto_seleccionado_alert').classList.add('d-none');
        }

        // Ocultar dropdown al hacer click afuera
        document.addEventListener('click', function(event) {
            let container = document.getElementById('lista-repuestos');
            let input = document.getElementById('buscador');
            if (!container.contains(event.target) && event.target !== input) {
                container.style.display = 'none';
            }
        });

        function agregarAlCarrito(id, nombre, precio, stock, cantidad_sumar = 1) {
            let existe = carrito.find(p => p.id_repuesto === id);
            if(existe) {
                if(existe.cantidad + cantidad_sumar <= stock) {
                    existe.cantidad += cantidad_sumar;
                } else {
                    Swal.fire('Atención', `Solo quedan ${stock} unidades en stock de este producto.`, 'warning');
                }
            } else {
                if (cantidad_sumar <= stock) {
                    carrito.push({ id_repuesto: id, nombre: nombre, precio: parseFloat(precio), cantidad: cantidad_sumar, stock: parseInt(stock) });
                } else {
                    Swal.fire('Atención', `Solo quedan ${stock} unidades en stock.`, 'warning');
                }
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
                text: "Se descontará el stock de los productos y se registrará a tu nombre.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../controllers/venta_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            action: 'guardar_venta', 
                            carrito: carrito,
                            cliente_doc: document.getElementById('cliente_doc').value,
                            cliente_nombre: document.getElementById('cliente_nombre').value
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire('¡Éxito!', 'Venta registrada (ID: ' + data.id_venta + ')', 'success')
                            .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Error procesando venta:', err);
                        Swal.fire('Error', 'Hubo un problema al conectar con el servidor. Revisa la consola o asegúrate de tener conexión.', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>
