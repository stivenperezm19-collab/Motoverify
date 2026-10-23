<?php
$is_cliente_auth = isset($_SESSION['user_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'Cliente';
$current_page = basename($_SERVER['PHP_SELF']);
$carrito_count = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $it) {
        $carrito_count += (int)($it['cantidad'] ?? 1);
    }
}
?>
<aside class="sidebar">
    <div class="px-3 mb-3 mt-3">
        <?php if ($is_cliente_auth): ?>
            <a href="repuestos.php" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold shadow-sm" style="border-radius: 12px; padding: 12px; background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%); border: none;">
                <i class="fa-solid fa-bag-shopping"></i> Comprar Repuestos
            </a>
        <?php else: ?>
            <a href="repuestos.php" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold shadow-sm" style="border-radius: 12px; padding: 12px; background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%); border: none;">
                <i class="fa-solid fa-magnifying-glass"></i> Consultar Repuestos
            </a>
        <?php endif; ?>
    </div>

    <nav class="flex-grow-1">
        <?php if ($is_cliente_auth): ?>
            <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
        <?php endif; ?>

        <a href="catalogo.php" class="nav-item <?= $current_page == 'catalogo.php' ? 'active' : '' ?>" title="Categorías y diagrama de ubicación en la moto">
            <i class="fa-solid fa-book-open"></i> Catálogo
        </a>

        <a href="repuestos.php" class="nav-item <?= $current_page == 'repuestos.php' ? 'active' : '' ?>" title="Tienda de repuestos">
            <i class="fa-solid fa-puzzle-piece"></i> Repuestos
        </a>

        <a href="carrito.php" class="nav-item <?= $current_page == 'carrito.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-shopping"></i> 
            <span>Mi Carrito</span>
            <span class="badge rounded-pill bg-danger ms-auto cart-badge-count <?= $carrito_count > 0 ? '' : 'd-none' ?>">
                <?= $carrito_count ?>
            </span>
        </a>

        <?php if ($is_cliente_auth): ?>
            <a href="historial_compras.php" class="nav-item <?= ($current_page == 'historial_compras.php' || $current_page == 'detalle_compra.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> Mis Pedidos
            </a>
            <a href="perfil.php" class="nav-item <?= $current_page == 'perfil.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-gear"></i> Mi Perfil
            </a>
        <?php else: ?>
            <!-- Elementos restringidos con aviso para invitados -->
            <a href="javascript:void(0)" onclick="mostrarAlertaLogin('acceder al historial de tus pedidos')" class="nav-item text-muted" title="Requiere iniciar sesión">
                <i class="fa-solid fa-clock-rotate-left"></i> 
                <span>Mis Pedidos</span>
                <i class="fa-solid fa-lock text-muted ms-auto" style="font-size: 0.8rem;"></i>
            </a>
            <a href="javascript:void(0)" onclick="mostrarAlertaLogin('ver y editar tu perfil de cliente')" class="nav-item text-muted" title="Requiere iniciar sesión">
                <i class="fa-solid fa-user-gear"></i> 
                <span>Mi Perfil</span>
                <i class="fa-solid fa-lock text-muted ms-auto" style="font-size: 0.8rem;"></i>
            </a>
        <?php endif; ?>
    </nav>

    <?php if (!$is_cliente_auth): ?>
        <div class="p-3 m-3 rounded-3 text-center border" style="background: #eff6ff; border-color: #bfdbfe !important;">
            <i class="fa-solid fa-shield-halved text-primary fs-4 mb-2"></i>
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">¿Deseas comprar?</h6>
            <p class="text-muted small mb-3" style="font-size: 0.75rem;">Inicia sesión o regístrate para comprar y rastrear tus repuestos.</p>
            <a href="../../public/index.php?register=1" class="btn btn-primary btn-sm w-100 fw-semibold rounded-pill mb-2 shadow-sm" style="background: #004ba8; border: none;">Registrarse</a>
            <a href="../../public/index.php?login=1" class="text-primary small fw-semibold text-decoration-none">Iniciar Sesión</a>
        </div>
    <?php endif; ?>

    <div class="mt-auto border-top pt-3 pb-3">
        <a href="../../public/index.php" class="nav-item text-secondary">
            <i class="fa-solid fa-store"></i> Tienda Principal
        </a>
        <?php if ($is_cliente_auth): ?>
            <a href="../../controllers/logout.php" class="nav-item text-danger">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
            </a>
        <?php endif; ?>
    </div>
</aside>

<script>
function mostrarAlertaLogin(accion) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Acceso para Clientes',
            text: 'Debes iniciar sesión con tu cuenta de cliente para ' + accion + '.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004ba8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Iniciar Sesión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../public/index.php?login=1';
            }
        });
    } else {
        window.location.href = '../../public/index.php?login=1';
    }
}
</script>
