<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar pt-4">
    <nav class="flex-grow-1">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <a href="catalogo.php" class="nav-item <?= $current_page == 'catalogo.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book"></i> Catálogo
        </a>
        <a href="marcas.php" class="nav-item <?= $current_page == 'marcas.php' ? 'active' : '' ?>">
            <i class="fa-regular fa-star"></i> Marcas
        </a>
        <a href="repuestos.php" class="nav-item <?= $current_page == 'repuestos.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-puzzle-piece"></i> Repuestos
        </a>
        <a href="ventas.php" class="nav-item <?= $current_page == 'ventas.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-shopping"></i> Punto de Venta
        </a>
        <a href="historial_ventas.php" class="nav-item <?= $current_page == 'historial_ventas.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> Mis Ventas
        </a>
    </nav>
    <div class="mt-auto border-top pt-3 pb-3">
        <a href="perfil.php" class="nav-item <?= $current_page == 'perfil.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i> Mi Perfil
        </a>
    </div>
</aside>