<?php
$current_page = basename($_SERVER['PHP_SELF']);
$total_pedidos_pendientes = 0;
if (isset($conn)) {
    try {
        $qPed = $conn->query("SELECT COUNT(*) as c FROM VENTA WHERE tipo_venta = 'Online' AND estado_pedido IN ('Pendiente', 'En Preparación') AND estado != 'Cancelada'");
        if ($qPed) $total_pedidos_pendientes = (int)$qPed->fetch_assoc()['c'];
    } catch(Exception $e) {}
}
?>
<aside class="sidebar pt-4">
    <nav class="flex-grow-1">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <a href="pedidos.php" class="nav-item <?= $current_page == 'pedidos.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-packing"></i> Pedidos de Clientes
            <?php if ($total_pedidos_pendientes > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.72rem;"><?= $total_pedidos_pendientes ?></span>
            <?php endif; ?>
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