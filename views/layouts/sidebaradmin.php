<?php
$id_rol = $usuario['id_rol'];
$rol_nombre = '';
switch ($id_rol) {
    case '1':
        $rol_nombre = 'Administrador';
        break;
    case '2':
        $rol_nombre = 'Vendedor';
        break;
    case '3':
        $rol_nombre = 'Cliente';
        break;
}
$nombreCompleto = $usuario['usuario'];
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<aside class="w-64 bg-white shadow-xl border-r border-gray-200">
    <div class="h-32 flex flex-col items-center justify-center border-b border-gray-100 px-4 gap-1">
        <div class="text-center text-green-800">
            <div class="text-[10px] font-bold leading-tight uppercase tracking-[0.2em] opacity-70">Sistema de</div>
            <div class="text-xl font-black tracking-tight leading-none">TIENDA INSUMO</div>
        </div>
    </div>

    <nav class="mt-4 px-3 space-y-2">
        <?php if ($id_rol === '1' || $id_rol === 1): ?>
            <a href="../admin/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-green-700 hover:bg-blue-50 transition">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            <a href="../admin/gusuarios.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-green-700 hover:bg-blue-50 transition">
                <i class="fas fa-users"></i>
                <span>Gestion Usuarios</span>
            </a>
            <a href="categorias.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-green-700 hover:bg-blue-50 transition">
                <i class="fas fa-tags"></i>
                <span>Proveedores</span>
            </a>
            <a href="productos.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-green-700 hover:bg-blue-50 transition">
                <i class="fas fa-box"></i>
                <span>Categorias</span>
            </a>
            <a href="productos.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-green-700 hover:bg-blue-50 transition">
                <i class="fas fa-box"></i>
                <span>Productos</span>
            </a>
         
        <?php endif; ?>

        <?php if ($id_rol === '2' || $id_rol === 2): ?>
            <a href="../vendedor/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-blue-700 hover:bg-blue-50 transition">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            <a href="../vendedor/ventas.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-blue-700 hover:bg-blue-50 transition">
                <i class="fas fa-shopping-cart"></i>
                <span>Ventas</span>
            </a>
            <a href="../vendedor/productos.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-blue-700 hover:bg-blue-50 transition">
                <i class="fas fa-box"></i>
                <span>Productos</span>
            </a>
        <?php endif; ?>

        <?php if ($id_rol === '3' || $id_rol === 3): ?>
            <a href="../cliente/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-blue-700 hover:bg-blue-50 transition">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            <a href="../cliente/compras.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-blue-700 hover:bg-blue-50 transition">
                <i class="fas fa-shopping-bag"></i>
                <span>Mis Compras</span>
            </a>
        <?php endif; ?>

     
    </nav>
</aside>

<main class="flex-1">
    <header class="h-24 bg-gradient-to-r from-blue-800 to-sky-700 text-white px-8 flex items-center justify-between shadow-md">
        <h1 class="text-4xl font-light"><?= htmlspecialchars($titulo) ?></h1>

        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-white text-blue-700 flex items-center justify-center text-xl">
                <i class="fas fa-user"></i>
            </div>
            <div class="text-right flex flex-col">
                <div class="font-bold text-lg leading-tight"><?= htmlspecialchars(ucfirst($rol_nombre)) ?></div>
                <div class="text-sm text-blue-100 opacity-90"><?= htmlspecialchars($nombreCompleto) ?></div>
                <a href="../../controllers/auth/authController.php?accion=logout" class="mt-1 text-xs bg-red-500/20 hover:bg-red-500/40 text-red-100 px-2 py-1 rounded-md transition-all flex items-center justify-end gap-1 self-end w-fit">
                    <i class="fas fa-power-off text-[10px]"></i>
                    <span>Salir</span>
                </a>
            </div>
        </div>
    </header>

    <section class="p-8">