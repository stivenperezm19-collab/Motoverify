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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            overflow-x: hidden;
        }

        /* --- Layout --- */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .sidebar-brand .logo-icon { position: relative; width: 40px; height: 35px; }
        .sidebar-brand .logo-icon .fa-gear { font-size: 28px; color: #666; position: absolute; left: 0; top: 0; }
        .sidebar-brand .logo-icon .fa-wrench { font-size: 18px; color: #111; position: absolute; left: 10px; top: 5px; transform: rotate(45deg); }
        .sidebar-brand .logo-icon .fa-circle { font-size: 10px; color: #ff0000; position: absolute; left: 22px; top: 12px; }
        .sidebar-brand .logo-text { font-size: 1.8rem; font-weight: 800; font-style: italic; letter-spacing: -1px; line-height: 1; }
        .sidebar-brand .text-moto { color: #111; }
        .sidebar-brand .text-verify { color: #004ba8; }

        .btn-new {
            margin: 10px 24px 20px;
            border: 1px solid #e0e0e0;
            background: #ffffff;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #111;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-new:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-new i { color: #004ba8; font-size: 1.2rem; }

        .sidebar-nav {
            flex: 1;
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: #4b5563;
            font-weight: 600;
            font-size: 1.05rem;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .nav-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .nav-item:hover { background-color: #f3f4f6; color: #111; }
        
        .nav-item.active {
            background-color: #f0f7ff;
            color: #004ba8;
            position: relative;
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -16px; top: 10%;
            height: 80%; width: 4px;
            background-color: #004ba8;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-bottom {
            padding: 20px 16px;
            border-top: 1px solid #eaeaea;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #fbfbfd;
        }

        /* --- Topbar --- */
        .topbar {
            height: 80px;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #ffffff;
            border-bottom: 1px solid #eaeaea;
        }
        
        .search-container {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 5px 15px;
            width: 450px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .search-container input {
            border: none;
            outline: none;
            padding: 8px;
            flex: 1;
            font-size: 0.95rem;
            color: #333;
        }
        .search-container .divider { width: 1px; height: 24px; background-color: #e0e0e0; margin: 0 10px; }
        .search-filter { display: flex; align-items: center; gap: 8px; color: #004ba8; font-weight: 600; font-size: 0.95rem; cursor: pointer; }
        
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .btn-camera {
            background-color: #004ba8;
            color: white;
            border: none;
            width: 45px; height: 45px;
            border-radius: 12px;
            font-size: 1.2rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-camera:hover { background-color: #00367a; }
        
        .user-avatar {
            width: 45px; height: 45px;
            background-color: #e5e7eb;
            color: #6b7280;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
        }

        /* --- Dashboard Body --- */
        .dashboard-body {
            padding: 30px 40px;
        }

        /* Hero Banner */
        .hero-banner {
            background-color: #0640aa;
            border-radius: 0;
            padding: 35px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            margin: -30px -40px 30px -40px;
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            left: 40px; top: 35px; bottom: 35px;
            width: 4px;
            background-color: #3b82f6;
            border-radius: 4px;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding-left: 20px;
        }
        .hero-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 5px; }
        .hero-subtitle { font-size: 1rem; opacity: 0.9; margin: 0; }
        
        .hero-gears {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.1);
        }
        .hero-gears i.fa-gear:nth-child(1) { font-size: 120px; position: absolute; right: 0; top: -50px; }
        .hero-gears i.fa-gear:nth-child(2) { font-size: 70px; position: absolute; right: 90px; top: -80px; }

        /* Section Titles */
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }

        /* Marcas Destacadas */
        .brands-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            background: white;
            padding: 20px 30px;
            border-radius: 16px;
            border: 1px solid #f3f4f6;
        }
        .brand-logos {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        .brand-item {
            font-weight: 800; font-size: 1.2rem; font-style: italic; color: #4b5563;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-bajaj { color: #004ba8; }
        .brand-tvs { color: #111; } .brand-tvs span { color: #dc2626; }
        .brand-yamaha { color: #dc2626; }
        .brand-honda { color: #dc2626; }
        .brand-suzuki { color: #004ba8; }
        .brand-ktm { color: #ea580c; }
        
        .btn-view-all {
            border: 1px solid #bfdbfe;
            color: #2563eb;
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-view-all:hover { background: #f0f7ff; }

        /* Grilla Repuestos */
        .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .part-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #f3f4f6;
            position: relative;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .part-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        
        .btn-edit-card {
            position: absolute;
            top: 20px; right: 20px;
            width: 32px; height: 32px;
            border-radius: 8px;
            border: 1px solid #bfdbfe;
            background: white;
            color: #2563eb;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-edit-card:hover { background: #eff6ff; }
        
        .part-code { font-weight: 700; color: #111; font-size: 1.1rem; margin-bottom: 5px; }
        .part-name { font-size: 0.9rem; color: #4b5563; margin-bottom: 2px; }
        .part-brand { color: #2563eb; font-weight: 600; }
        .part-model { font-size: 0.85rem; color: #9ca3af; margin-bottom: 15px; }
        
        .part-image {
            width: 100%;
            height: 140px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .btn-next-card {
            position: absolute;
            bottom: 20px; right: 20px;
            color: #9ca3af;
            font-size: 1.2rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .btn-next-card:hover { color: #2563eb; }

        .btn-load-more {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid #bfdbfe;
            color: #2563eb;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            margin: 0 auto;
            transition: background 0.2s;
        }
        .btn-load-more:hover { background: #eff6ff; }

    </style>
</head>
<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="dashboard.php" class="sidebar-brand">
                <div class="logo-icon">
                    <i class="fa-solid fa-gear"></i>
                    <i class="fa-solid fa-wrench"></i>
                    <i class="fa-solid fa-circle"></i>
                </div>
                <div class="logo-text">
                    <span class="text-moto">moto</span><span class="text-verify">verify</span>
                </div>
            </a>

            <div class="btn-new">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo</span>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">
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

            <div class="sidebar-bottom sidebar-nav">
                <a href="#" class="nav-item">
                    <i class="fa-regular fa-circle-question"></i> Ayuda
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-gear"></i> Configuración
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            
            <!-- Topbar -->
            <header class="topbar">
                <div style="flex:1"></div> <!-- Spacer for flex alignment if search is centered in future, but mockup shows it right-aligned near actions -->
                
                <div class="d-flex align-items-center gap-4">
                    <div class="search-container">
                        <input type="text" placeholder="Pulsar135/180/NS200" value="Pulsar135/180/NS200">
                        <div class="divider"></div>
                        <div class="search-filter">
                            Filtro <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>

                    <div class="topbar-actions">
                        <button class="btn-camera" title="Búsqueda IA">
                            <i class="fa-solid fa-camera"></i>
                        </button>
                        <a href="perfil.php" class="user-avatar" title="Perfil" style="text-decoration:none;">
                            <i class="fa-regular fa-user"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Dashboard Body -->
            <div class="dashboard-body">
                
                <!-- Hero Banner -->
                <div class="hero-banner">
                    <div class="hero-content">
                        <h1 class="hero-title">Bienvenido a Motoverify</h1>
                        <p class="hero-subtitle">Encuentra y verifica repuestos de forma rápida y confiable.</p>
                    </div>
                    <div class="hero-gears">
                        <i class="fa-solid fa-gear"></i>
                        <i class="fa-solid fa-gear"></i>
                    </div>
                </div>

                <!-- Marcas Destacadas -->
                <h3 class="section-title">Marcas destacadas</h3>
                <div class="brands-container">
                    <div class="brand-logos">
                        <!-- Simulated Logos with Text for demonstration since images aren't provided -->
                        <div class="brand-item brand-bajaj">
                            BAJAJ
                        </div>
                        <div class="brand-item brand-tvs">
                            TVS<span>★</span>
                        </div>
                        <div class="brand-item brand-yamaha">
                            YAMAHA
                        </div>
                        <div class="brand-item brand-honda">
                            HONDA
                        </div>
                        <div class="brand-item brand-suzuki">
                            SUZUKI
                        </div>
                        <div class="brand-item brand-ktm">
                            KTM
                        </div>
                    </div>
                    <a href="#" class="btn-view-all">Ver todas <i class="fa-solid fa-chevron-right"></i></a>
                </div>

                <!-- Repuestos Grid -->
                <h3 class="section-title">Repuestos</h3>
                <div class="parts-grid">
                    
                    <!-- Tarjeta 1 -->
                    <div class="part-card">
                        <div class="btn-edit-card"><i class="fa-solid fa-pen"></i></div>
                        <div class="part-code">5901100200030</div>
                        <div class="part-name"><span class="part-brand">Vitrix</span> Guardabarros<br>delantero</div>
                        <div class="part-model">Xr125L</div>
                        <img src="https://motosyeze.com/wp-content/uploads/2021/04/guardabarro-delantero-xr-125-150-blanco-original.jpg" alt="Guardabarros" class="part-image" onerror="this.src='https://via.placeholder.com/150?text=Guardabarros'">
                        <a href="#" class="btn-next-card"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <!-- Tarjeta 2 -->
                    <div class="part-card">
                        <div class="btn-edit-card"><i class="fa-solid fa-pen"></i></div>
                        <div class="part-code">5901100200030</div>
                        <div class="part-name"><span class="part-brand">Vitrix</span> Farola<br>delantera</div>
                        <div class="part-model">Xr125L</div>
                        <img src="https://i.pinimg.com/736x/8d/6d/eb/8d6debccf2070e1762c2f6d0f5cd3d59.jpg" alt="Farola" class="part-image" onerror="this.src='https://via.placeholder.com/150?text=Farola'">
                        <a href="#" class="btn-next-card"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <!-- Tarjeta 3 -->
                    <div class="part-card">
                        <div class="btn-edit-card"><i class="fa-solid fa-pen"></i></div>
                        <div class="part-code">5901100200030</div>
                        <div class="part-name"><span class="part-brand">Vitrix</span> Espejo<br>izquierdo</div>
                        <div class="part-model">Xr125L</div>
                        <img src="https://cdn.shopify.com/s/files/1/0273/2559/5725/products/espejo-retrovisor-izquierdo-honda-xr-125l-original-900x900_1024x1024@2x.jpg" alt="Espejo" class="part-image" onerror="this.src='https://via.placeholder.com/150?text=Espejo'">
                        <a href="#" class="btn-next-card"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <!-- Tarjeta 4 -->
                    <div class="part-card">
                        <div class="btn-edit-card"><i class="fa-solid fa-pen"></i></div>
                        <div class="part-code">5901100200030</div>
                        <div class="part-name"><span class="part-brand">Vitrix</span> Disco freno<br>delantero</div>
                        <div class="part-model">Xr125L</div>
                        <img src="https://http2.mlstatic.com/D_NQ_NP_907338-MCO48106132717_112021-O.webp" alt="Disco Freno" class="part-image" onerror="this.src='https://via.placeholder.com/150?text=Disco+Freno'">
                        <a href="#" class="btn-next-card"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <!-- Tarjeta 5 -->
                    <div class="part-card">
                        <div class="btn-edit-card"><i class="fa-solid fa-pen"></i></div>
                        <div class="part-code">5901100200030</div>
                        <div class="part-name"><span class="part-brand">Vitrix</span> Sprocket<br>trasero</div>
                        <div class="part-model">Xr125L</div>
                        <img src="https://http2.mlstatic.com/D_NQ_NP_667232-MCO44558296307_012021-O.jpg" alt="Sprocket" class="part-image" onerror="this.src='https://via.placeholder.com/150?text=Sprocket'">
                        <a href="#" class="btn-next-card"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <!-- Tarjeta 6 -->
                    <div class="part-card">
                        <div class="btn-edit-card"><i class="fa-solid fa-pen"></i></div>
                        <div class="part-code">5901100200030</div>
                        <div class="part-name"><span class="part-brand">Vitrix</span> Stop<br>trasero</div>
                        <div class="part-model">Xr125L</div>
                        <img src="https://http2.mlstatic.com/D_NQ_NP_736611-MCO43685603417_102020-O.jpg" alt="Stop Trasero" class="part-image" onerror="this.src='https://via.placeholder.com/150?text=Stop'">
                        <a href="#" class="btn-next-card"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    
                </div>

                <div class="text-center">
                    <a href="#" class="btn-load-more">Ver más repuestos <i class="fa-solid fa-chevron-down"></i></a>
                </div>

            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
