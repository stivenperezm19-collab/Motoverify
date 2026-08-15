<?php
require_once '../config/database.php';

$search = $_GET['search'] ?? '';
$marca_id = $_GET['marca'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';
$modelo = $_GET['modelo'] ?? '';

// Fetch filters
$marcas = $conn->query("SELECT id, nombre FROM marcas ORDER BY nombre");
$categorias = $conn->query("SELECT id, nombre FROM catalogos ORDER BY nombre");
$modelos = $conn->query("SELECT DISTINCT modelo_moto FROM repuesto_compatibilidad ORDER BY modelo_moto");

// Build search query
$query = "SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre 
          FROM repuestos r
          LEFT JOIN marcas m ON r.marca_id = m.id
          LEFT JOIN catalogos c ON r.catalogo_id = c.id
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (r.nombre LIKE ? OR r.codigo_barras = ?)";
    $params[] = "%$search%";
    $params[] = $search;
    $types .= "ss";
}

if (!empty($marca_id)) {
    $query .= " AND r.marca_id = ?";
    $params[] = $marca_id;
    $types .= "i";
}

if (!empty($categoria_id)) {
    $query .= " AND r.catalogo_id = ?";
    $params[] = $categoria_id;
    $types .= "i";
}

if (!empty($modelo)) {
    $query .= " AND r.id IN (SELECT repuesto_id FROM repuesto_compatibilidad WHERE modelo_moto = ?)";
    $params[] = $modelo;
    $types .= "s";
}

$is_search = !empty($search) || !empty($marca_id) || !empty($categoria_id) || !empty($modelo);

if ($is_search) {
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $repuestos = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre FROM repuestos r LEFT JOIN marcas m ON r.marca_id = m.id LEFT JOIN catalogos c ON r.catalogo_id = c.id LIMIT 5");
    if($result) {
        $repuestos = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $repuestos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoverify - Búsqueda</title>
    
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
        }

        /* --- Navbar --- */
        .navbar {
            padding: 1.2rem 0;
            background-color: white;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .logo-icon-container {
            position: relative;
            width: 45px;
            height: 40px;
            margin-right: 5px;
        }
        .logo-text {
            font-size: 2.2rem;
            font-weight: 800;
            font-style: italic;
            letter-spacing: -1.5px;
            line-height: 1;
        }
        .logo-text-moto { color: #1a1a1a; }
        .logo-text-verify { color: #004ba8; }

        .user-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #001b4d;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.05rem;
        }
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1.5px solid #004ba8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #004ba8;
            background-color: white;
            font-size: 1.2rem;
        }

        /* --- Hero Section --- */
        .hero-section {
            background-color: #004ba8;
            position: relative;
            padding: 40px 0 120px;
            color: white;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
            margin-bottom: -60px;
            z-index: 1;
        }

        .bg-decorations {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .gear-icon { position: absolute; color: rgba(255, 255, 255, 0.08); }
        .gear-1 { font-size: 140px; top: -30px; left: -20px; }
        .gear-2 { font-size: 90px; top: 120px; left: 60px; }
        .gear-3 { font-size: 170px; top: -40px; right: -50px; }
        .gear-4 { font-size: 80px; top: 150px; right: 50px; }

        .dots-pattern {
            position: absolute;
            width: 150px; height: 150px;
            background-image: radial-gradient(rgba(255, 255, 255, 0.2) 2px, transparent 2px);
            background-size: 20px 20px;
        }
        .dots-left { top: 30px; left: 40px; }
        .dots-right { bottom: 80px; right: 40px; }

        .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero-title {
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 35px;
        }

        /* --- Search Box --- */
        .search-container {
            max-width: 650px;
            margin: 0 auto;
        }
        .search-box-outer {
            background-color: #091f43;
            border-radius: 20px;
            padding: 6px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        .search-box-inner {
            background-color: white;
            border-radius: 14px;
            padding: 15px 25px;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .search-input-area {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 12px;
            margin-bottom: 12px;
            padding-right: 60px;
            position: relative;
        }
        .search-input-area input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 1.15rem;
            font-weight: 500;
            color: #212529;
        }
        .search-input-area input::placeholder { color: #888; }
        
        .search-filters {
            display: flex;
            align-items: center;
            min-height: 35px;
        }
        .filter-text {
            color: #004ba8;
            font-weight: 800;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .filter-text i { color: #a0a0a0; font-size: 1.3rem; }

        .camera-button {
            position: absolute;
            right: 15px;
            bottom: 10px;
            background-color: #091f43;
            color: white;
            border: none;
            width: 60px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .camera-button:hover {
            background-color: #001b4d;
        }

        /* --- Results List --- */
        .results-section {
            position: relative;
            z-index: 2;
            max-width: 850px;
            margin: 0 auto 40px;
            padding: 0 15px;
        }
        .results-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
            overflow: hidden;
        }
        .result-item {
            display: flex;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            color: inherit;
            transition: background-color 0.2s;
        }
        .result-item:last-child { border-bottom: none; }
        .result-item:hover { background-color: #f8f9fa; color: inherit; }
        .result-icon {
            width: 45px;
            height: 45px;
            background-color: #004ba8;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 25px;
        }
        .result-text {
            font-weight: 600;
            color: #001b4d;
            flex-grow: 1;
            font-size: 1.15rem;
        }
        .result-arrow { color: #007bff; font-size: 1.2rem; }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .no-results i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }

        /* --- AI Modal Styles --- */
        #imagePreviewContainer {
            width: 100%;
            height: 350px;
            border: 2px dashed #ccc;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
            position: relative;
        }
        #imagePreviewContainer img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
            background-color: #000;
        }
        .upload-placeholder {
            text-align: center;
            color: #888;
            padding: 20px;
        }
        .upload-placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #004ba8;
        }
        #aiStatus {
            margin-top: 15px;
        }
        
        #cameraContainer {
            border-radius: 10px; 
            overflow: hidden; 
            background: #000; 
            height: 350px;
            width: 100%;
            display: none;
        }
        /* --- Login Modal Styles --- */
        .modal-login .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            background: transparent;
            box-shadow: none;
        }
        .modal-login .modal-dialog {
            max-width: 480px;
        }
        .modal-login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            padding: 40px 50px;
            position: relative;
        }
        /* Background overlays for the modal */
        .modal-login-bg-top {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 120px;
            background-color: #0640aa;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 50% 100%, 0 90%);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            z-index: 1;
        }
        .modal-login-bg-bottom {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 150px;
            background-color: #f8f9fc;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            z-index: 0;
        }
        .login-content { position: relative; z-index: 10; }
        
        .btn-close-card {
            position: absolute;
            top: -15px; right: -15px;
            width: 35px; height: 35px;
            background-color: #0d2859;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #ff3333; font-size: 1.2rem;
            text-decoration: none; border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s; z-index: 20;
            cursor: pointer;
        }
        .btn-close-card:hover { transform: scale(1.1); color: #ff0000; }
        
        .avatar-container { text-align: center; margin-bottom: 15px; margin-top: 10px; }
        .avatar-circle {
            width: 80px; height: 80px; background-color: #d8eaff;
            border-radius: 50%; display: inline-flex; align-items: center;
            justify-content: center; color: #0066ff; font-size: 2.2rem; position: relative;
        }
        .avatar-badge {
            position: absolute; top: 0; right: -5px; background-color: #0066ff;
            color: white; width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; border: 2px solid white;
        }
        
        .login-title {
            text-align: center; font-weight: 800; color: #0b2253; font-size: 1.6rem;
            margin-bottom: 30px; position: relative;
        }
        .login-title::after {
            content: ''; position: absolute; bottom: -10px; left: 50%;
            transform: translateX(-50%); width: 40px; height: 3px;
            background-color: #0066ff; border-radius: 2px;
        }
        
        .modal-login .form-label {
            font-weight: 700; color: #0b2253; font-size: 0.95rem;
            margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
        }
        .modal-login .form-control-custom {
            width: 100%; padding: 12px 15px; border: 2px solid #3b82f6;
            border-radius: 10px; font-size: 1rem; color: #333; outline: none;
            margin-bottom: 25px; background-color: #fcfcfd; transition: box-shadow 0.2s;
        }
        .modal-login .form-control-custom:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .modal-login .form-control-custom::placeholder { color: #a0aec0; font-weight: 500; }
        
        .forgot-pass { text-align: right; margin-top: -15px; margin-bottom: 30px; }
        .forgot-pass a { color: #3b82f6; font-weight: 600; font-size: 0.85rem; text-decoration: none; }
        .forgot-pass a:hover { text-decoration: underline; }
        
        .btn-submit {
            background-color: #062b80; color: white; width: 100%; padding: 14px;
            border: none; border-radius: 50px; font-weight: 700; font-size: 1.1rem;
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-submit:hover { background-color: #041c5c; }
        
        .alert-error {
            background-color: #fee2e2; color: #b91c1c; border: 1px solid #f87171;
            padding: 10px 15px; border-radius: 8px; font-size: 0.9rem;
            font-weight: 500; margin-bottom: 20px; text-align: center;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar border-bottom">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand" href="index.php">
                <div class="logo-icon-container">
                    <i class="fa-solid fa-gear text-secondary fs-3 position-absolute" style="left: 0; top: 0;"></i>
                    <i class="fa-solid fa-wrench text-dark fs-5 position-absolute" style="left: 12px; top: 5px; transform: rotate(45deg);"></i>
                    <i class="fa-solid fa-circle text-danger position-absolute" style="font-size: 12px; left: 24px; top: 15px;"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-text-moto">moto</span><span class="logo-text-verify">verify</span>
                </div>
            </a>
            
            <div class="d-flex">
                <a href="#" class="user-dropdown-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <span class="d-none d-sm-inline">Administrador</span>
                    <i class="fa-solid fa-chevron-down ms-1 fs-6"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="bg-decorations">
            <i class="fa-solid fa-gear gear-icon gear-1"></i>
            <i class="fa-solid fa-gear gear-icon gear-2"></i>
            <i class="fa-solid fa-gear gear-icon gear-3"></i>
            <i class="fa-solid fa-gear gear-icon gear-4"></i>
            <div class="dots-pattern dots-left"></div>
            <div class="dots-pattern dots-right"></div>
        </div>

        <div class="hero-content container">
            <h1 class="hero-title">Búsqueda</h1>
            
            <div class="search-container">
                <form id="searchForm" method="GET" action="index.php">
                    <div class="search-box-outer">
                        <div class="search-box-inner">
                            <div class="search-input-area">
                                <input type="text" name="search" placeholder="Pulsar135/180/NS200" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="search-filters">
                                <span class="filter-text" data-bs-toggle="modal" data-bs-target="#filtrosModal">
                                    Filtros <?= (!empty($marca_id) || !empty($categoria_id) || !empty($modelo)) ? '<span class="badge bg-primary ms-2">Activos</span>' : '' ?> <i class="fa-solid fa-caret-down"></i>
                                </span>
                            </div>
                            <button type="button" class="camera-button" title="Búsqueda por foto IA" data-bs-toggle="modal" data-bs-target="#iaModal">
                                <i class="fa-solid fa-camera"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Results Section -->
    <section class="results-section">
        <div class="results-list">
            
            <?php if ($is_search && empty($repuestos)): ?>
                <div class="no-results">
                    <i class="fa-solid fa-box-open"></i>
                    <h4>Sin coincidencias</h4>
                    <p>No encontramos repuestos con los criterios actuales.</p>
                    <a href="index.php" class="btn btn-outline-primary mt-2">Limpiar filtros y búsqueda</a>
                </div>
            <?php elseif (!empty($repuestos)): ?>
                <?php foreach ($repuestos as $repuesto): ?>
                    <a href="ficha_tecnica.php?id=<?= $repuesto['id'] ?>" class="result-item">
                        <div class="result-icon">
                            <i class="fa-regular <?= $is_search ? 'fa-box' : 'fa-clock' ?>"></i>
                        </div>
                        <div class="result-text">
                            <?= htmlspecialchars($repuesto['nombre']) ?> 
                            <?php if(!empty($repuesto['marca_nombre'])): ?>
                                <small class="text-muted d-block" style="font-size: 0.85em; font-weight: normal;">Marca: <?= htmlspecialchars($repuesto['marca_nombre']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="result-arrow">
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="no-results">
                    <p>Utiliza la barra de búsqueda o la cámara IA para encontrar repuestos.</p>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Modal de Filtros -->
    <div class="modal fade" id="filtrosModal" tabindex="-1" aria-labelledby="filtrosModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-primary">Filtros de Búsqueda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Marca</label>
                <select name="marca" class="form-select" form="searchForm">
                    <option value="">Todas las marcas</option>
                    <?php if($marcas) while($m = $marcas->fetch_assoc()): ?>
                        <option value="<?= $m['id'] ?>" <?= $m['id'] == $marca_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Categoría</label>
                <select name="categoria" class="form-select" form="searchForm">
                    <option value="">Todas las categorías</option>
                    <?php if($categorias) while($c = $categorias->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $categoria_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Modelo de Moto (Compatibilidad)</label>
                <select name="modelo" class="form-select" form="searchForm">
                    <option value="">Todos los modelos</option>
                    <?php if($modelos) while($mod = $modelos->fetch_assoc()): ?>
                        <?php if(!empty($mod['modelo_moto'])): ?>
                            <option value="<?= htmlspecialchars($mod['modelo_moto']) ?>" <?= $mod['modelo_moto'] == $modelo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mod['modelo_moto']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </select>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php'">Limpiar</button>
            <button type="submit" form="searchForm" class="btn btn-primary">Aplicar Filtros</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal IA (Reconocimiento Visual) -->
    <div class="modal fade" id="iaModal" tabindex="-1" aria-labelledby="iaModalLabel" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-microchip me-2"></i>Reconocimiento Visual IA</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseIaModal"></button>
          </div>
          <div class="modal-body text-center py-4">
            
            <!-- Contenedor Cámara en Vivo -->
            <div id="cameraContainer" class="position-relative mb-3">
                <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                <button class="btn btn-light position-absolute bottom-0 start-50 translate-middle-x mb-3 rounded-circle shadow d-flex align-items-center justify-content-center" id="btnSnapPhoto" style="width: 60px; height: 60px; z-index: 10;">
                    <i class="fa-solid fa-camera fs-3 text-primary"></i>
                </button>
                <button class="btn btn-dark position-absolute top-0 end-0 m-2 rounded-circle shadow-sm" id="btnSwitchCamera" style="z-index: 10;" title="Cambiar cámara">
                    <i class="fa-solid fa-camera-rotate"></i>
                </button>
            </div>

            <!-- Canvas oculto para capturar el frame -->
            <canvas id="cameraCanvas" class="d-none"></canvas>

            <!-- Input de archivo (fallback) -->
            <input type="file" id="aiImageInput" accept="image/*" class="d-none">
            
            <!-- Contenedor Preview -->
            <div id="imagePreviewContainer">
                <div class="upload-placeholder" id="uploadPlaceholder">
                    <i class="fa-solid fa-image"></i>
                    <p class="mb-2 fw-semibold">Selecciona o toma una foto del repuesto</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary me-2" id="btnStartCamera">
                            <i class="fa-solid fa-video me-1"></i> Abrir Cámara
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('aiImageInput').click()">
                            <i class="fa-solid fa-folder-open me-1"></i> Archivo
                        </button>
                    </div>
                </div>
                <img id="imagePreview" alt="Vista previa del repuesto">
                <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 d-none shadow-sm" id="btnRetakePhoto" title="Descartar foto">
                    <i class="fa-solid fa-trash me-1"></i> Eliminar
                </button>
            </div>

            <div id="aiStatus" class="d-none mt-3">
                <div class="d-flex align-items-center justify-content-center text-primary fw-bold" id="aiLoading">
                    <div class="spinner-border me-2" role="status">
                        <span class="visually-hidden">Analizando...</span>
                    </div>
                    Analizando imagen con IA...
                </div>
                <div class="alert alert-danger mt-3 d-none" id="aiErrorMsg"></div>
                <div class="alert alert-success mt-3 d-none" id="aiSuccessMsg"></div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="btnCancelIa">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnAnalyzeIa" disabled>
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Identificar Repuesto
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const fileInput = document.getElementById('aiImageInput');
            const previewContainer = document.getElementById('imagePreviewContainer');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const imagePreview = document.getElementById('imagePreview');
            const btnAnalyze = document.getElementById('btnAnalyzeIa');
            const statusDiv = document.getElementById('aiStatus');
            const loadingDiv = document.getElementById('aiLoading');
            const errorMsg = document.getElementById('aiErrorMsg');
            const successMsg = document.getElementById('aiSuccessMsg');
            const btnClose = document.getElementById('btnCloseIaModal');
            const btnCancel = document.getElementById('btnCancelIa');
            const btnRetakePhoto = document.getElementById('btnRetakePhoto');
            
            // Elementos de la Cámara
            const cameraContainer = document.getElementById('cameraContainer');
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const btnStartCamera = document.getElementById('btnStartCamera');
            const btnSnapPhoto = document.getElementById('btnSnapPhoto');
            const btnSwitchCamera = document.getElementById('btnSwitchCamera');

            let currentStream = null;
            let useFrontCamera = false;
            let photoBlob = null; // Guardará la imagen a enviar (blob o file)

            // Función para detener la cámara
            function stopMediaTracks(stream) {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            }

            // Función para iniciar la cámara
            async function startCamera() {
                stopMediaTracks(currentStream);
                
                const constraints = {
                    video: {
                        facingMode: useFrontCamera ? "user" : "environment"
                    }
                };
                
                try {
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    currentStream = stream;
                    video.srcObject = stream;
                    
                    previewContainer.style.display = 'none';
                    cameraContainer.style.display = 'block';
                    btnAnalyze.disabled = true;
                    photoBlob = null;
                } catch (err) {
                    console.error("Error al acceder a la cámara: ", err);
                    alert("No se pudo acceder a la cámara. Revisa los permisos de tu navegador o usa la opción de subir archivo.");
                }
            }

            // Eventos de Cámara
            btnStartCamera.addEventListener('click', startCamera);

            btnSwitchCamera.addEventListener('click', () => {
                useFrontCamera = !useFrontCamera;
                startCamera();
            });

            // Tomar la foto
            btnSnapPhoto.addEventListener('click', () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                
                // Convertir el canvas a Blob (imagen)
                canvas.toBlob((blob) => {
                    photoBlob = blob;
                    const imageUrl = URL.createObjectURL(blob);
                    
                    imagePreview.src = imageUrl;
                    
                    // Apagar cámara y mostrar preview
                    stopMediaTracks(currentStream);
                    cameraContainer.style.display = 'none';
                    previewContainer.style.display = 'flex';
                    uploadPlaceholder.style.display = 'none';
                    imagePreview.style.display = 'block';
                    
                    btnRetakePhoto.classList.remove('d-none');
                    btnAnalyze.disabled = false;
                }, 'image/jpeg', 0.9);
            });

            // Resetear el modal al cerrar
            const iaModal = document.getElementById('iaModal');
            iaModal.addEventListener('hidden.bs.modal', function () {
                stopMediaTracks(currentStream);
                cameraContainer.style.display = 'none';
                previewContainer.style.display = 'flex';
                
                fileInput.value = '';
                imagePreview.style.display = 'none';
                btnRetakePhoto.classList.add('d-none');
                uploadPlaceholder.style.display = 'block';
                
                btnAnalyze.disabled = true;
                photoBlob = null;
                
                statusDiv.classList.add('d-none');
                errorMsg.classList.add('d-none');
                successMsg.classList.add('d-none');
                loadingDiv.classList.add('d-none');
            });

            // Botón para descartar foto y volver al inicio del modal
            btnRetakePhoto.addEventListener('click', () => {
                imagePreview.style.display = 'none';
                btnRetakePhoto.classList.add('d-none');
                uploadPlaceholder.style.display = 'block';
                btnAnalyze.disabled = true;
                photoBlob = null;
                fileInput.value = '';
            });

            // Manejar selección de archivo (Fallback)
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    photoBlob = file;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        uploadPlaceholder.style.display = 'none';
                        imagePreview.style.display = 'block';
                        btnRetakePhoto.classList.remove('d-none');
                        btnAnalyze.disabled = false;
                        
                        statusDiv.classList.add('d-none');
                        errorMsg.classList.add('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Manejar análisis de IA
            btnAnalyze.addEventListener('click', function() {
                if (!photoBlob) return;

                // UI Loading state
                btnAnalyze.disabled = true;
                // btnClose y btnCancel se mantienen habilitados para que el usuario pueda salir si tarda mucho.
                btnRetakePhoto.classList.add('d-none'); // Esconder botón de basura temporalmente
                
                statusDiv.classList.remove('d-none');
                loadingDiv.classList.remove('d-none');
                errorMsg.classList.add('d-none');
                successMsg.classList.add('d-none');

                // Preparar datos para enviar
                const formData = new FormData();
                // Asegurar que enviamos un archivo con nombre
                formData.append('image', photoBlob, 'photo.jpg');

                // Llamar al endpoint de IA
                fetch('ai_analyze.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loadingDiv.classList.add('d-none');
                    
                    if (data.status === 'success') {
                        // Éxito: Mostrar mensaje y redirigir
                        successMsg.textContent = '¡Repuesto identificado! Redirigiendo...';
                        successMsg.classList.remove('d-none');
                        
                        setTimeout(() => {
                            window.location.href = 'ficha_tecnica.php?id=' + data.repuesto_id;
                        }, 1500);
                    } else {
                        // Error (Flujo 3a del diagrama)
                        errorMsg.textContent = data.message;
                        errorMsg.classList.remove('d-none');
                        
                        // Restaurar UI para reintentar
                        btnAnalyze.disabled = false;
                        btnRetakePhoto.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    loadingDiv.classList.add('d-none');
                    errorMsg.textContent = 'Error de conexión con el sistema de IA.';
                    errorMsg.classList.remove('d-none');
                    
                    btnAnalyze.disabled = false;
                    btnRetakePhoto.classList.remove('d-none');
                });
            });
        });
    </script>

    <!-- Login Modal -->
    <div class="modal fade modal-login" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent">
                <div class="modal-login-card">
                    
                    <div class="modal-login-bg-top">
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.05); font-size: 80px; top: -10px; left: -10px;"></i>
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.05); font-size: 100px; top: -20px; right: -20px;"></i>
                    </div>
                    
                    <div class="modal-login-bg-bottom">
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(0,0,0,0.03); font-size: 150px; bottom: -30px; left: -30px;"></i>
                        <i class="fa-solid fa-motorcycle position-absolute" style="color: rgba(0,0,0,0.03); font-size: 100px; bottom: 20px; right: 10px;"></i>
                    </div>

                    <div class="login-content">
                        <div class="btn-close-card" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </div>

                        <div class="avatar-container">
                            <div class="avatar-circle">
                                <i class="fa-solid fa-user-tie"></i>
                                <div class="avatar-badge"><i class="fa-solid fa-gear"></i></div>
                            </div>
                        </div>

                        <h2 class="login-title">Iniciar sesión</h2>

                        <div class="alert-error d-none" id="loginErrorMsg">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <span id="loginErrorText">Error</span>
                        </div>

                        <form id="ajaxLoginForm">
                            <label for="loginEmail" class="form-label">
                                Email <img src="https://upload.wikimedia.org/wikipedia/commons/7/7e/Gmail_icon_%(2020%).svg" alt="Gmail" style="width: 16px; margin-left: 2px;">
                            </label>
                            <input type="email" id="loginEmail" name="email" class="form-control-custom" placeholder="Example@gmail.com" required>

                            <label for="loginPassword" class="form-label">Contraseña</label>
                            <input type="password" id="loginPassword" name="password" class="form-control-custom" placeholder="••••••••••••••••" required>

                            <div class="forgot-pass">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">¿Olvidaste tu contraseña?</a>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn-submit" id="btnLoginSubmit">
                                    <span id="btnLoginText">Ingresar</span>
                                    <span class="spinner-border spinner-border-sm d-none" id="btnLoginSpinner"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Login AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('ajaxLoginForm');
            const errorMsgBox = document.getElementById('loginErrorMsg');
            const errorMsgText = document.getElementById('loginErrorText');
            const btnLoginSubmit = document.getElementById('btnLoginSubmit');
            const btnLoginText = document.getElementById('btnLoginText');
            const btnLoginSpinner = document.getElementById('btnLoginSpinner');

            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Reset UI
                errorMsgBox.classList.add('d-none');
                btnLoginSubmit.disabled = true;
                btnLoginText.classList.add('d-none');
                btnLoginSpinner.classList.remove('d-none');

                const formData = new FormData(loginForm);

                fetch('login_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        btnLoginSpinner.classList.add('d-none');
                        btnLoginText.classList.remove('d-none');
                        btnLoginText.textContent = '¡Correcto! Entrando...';
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 500);
                    } else {
                        // Error
                        errorMsgText.textContent = data.message;
                        errorMsgBox.classList.remove('d-none');
                        
                        // Re-enable button
                        btnLoginSubmit.disabled = false;
                        btnLoginText.classList.remove('d-none');
                        btnLoginSpinner.classList.add('d-none');
                    }
                })
                .catch(error => {
                    errorMsgText.textContent = 'Error de conexión con el servidor.';
                    errorMsgBox.classList.remove('d-none');
                    btnLoginSubmit.disabled = false;
                    btnLoginText.classList.remove('d-none');
                    btnLoginSpinner.classList.add('d-none');
                });
            });
            
            // Clear form when modal is closed
            document.getElementById('loginModal').addEventListener('hidden.bs.modal', function () {
                loginForm.reset();
                errorMsgBox.classList.add('d-none');
                btnLoginSubmit.disabled = false;
                btnLoginText.classList.remove('d-none');
                btnLoginSpinner.classList.add('d-none');
            });
        });
    </script>

    <!-- Forgot Password Modal -->
    <div class="modal fade modal-login" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent">
                <div class="modal-login-card">
                    
                    <div class="modal-login-bg-top">
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.05); font-size: 80px; top: -10px; left: -10px;"></i>
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.05); font-size: 100px; top: -20px; right: -20px;"></i>
                    </div>
                    
                    <div class="modal-login-bg-bottom">
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(0,0,0,0.03); font-size: 150px; bottom: -30px; left: -30px;"></i>
                    </div>

                    <div class="login-content">
                        <div class="btn-close-card" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </div>

                        <div class="avatar-container">
                            <div class="avatar-circle">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                        </div>

                        <h2 class="login-title">Recuperar contraseña</h2>
                        <p class="text-center text-muted mb-4" style="font-size: 0.9rem;">Ingresa tu correo electrónico y te enviaremos un enlace para crear una nueva contraseña.</p>

                        <div class="alert-error d-none" id="forgotErrorMsg">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <span id="forgotErrorText">Error</span>
                        </div>
                        <div class="alert alert-success d-none" id="forgotSuccessMsg" style="border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-align: center;">
                            <i class="fa-solid fa-check-circle me-1"></i> <span id="forgotSuccessText">Correo enviado con éxito. Revisa tu bandeja de entrada.</span>
                        </div>

                        <form id="ajaxForgotForm">
                            <label for="forgotEmail" class="form-label">
                                Correo Electrónico
                            </label>
                            <input type="email" id="forgotEmail" name="email" class="form-control-custom" placeholder="tucorreo@gmail.com" required>

                            <div class="text-center">
                                <button type="submit" class="btn-submit" id="btnForgotSubmit">
                                    <span id="btnForgotText">Enviar enlace</span>
                                    <span class="spinner-border spinner-border-sm d-none" id="btnForgotSpinner"></span>
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal" style="font-size: 0.85rem; color: #666; text-decoration: none;"><i class="fa-solid fa-arrow-left me-1"></i> Volver a Iniciar sesión</a>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forgotForm = document.getElementById('ajaxForgotForm');
            const forgotErrorMsgBox = document.getElementById('forgotErrorMsg');
            const forgotErrorMsgText = document.getElementById('forgotErrorText');
            const forgotSuccessMsgBox = document.getElementById('forgotSuccessMsg');
            const btnForgotSubmit = document.getElementById('btnForgotSubmit');
            const btnForgotText = document.getElementById('btnForgotText');
            const btnForgotSpinner = document.getElementById('btnForgotSpinner');

            forgotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                forgotErrorMsgBox.classList.add('d-none');
                forgotSuccessMsgBox.classList.add('d-none');
                btnForgotSubmit.disabled = true;
                btnForgotText.classList.add('d-none');
                btnForgotSpinner.classList.remove('d-none');

                const formData = new FormData(forgotForm);

                fetch('forgot_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnForgotSpinner.classList.add('d-none');
                    btnForgotText.classList.remove('d-none');
                    
                    if (data.status === 'success') {
                        forgotSuccessMsgBox.classList.remove('d-none');
                        
                        // Solo para pruebas en entorno local:
                        if (data.debug_link) {
                            forgotSuccessMsgBox.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Correo enviado. <br><a href="' + data.debug_link + '" class="text-success fw-bold" style="text-decoration:underline;">[Simulador] Clic aquí para ir a crear nueva contraseña</a>';
                        }
                        
                        forgotForm.reset();
                        // Maintain button disabled to prevent multi-submit
                    } else {
                        forgotErrorMsgText.textContent = data.message;
                        forgotErrorMsgBox.classList.remove('d-none');
                        btnForgotSubmit.disabled = false;
                    }
                })
                .catch(error => {
                    forgotErrorMsgText.textContent = 'Error de conexión. Intente nuevamente.';
                    forgotErrorMsgBox.classList.remove('d-none');
                    btnForgotSubmit.disabled = false;
                    btnForgotText.classList.remove('d-none');
                    btnForgotSpinner.classList.add('d-none');
                });
            });
            
            document.getElementById('forgotPasswordModal').addEventListener('hidden.bs.modal', function () {
                forgotForm.reset();
                forgotErrorMsgBox.classList.add('d-none');
                forgotSuccessMsgBox.classList.add('d-none');
                btnForgotSubmit.disabled = false;
                btnForgotText.classList.remove('d-none');
                btnForgotSpinner.classList.add('d-none');
            });
        });
    </script>
</body>
</html>
