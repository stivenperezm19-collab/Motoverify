<?php
session_start();
// Validación básica de sesión, descomentar cuando esté lista
/*
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/index.php');
    exit;
}
*/
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; color: #333; overflow-x: hidden; }

        /* ===== TOPBAR GLOBAL ===== */
        .topbar-global {
            height: 110px;
            background: #ffffff;
            border-bottom: 1px solid #eaeaea;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1050;
        }
        .topbar-logo { display: inline-flex; align-items: center; height: 100%; text-decoration: none; }
        .topbar-logo img { max-height: 85px; width: auto; object-fit: contain; object-position: left center; margin-left: 5px; padding-right: 15px; }

        .search-container { background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .search-container input { border: none; outline: none; box-shadow: none; }
        
        /* ===== SIDEBAR ===== */
        .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid #eaeaea; position: fixed; top: 110px; bottom: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: #4b5563; font-weight: 600; font-size: 1.05rem; text-decoration: none; border-radius: 10px; transition: all 0.2s; margin: 0 16px 8px; }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        .nav-item.active { background-color: #f0f7ff; color: #004ba8; position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -16px; top: 10%; height: 80%; width: 4px; background-color: #004ba8; border-radius: 0 4px 4px 0; }
        
        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left: 260px; margin-top: 110px; min-height: calc(100vh - 110px); display: flex; flex-direction: column; background-color: #f8f9fc; }
        
        /* Hero Banner */
        .hero-banner { background: linear-gradient(135deg, #0640aa 0%, #1e5abf 100%); color: white; position: relative; overflow: hidden; }
        .hero-banner::before { content: ''; position: absolute; left: 40px; top: 25%; height: 50%; width: 4px; background-color: #60a5fa; border-radius: 4px; }
        .hero-gears { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); pointer-events: none; }

        /* Section Title */
        .section-title { font-weight: 700; font-size: 1.1rem; color: #1f2937; margin-bottom: 18px; }

        /* Brands */
        .brands-card { background: #fff; border: 1px solid #f0f1f3; border-radius: 14px; padding: 18px 28px; margin-bottom: 32px; }
        .brand-item { font-weight: 800; font-size: 1.1rem; font-style: italic; color: #4b5563; cursor: pointer; transition: opacity 0.2s; }
        .brand-item:hover { opacity: 0.7; }
        .brand-bajaj { color: #004ba8; }
        .brand-tvs { color: #111; }
        .brand-tvs .star { color: #dc2626; }
        .brand-yamaha { color: #dc2626; }
        .brand-honda { color: #dc2626; }
        .brand-suzuki { color: #004ba8; }
        .brand-hero { color: #111; }
        .brand-hero .accent { color: #dc2626; }
        .brand-ktm { color: #ea580c; }
        .brand-akt { color: #111; }
        .btn-ver-todas { border: 1px solid #bfdbfe; color: #2563eb; background: #fff; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; text-decoration: none; transition: background 0.2s; }
        .btn-ver-todas:hover { background: #eff6ff; color: #1d4ed8; }

        /* Parts */
        .part-card { background: #fff; border-radius: 14px; padding: 18px 20px; border: 1px solid #f0f1f3; position: relative; transition: box-shadow 0.2s, transform 0.2s; }
        .part-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.06); transform: translateY(-2px); }
        .part-card .btn-edit { position: absolute; top: 16px; right: 16px; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #bfdbfe; background: #fff; color: #2563eb; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; font-size: 0.8rem; }
        .part-card .btn-edit:hover { background: #eff6ff; }
        .part-code { font-weight: 700; color: #111; font-size: 1rem; margin-bottom: 4px; }
        .part-name { font-size: 0.88rem; color: #4b5563; line-height: 1.35; margin-bottom: 2px; }
        .part-brand { color: #2563eb; font-weight: 600; }
        .part-model { font-size: 0.82rem; color: #9ca3af; font-style: italic; }
        .part-img { width: 120px; height: 100px; object-fit: contain; flex-shrink: 0; }
        .part-card .btn-arrow { position: absolute; bottom: 16px; right: 16px; color: #c4c9d4; font-size: 1rem; text-decoration: none; transition: color 0.2s; }
        .part-card .btn-arrow:hover { color: #2563eb; }

        /* Load More */
        .btn-load-more { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #bfdbfe; color: #2563eb; font-weight: 600; padding: 10px 22px; border-radius: 10px; text-decoration: none; transition: background 0.2s; }
        .btn-load-more:hover { background: #eff6ff; }
    </style>
</head>
<body>

    <!-- ===== TOPBAR GLOBAL (full-width con logo) ===== -->
    <header class="topbar-global px-4 d-flex align-items-center justify-content-between">
        <!-- Logo -->
        <a href="dashboard.php" class="topbar-logo text-decoration-none">
            <img src="assets/logo_motoverify.png?v=<?php echo time(); ?>" alt="Motoverify">
        </a>

        <!-- Search + Actions -->
        <div class="d-flex align-items-center gap-3">
            <div class="search-container d-flex align-items-center" style="width: 400px;">
                <input type="text" class="form-control" placeholder="Pulsar135/180/NS200" value="Pulsar135/180/NS200">
                <div class="vr mx-2"></div>
                <span class="text-primary fw-semibold" style="cursor:pointer; white-space:nowrap;">
                    Filtro <i class="fa-solid fa-chevron-down ms-1"></i>
                </span>
            </div>
            <button class="btn btn-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 12px;">
                <i class="fa-solid fa-camera fs-5"></i>
            </button>
            <a href="perfil.php" class="d-flex align-items-center justify-content-center bg-light text-secondary rounded-circle text-decoration-none" style="width: 45px; height: 45px; font-size: 1.2rem;">
                <i class="fa-regular fa-user"></i>
            </a>
        </div>
    </header>

    <!-- ===== SIDEBAR (debajo del topbar) ===== -->
    <aside class="sidebar">
        <div class="px-3 mb-3 mt-3">
            <button class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold border" style="border-radius: 12px; padding: 12px;">
                <i class="fa-solid fa-plus text-primary"></i> Nuevo
            </button>
        </div>

        <nav class="flex-grow-1">
            <a href="dashboard.php" class="nav-item active">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
            <a href="marcas.php" class="nav-item">
                <i class="fa-regular fa-star"></i> Marcas
            </a>
            <a href="repuestos.php" class="nav-item">
                <i class="fa-solid fa-puzzle-piece"></i> Repuestos
            </a>
            <a href="catalogo.php" class="nav-item">
                <i class="fa-solid fa-book"></i> Catálogo
            </a>
        </nav>

        <div class="mt-auto border-top pt-3 pb-3">
            <a href="#" class="nav-item">
                <i class="fa-regular fa-circle-question"></i> Ayuda
            </a>
            <a href="perfil.php" class="nav-item">
                <i class="fa-solid fa-gear"></i> Configuración
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">

        <div class="p-4 flex-grow-1">

            <!-- Hero Banner -->
            <div class="hero-banner rounded-3 p-4 ps-5 mb-4 d-flex align-items-center">
                <div class="position-relative" style="z-index: 2; padding-left: 15px;">
                    <h2 class="fw-bold mb-2">Bienvenido a Motoverify</h2>
                    <p class="mb-0 opacity-75" style="font-size: 1rem;">Encuentra y verifica repuestos de forma rápida y confiable.</p>
                </div>
                <div class="hero-gears">
                    <i class="fa-solid fa-gear" style="font-size: 110px; color: rgba(255,255,255,0.08); position: absolute; right: 0; top: -55px;"></i>
                    <i class="fa-solid fa-gear" style="font-size: 65px; color: rgba(255,255,255,0.06); position: absolute; right: 85px; top: -25px;"></i>
                </div>
            </div>

            <!-- Marcas Destacadas -->
            <h3 class="section-title">Marcas destacadas</h3>
            <div class="brands-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="brand-item brand-bajaj">BAJAJ</div>
                    <div class="brand-item brand-tvs">TVS <span class="star">★</span></div>
                    <div class="brand-item brand-yamaha">YAMAHA</div>
                    <div class="brand-item brand-honda">HONDA</div>
                    <div class="brand-item brand-suzuki">SUZUKI</div>
                    <div class="brand-item brand-hero">Hero<span class="accent">★</span></div>
                    <div class="brand-item brand-ktm">KTM</div>
                    <div class="brand-item brand-akt">AKT</div>
                </div>
                <a href="marcas.php" class="btn-ver-todas d-inline-flex align-items-center gap-2">
                    Ver todas <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                </a>
            </div>

            <!-- Repuestos -->
            <h3 class="section-title">Repuestos</h3>
            <div class="row g-4 mb-4">

                <!-- Tarjeta 1 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="part-card h-100">
                        <button class="btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="flex-grow-1">
                                <div class="part-code">5901100200030</div>
                                <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                                <div class="part-model">Xr125L</div>
                            </div>
                            <img src="https://placehold.co/150x100?text=Guardabarros" alt="Guardabarros" class="part-img">
                        </div>
                        <a href="#" class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Tarjeta 2 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="part-card h-100">
                        <button class="btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="flex-grow-1">
                                <div class="part-code">5901100200030</div>
                                <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                                <div class="part-model">Xr125L</div>
                            </div>
                            <img src="https://placehold.co/150x100?text=Farola" alt="Farola" class="part-img">
                        </div>
                        <a href="#" class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Tarjeta 3 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="part-card h-100">
                        <button class="btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="flex-grow-1">
                                <div class="part-code">5901100200030</div>
                                <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                                <div class="part-model">Xr125L</div>
                            </div>
                            <img src="https://placehold.co/150x100?text=Espejo" alt="Espejo" class="part-img">
                        </div>
                        <a href="#" class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Tarjeta 4 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="part-card h-100">
                        <button class="btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="flex-grow-1">
                                <div class="part-code">5901100200030</div>
                                <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                                <div class="part-model">Xr125L</div>
                            </div>
                            <img src="https://placehold.co/150x100?text=Disco Freno" alt="Disco Freno" class="part-img">
                        </div>
                        <a href="#" class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Tarjeta 5 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="part-card h-100">
                        <button class="btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="flex-grow-1">
                                <div class="part-code">5901100200030</div>
                                <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                                <div class="part-model">Xr125L</div>
                            </div>
                            <img src="https://placehold.co/150x100?text=Sprocket" alt="Sprocket" class="part-img">
                        </div>
                        <a href="#" class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Tarjeta 6 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="part-card h-100">
                        <button class="btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="flex-grow-1">
                                <div class="part-code">5901100200030</div>
                                <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                                <div class="part-model">Xr125L</div>
                            </div>
                            <img src="https://placehold.co/150x100?text=Stop Trasero" alt="Stop Trasero" class="part-img">
                        </div>
                        <a href="#" class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

            </div>

            <!-- Load More -->
            <div class="text-center">
                <a href="#" class="btn-load-more">Ver más repuestos <i class="fa-solid fa-chevron-down" style="font-size:0.75rem;"></i></a>
            </div>

        </div>
    </main>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
