<?php
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
        <a href="catalogo.php" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold shadow-sm" style="border-radius: 12px; padding: 12px; background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%); border: none;">
            <i class="fa-solid fa-bag-shopping"></i> Comprar Repuestos
        </a>
    </div>

    <nav class="flex-grow-1">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <a href="catalogo.php" class="nav-item <?= $current_page == 'catalogo.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i> Catálogo
        </a>
        <a href="carrito.php" class="nav-item <?= $current_page == 'carrito.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-shopping"></i> 
            <span>Mi Carrito</span>
            <span class="badge rounded-pill bg-danger ms-auto cart-badge-count <?= $carrito_count > 0 ? '' : 'd-none' ?>">
                <?= $carrito_count ?>
            </span>
        </a>
        <a href="historial_compras.php" class="nav-item <?= ($current_page == 'historial_compras.php' || $current_page == 'detalle_compra.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> Mis Pedidos
        </a>
        <a href="perfil.php" class="nav-item <?= $current_page == 'perfil.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i> Mi Perfil
        </a>
    </nav>

    <div class="mt-auto border-top pt-3 pb-3">
        <a href="../../public/index.php" class="nav-item text-secondary">
            <i class="fa-solid fa-store"></i> Tienda Principal
        </a>
        <a href="../../controllers/logout.php" class="nav-item text-danger">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
        </a>
    </div>
</aside>
