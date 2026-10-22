<?php
$cliente_nombre = $_SESSION['nombre'] ?? 'Cliente';
$cliente_email = $_SESSION['email'] ?? '';
$carrito_total_items = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $it) {
        $carrito_total_items += (int)($it['cantidad'] ?? 1);
    }
}
?>
<!-- ===== TOPBAR CLIENTE ===== -->
<header class="topbar-global px-4 d-flex align-items-center justify-content-between">
    <!-- Logo Motoverify -->
    <a href="dashboard.php" class="topbar-logo text-decoration-none">
        <img src="../../public/assets/images/logo_motoverify.png?v=<?= time() ?>" alt="Motoverify">
    </a>

    <!-- Buscador en tiempo real hacia el Catálogo -->
    <div class="d-flex align-items-center gap-3">
        <form action="catalogo.php" method="GET" class="search-container d-flex align-items-center d-none d-md-flex" style="width: 380px; margin-bottom: 0;">
            <i class="fa-solid fa-magnifying-glass text-muted me-2"></i>
            <input type="text" name="search" class="form-control" placeholder="Buscar repuestos, marcas o modelos..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="background:transparent; border:none; box-shadow:none;">
            <?php if (!empty($_GET['search'])): ?>
                <a href="catalogo.php" class="text-muted text-decoration-none ms-2"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </form>

        <!-- Botón de Carrito con Contador -->
        <a href="carrito.php" class="btn btn-outline-primary position-relative d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 12px; border-color: #e0e0e0; background: #fff;" title="Ver mi carrito de compras">
            <i class="fa-solid fa-cart-shopping fs-5 text-primary"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge-count <?= $carrito_total_items > 0 ? '' : 'd-none' ?>" style="font-size: 0.72rem;">
                <?= $carrito_total_items ?>
            </span>
        </a>

        <!-- Menú de Usuario Cliente -->
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 border shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 12px; padding: 6px 14px; background: #fff;">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 34px; height: 34px; background: #004ba8; font-size: 0.9rem;">
                    <?= strtoupper(mb_substr($cliente_nombre, 0, 1)) ?>
                </div>
                <div class="d-none d-sm-block text-start" style="line-height: 1.2;">
                    <span class="fw-semibold text-dark d-block" style="font-size: 0.9rem;"><?= htmlspecialchars($cliente_nombre) ?></span>
                    <span class="badge bg-primary-subtle text-primary fw-medium" style="font-size: 0.7rem;">Cliente</span>
                </div>
                <i class="fa-solid fa-chevron-down text-muted ms-1" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 12px; min-width: 220px;">
                <li class="px-3 py-2 border-bottom">
                    <p class="mb-0 fw-bold text-dark text-truncate" style="font-size: 0.9rem;"><?= htmlspecialchars($cliente_nombre) ?></p>
                    <p class="mb-0 text-muted small text-truncate"><?= htmlspecialchars($cliente_email) ?></p>
                </li>
                <li><a class="dropdown-item py-2" href="dashboard.php"><i class="fa-solid fa-house me-2 text-primary"></i>Panel de Inicio</a></li>
                <li><a class="dropdown-item py-2" href="catalogo.php"><i class="fa-solid fa-book-open me-2 text-primary"></i>Catálogo de Productos</a></li>
                <li><a class="dropdown-item py-2" href="carrito.php"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Mi Carrito</a></li>
                <li><a class="dropdown-item py-2" href="historial_compras.php"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Mis Pedidos</a></li>
                <li><a class="dropdown-item py-2" href="perfil.php"><i class="fa-solid fa-user-gear me-2 text-primary"></i>Mi Perfil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 text-secondary" href="../../public/index.php"><i class="fa-solid fa-store me-2"></i>Tienda Pública</a></li>
                <li><a class="dropdown-item py-2 text-danger" href="../../controllers/logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>
</header>
