<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="px-3 mb-3 mt-3 dropdown">
        <button class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold border" style="border-radius: 12px; padding: 12px;" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-plus text-primary"></i> Nuevo
        </button>
        <ul class="dropdown-menu w-100 shadow-sm border-0" style="border-radius: 10px;">
            <li><a class="dropdown-item py-2" href="repuestos.php"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Crear Manualmente</a></li>
            <li><a class="dropdown-item py-2" href="repuestos.php?ai_import=true"><i class="fa-solid fa-wand-magic-sparkles me-2 text-success"></i>Importación con IA</a></li>
        </ul>
    </div>

    <nav class="flex-grow-1">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <a href="ventas.php" class="nav-item <?= $current_page == 'ventas.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-shopping"></i> Punto de Venta
        </a>
        <a href="historial_ventas.php" class="nav-item <?= $current_page == 'historial_ventas.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> Historial de Ventas
        </a>
        <a href="marcas.php" class="nav-item <?= $current_page == 'marcas.php' ? 'active' : '' ?>">
            <i class="fa-regular fa-star"></i> Marcas
        </a>
        <a href="repuestos.php" class="nav-item <?= $current_page == 'repuestos.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-puzzle-piece"></i> Repuestos
        </a>
        <a href="catalogo.php" class="nav-item <?= $current_page == 'catalogo.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book"></i> Catálogo
        </a>
        <a href="usuarios.php" class="nav-item <?= $current_page == 'usuarios.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users-gear"></i> Gestión de usuarios
        </a>
    </nav>

    <div class="mt-auto border-top pt-3 pb-3">
        <a href="perfil.php" class="nav-item <?= $current_page == 'perfil.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i> Configuración
        </a>
    </div>
</aside>