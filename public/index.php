<?php
// Revisamos si la sesión ya está iniciada, si no, la iniciamos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Conectamos a la base de datos
require_once '../config/database.php';

// Obtenemos los filtros que el usuario haya escrito en la barra de búsqueda o seleccionado
$search = $_GET['search'] ?? '';
$marca_id = $_GET['marca'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';
$modelo = $_GET['modelo'] ?? '';

// --- CARGAMOS LOS FILTROS DISPONIBLES ---
// Obtenemos todas las marcas de la base de datos (ordenadas alfabéticamente)
$marcas = $conn->query("SELECT id_marca as id, nombre FROM MARCA ORDER BY nombre");
// Obtenemos todas las categorías
$categorias = $conn->query("SELECT id_categoria as id, nombre FROM CATEGORIA ORDER BY nombre");
// Obtenemos todos los modelos de moto disponibles (sin repetir)
$modelos = $conn->query("SELECT DISTINCT compatibilidades as modelo_moto FROM REPUESTO WHERE compatibilidades IS NOT NULL AND compatibilidades != ''");

// --- PREPARAMOS LA BÚSQUEDA ---
// Construimos la pregunta base para la base de datos: "Tráeme todos los repuestos con sus marcas, categorías y fotos"
$query = "SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre, 
                 i.ruta_archivo as imagen_url, 
                 m_img.ruta_archivo as marca_imagen_url, 
                 c_img.ruta_archivo as categoria_imagen_url
          FROM REPUESTO r
          LEFT JOIN MARCA m ON r.id_marca = m.id_marca
          LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria
          LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
          LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
          LEFT JOIN MARCA_IMAGEN mi ON m.id_marca = mi.id_marca AND mi.imagen_principal = 1
          LEFT JOIN IMAGEN m_img ON mi.id_imagen = m_img.id_imagen
          LEFT JOIN CATEGORIA_IMAGEN ci ON c.id_categoria = ci.id_categoria AND ci.imagen_principal = 1
          LEFT JOIN IMAGEN c_img ON ci.id_imagen = c_img.id_imagen
          WHERE 1=1"; // "WHERE 1=1" es un truco para poder añadir más condiciones fácilmente luego
          
$params = []; // Aquí guardaremos los valores de búsqueda
$types = "";  // Aquí guardamos qué tipo de dato son (s = texto, i = número)

// Si el usuario escribió algo en la barra de búsqueda...
if (!empty($search)) {
    // Separamos lo que escribió palabra por palabra
    $searchTerms = array_filter(explode(' ', trim($search)));
    foreach ($searchTerms as $term) {
        // Buscamos esa palabra en el nombre, código de barras, código interno, modelo, referencia o marca
        $query .= " AND (r.nombre LIKE ? OR r.codigo_barras LIKE ? OR r.codigo_interno LIKE ? OR r.compatibilidades LIKE ? OR r.referencia_fabricante LIKE ? OR m.nombre LIKE ?)";
        $likeTerm = "%$term%"; // "%" significa "que contenga" esta palabra
        array_push($params, $likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm);
        $types .= "ssssss"; // Añadimos 6 's' porque son 6 campos de texto
    }
}

// Si el usuario filtró por una marca específica...
if (!empty($marca_id)) {
    $query .= " AND r.id_marca = ?";
    $params[] = $marca_id;
    $types .= "i"; // 'i' de entero (número)
}

// Si el usuario filtró por una categoría específica...
if (!empty($categoria_id)) {
    $query .= " AND r.id_categoria = ?";
    $params[] = $categoria_id;
    $types .= "i";
}

// Si el usuario filtró por un modelo de moto específico...
if (!empty($modelo)) {
    $query .= " AND r.compatibilidades LIKE ?";
    $params[] = "%$modelo%";
    $types .= "s";
}

// Verificamos si estamos haciendo una búsqueda o viendo la pantalla principal
$is_search = !empty($search) || !empty($marca_id) || !empty($categoria_id) || !empty($modelo);

if ($is_search) {
    // Ejecutamos la búsqueda exacta
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $repuestos = $result->fetch_all(MYSQLI_ASSOC); // Guardamos los resultados
} else {
    // Si no estamos buscando nada, cargamos el historial reciente del usuario
    $base_query = "SELECT r.*, m.nombre as marca_nombre, c.nombre as categoria_nombre, 
                                   i.ruta_archivo as imagen_url, 
                                   m_img.ruta_archivo as marca_imagen_url, 
                                   c_img.ruta_archivo as categoria_imagen_url
                            FROM REPUESTO r 
                            LEFT JOIN MARCA m ON r.id_marca = m.id_marca 
                            LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria 
                            LEFT JOIN REPUESTO_IMAGEN ri ON r.id_repuesto = ri.id_repuesto AND ri.imagen_principal = 1
                            LEFT JOIN IMAGEN i ON ri.id_imagen = i.id_imagen
                            LEFT JOIN MARCA_IMAGEN mi ON m.id_marca = mi.id_marca AND mi.imagen_principal = 1
                            LEFT JOIN IMAGEN m_img ON mi.id_imagen = m_img.id_imagen
                            LEFT JOIN CATEGORIA_IMAGEN ci ON c.id_categoria = ci.id_categoria AND ci.imagen_principal = 1
                            LEFT JOIN IMAGEN c_img ON ci.id_imagen = c_img.id_imagen";
                            
    $repuestos = [];
    $historial = $_SESSION['historial'] ?? [];
    $ids_str = "";
    
    // Si tiene un historial de repuestos vistos...
    if (!empty($historial)) {
        // Los cargamos de la base de datos en el mismo orden que los vio
        $ids_str = implode(',', array_map('intval', $historial));
        $result = $conn->query("$base_query WHERE r.id_repuesto IN ($ids_str) ORDER BY FIELD(r.id_repuesto, $ids_str)");
        if ($result) {
            $repuestos = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Queremos mostrar al menos 5 repuestos en pantalla inicial
    $faltantes = 5 - count($repuestos);
    if ($faltantes > 0) {
        $exclude_sql = "";
        // Si ya hay repuestos del historial, los excluimos para no repetirlos
        if (!empty($ids_str)) {
            $exclude_sql = "WHERE r.id_repuesto NOT IN ($ids_str)";
        }
        // Rellenamos el espacio sobrante con los últimos repuestos agregados al sistema
        $result2 = $conn->query("$base_query $exclude_sql ORDER BY r.id_repuesto DESC LIMIT $faltantes");
        if ($result2) {
            $nuevos = $result2->fetch_all(MYSQLI_ASSOC);
            $repuestos = array_merge($repuestos, $nuevos); // Unimos las listas
        }
    }
}

// --- RESPUESTA EN VIVO (AJAX) ---
// Si la página web nos pide resultados "en vivo" (mientras el usuario teclea)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if ($is_search && empty($repuestos)) {
        // Si no hay resultados
        echo '<div class="no-results"><i class="fa-solid fa-box-open"></i><h4>Sin coincidencias</h4><p>No encontramos repuestos con los criterios actuales.</p></div>';
    } elseif (!empty($repuestos)) {
        // Mostramos cada resultado como un enlace
        foreach ($repuestos as $repuesto) {
            echo '<a href="ficha_tecnica.php?id=' . htmlspecialchars($repuesto['id_repuesto']) . '" class="result-item">';
            
            // Ícono o foto
            echo '<div class="result-icon" style="overflow: hidden; background: white;">';
            $icon_url = !empty($repuesto['imagen_url']) ? $repuesto['imagen_url'] : (!empty($repuesto['marca_imagen_url']) ? $repuesto['marca_imagen_url'] : (!empty($repuesto['categoria_imagen_url']) ? $repuesto['categoria_imagen_url'] : ''));
            if ($icon_url) {
                // Si es la imagen del repuesto, la hacemos grande. Si es logo de marca/categoría, le damos un borde para que se vea bien
                $fit = !empty($repuesto['imagen_url']) ? 'cover' : 'contain; padding: 4px;';
                echo '<img src="../' . htmlspecialchars($icon_url) . '" alt="Icono" style="width: 100%; height: 100%; object-fit: ' . $fit . ' border-radius: 50%;">';
            } else {
                // Engranaje genérico si no hay foto
                echo '<i class="fa-solid fa-gears" style="color: #004ba8;"></i>';
            }
            echo '</div>';

            // Textos descriptivos (Nombre, marca, categoría)
            echo '<div class="result-text">' . htmlspecialchars($repuesto['nombre']);
            
            $subtitles = [];
            if(!empty($repuesto['marca_nombre'])) $subtitles[] = 'Marca: ' . htmlspecialchars($repuesto['marca_nombre']);
            if(!empty($repuesto['categoria_nombre'])) $subtitles[] = 'Categoría: ' . htmlspecialchars($repuesto['categoria_nombre']);
            
            if(!empty($subtitles)) {
                echo '<small class="text-muted d-block mt-1" style="font-size: 0.85em; font-weight: 500;">' . implode(' &nbsp;|&nbsp; ', $subtitles) . '</small>';
            }
            
            echo '</div><div class="result-arrow"><i class="fa-solid fa-arrow-right"></i></div></a>';
        }
    } else {
        // Mensaje inicial
        echo '<div class="no-results"><p>Utiliza la barra de búsqueda o la cámara IA para encontrar repuestos.</p></div>';
    }
    exit; // Terminamos aquí si era una petición "en vivo"
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
    
    <!-- HTML5 QRCode -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            overflow-y: scroll; /* Force scrollbar to prevent layout shift */
        }

        /* --- Navbar Profesional --- */
        .navbar {
            padding: 0.85rem 0;
            background-color: #ffffff;
            border-bottom: 1px solid #eef2f6 !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            padding: 0;
        }
        .navbar-brand img {
            height: 72px;
            width: auto;
            object-fit: contain;
            transition: transform 0.2s ease;
        }
        .navbar-brand:hover img {
            transform: scale(1.02);
        }

        .nav-link-custom {
            font-size: 0.95rem;
            font-weight: 500;
            color: #334155;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-link-custom:hover {
            color: #004ba8;
            background-color: #f8fafc;
        }

        .btn-header-login {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-header-login:hover {
            color: #004ba8;
            background-color: #f1f5f9;
        }

        .btn-header-register {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff !important;
            background-color: #004ba8;
            border: 1px solid #004ba8;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0, 75, 168, 0.2);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-header-register:hover {
            background-color: #003a82;
            border-color: #003a82;
            box-shadow: 0 4px 12px rgba(0, 75, 168, 0.3);
            transform: translateY(-1px);
        }

        .btn-header-cart {
            font-size: 0.9rem;
            font-weight: 600;
            color: #004ba8;
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            padding: 7px 14px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-header-cart:hover {
            background-color: #dbeafe;
            color: #003a82;
        }

        @media (max-width: 576px) {
            .navbar-brand img {
                height: 52px;
            }
            .btn-header-login, .btn-header-register {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            .nav-link-custom {
                padding: 6px 10px;
                font-size: 0.88rem;
            }
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
        .gear-icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.08);
            transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
            will-change: transform;
            pointer-events: none;
        }
        .gear-1 { font-size: 140px; top: -30px; left: -20px; }
        .gear-2 { font-size: 90px; top: 120px; left: 60px; }
        .gear-3 { font-size: 170px; top: -40px; right: -50px; }
        .gear-4 { font-size: 80px; top: 150px; right: 50px; }

        .antigravity-dots-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

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

        /* --- Search Card (Estilo Profesional y Sencillo) --- */
        .search-container {
            max-width: 680px;
            margin: 0 auto;
        }
        .search-card {
            background-color: #ffffff;
            border-radius: 18px;
            padding: 16px 20px 14px 22px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .search-card:focus-within {
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.2);
        }

        .search-main-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .search-icon-leading {
            color: #64748b;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .search-input-field {
            flex: 1;
            border: none;
            outline: none;
            font-size: 1.02rem;
            font-weight: 500;
            color: #0f172a;
            background: transparent;
        }
        .search-input-field::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .camera-button-modern {
            background-color: #0a2558;
            color: #ffffff;
            border: none;
            width: 44px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(10, 37, 88, 0.25);
        }
        .camera-button-modern:hover {
            background-color: #004ba8;
            transform: scale(1.04);
            color: #ffffff;
        }

        .search-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
        }
        .filter-btn-trigger {
            color: #004ba8;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
            transition: color 0.15s ease;
        }
        .filter-btn-trigger:hover {
            color: #003a82;
        }
        .filter-btn-trigger .filter-icon {
            font-size: 0.95rem;
            color: #004ba8;
        }
        .filter-btn-trigger .chevron-icon {
            font-size: 0.75rem;
            color: #64748b;
            transition: transform 0.2s ease;
        }

        .search-sublink {
            color: #64748b;
            font-size: 0.88rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.15s ease;
        }
        .search-sublink:hover {
            color: #004ba8;
            text-decoration: underline;
        }
        .search-sublink i {
            font-size: 0.75rem;
        }

        /* --- Pill Explorar Catálogo --- */
        .explore-catalog-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #ffffff;
            color: #004ba8;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .explore-catalog-pill:hover {
            color: #003a82;
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
        }
        .explore-catalog-pill i {
            font-size: 1rem;
        }

        @media (max-width: 576px) {
            .search-card {
                padding: 12px 14px 10px 14px;
                border-radius: 14px;
            }
            .search-input-field {
                font-size: 0.9rem;
            }
            .camera-button-modern {
                width: 38px;
                height: 36px;
                font-size: 1rem;
            }
            .explore-catalog-pill {
                font-size: 0.85rem;
                padding: 8px 16px;
            }
        }

        /* --- Results List --- */
        .results-section {
            position: relative;
            z-index: 2;
            max-width: 850px;
            margin: -20px auto 40px; /* Pull up slightly for better proportion */
            padding: 0 15px;
        }
        .results-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            overflow: hidden;
            transition: opacity 0.3s ease-in-out;
        }
        .result-item {
            display: flex;
            align-items: center;
            padding: 22px 35px;
            border-bottom: 1px solid #f4f5f7;
            text-decoration: none;
            color: inherit;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: transparent;
        }
        .result-item:last-child { border-bottom: none; }
        .result-item:hover { 
            background-color: #f8fafc; 
            color: inherit;
            padding-left: 45px; /* Fluid slide-in effect */
        }
        .result-item:hover .result-arrow {
            transform: translateX(5px);
        }
        .result-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #004ba8 0%, #1e5abf 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 25px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 75, 168, 0.2);
        }
        .result-text {
            font-weight: 600;
            color: #0b2253;
            flex-grow: 1;
            font-size: 1.2rem;
            letter-spacing: -0.3px;
        }
        .result-arrow { 
            color: #3b82f6; 
            font-size: 1.3rem; 
            transition: transform 0.25s ease;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 30px;
            color: #64748b;
        }
        .no-results i {
            font-size: 3.5rem;
            color: #cbd5e1;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .no-results:hover i {
            transform: scale(1.1);
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
        /* --- Login & Register Modal Styles --- */
        .modal-login .modal-content,
        .modal-register .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            background: transparent;
            box-shadow: none;
        }
        .modal-login .modal-dialog {
            max-width: 480px;
        }
        .modal-register .modal-dialog {
            max-width: 540px;
        }
        .modal-login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            padding: 35px 45px 30px 45px;
            position: relative;
        }
        @media (max-width: 576px) {
            .modal-register .modal-login-card,
            .modal-login .modal-login-card {
                padding: 30px 20px 25px 20px;
            }
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
            margin-bottom: 25px; position: relative;
        }
        .login-title::after {
            content: ''; position: absolute; bottom: -10px; left: 50%;
            transform: translateX(-50%); width: 40px; height: 3px;
            background-color: #0066ff; border-radius: 2px;
        }
        
        .modal-login .form-label,
        .modal-register .form-label {
            font-weight: 700; color: #0b2253; font-size: 0.92rem;
            margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;
        }
        .modal-login .form-label .label-tag-icon,
        .modal-register .form-label .label-tag-icon {
            color: #0066ff; font-size: 0.9rem;
        }
        .modal-login .form-control-custom,
        .modal-register .form-control-custom {
            width: 100%; padding: 11px 15px; border: 2px solid #3b82f6;
            border-radius: 10px; font-size: 0.95rem; color: #333; outline: none;
            background-color: #fcfcfd; transition: box-shadow 0.2s, border-color 0.2s;
        }
        .modal-login .form-control-custom {
            margin-bottom: 25px;
        }
        .modal-register .form-control-custom {
            margin-bottom: 0px;
        }
        .modal-login .form-control-custom:focus,
        .modal-register .form-control-custom:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            border-color: #1d4ed8;
            background-color: #ffffff;
        }
        .modal-login .form-control-custom::placeholder,
        .modal-register .form-control-custom::placeholder {
            color: #a0aec0; font-weight: 500;
        }
        
        .toggle-password-btn {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px 6px;
            font-size: 0.95rem;
            transition: color 0.15s;
        }
        .toggle-password-btn:hover {
            color: #0066ff;
        }
        
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

        /* --- Stepper & Multi-Section Styles for Registration --- */
        .register-stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            color: #64748b;
            font-weight: 800;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }
        .step-text {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.15;
        }
        .step-sub {
            font-size: 0.68rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .step-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            transition: color 0.2s;
        }
        .step-connector {
            flex-grow: 1;
            height: 2px;
            background: #cbd5e1;
            margin: 0 12px;
            transition: background 0.3s ease;
        }
        .step-item.active .step-circle {
            background: #0066ff;
            border-color: #0066ff;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.18);
        }
        .step-item.active .step-title {
            color: #0b2253;
        }
        .step-item.active .step-sub {
            color: #0066ff;
        }
        .step-item.completed .step-circle {
            background: #10b981;
            border-color: #10b981;
            color: #ffffff;
        }
        .step-connector.completed {
            background: #10b981;
        }
        .form-section {
            display: none;
            animation: formFadeIn 0.25s ease-in-out;
        }
        .form-section.active {
            display: block;
        }
        @keyframes formFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .section-badge {
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 0.82rem;
            color: #1e40af;
            font-weight: 600;
            text-align: center;
        }

        /* --- Quiénes Somos (Modern About Us Layout) --- */
        .about-section {
            padding: 85px 0 95px;
            background-color: #ffffff;
            border-top: 1px solid #f1f5f9;
            position: relative;
        }
        .about-eyebrow {
            font-size: 0.92rem;
            font-weight: 700;
            color: #059669;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            text-transform: capitalize;
        }
        .about-hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            letter-spacing: -0.7px;
            margin-bottom: 24px;
        }
        .btn-about-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #065f46;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.96rem;
            padding: 13px 32px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(6, 95, 70, 0.22);
            transition: all 0.25s ease;
        }
        .btn-about-cta:hover {
            background-color: #044e39;
            box-shadow: 0 6px 20px rgba(6, 95, 70, 0.32);
            transform: translateY(-2px);
            color: #ffffff !important;
        }
        .about-hero-desc {
            font-size: 1.05rem;
            line-height: 1.75;
            color: #475569;
            margin-bottom: 0;
            padding-top: 8px;
        }

        /* Tarjeta de Historia (Imagen grande) */
        .about-story-card {
            position: relative;
            border-radius: 26px;
            overflow: hidden;
            min-height: 480px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
        }
        .about-story-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
        }
        .story-image-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 0;
        }
        .about-story-card:hover .story-image-bg {
            transform: scale(1.04);
        }
        .story-gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0) 25%, rgba(15, 23, 42, 0.55) 60%, rgba(15, 23, 42, 0.95) 100%);
            z-index: 1;
        }
        .story-content {
            position: relative;
            z-index: 2;
            padding: 34px 38px;
            color: #ffffff;
        }
        .story-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }
        .story-text {
            font-size: 1rem;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        /* Tarjetas de Misión y Visión apiladas */
        .about-stacked-cards {
            display: flex;
            flex-direction: column;
            gap: 24px;
            height: 100%;
        }
        .about-mission-card {
            background-color: #d1fae5;
            border-radius: 26px;
            border: none;
            padding: 38px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 4px 18px rgba(16, 185, 129, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .about-mission-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.15);
        }
        .mission-title {
            font-size: 1.7rem;
            font-weight: 700;
            color: #064e3b;
            margin-bottom: 14px;
            letter-spacing: -0.3px;
        }
        .mission-text {
            font-size: 1.02rem;
            line-height: 1.7;
            color: #065f46;
            margin: 0;
            font-weight: 450;
        }

        .about-vision-card {
            background-color: #065f46;
            border-radius: 26px;
            border: none;
            padding: 38px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(6, 95, 70, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .about-vision-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 34px rgba(6, 95, 70, 0.3);
        }
        .vision-title {
            font-size: 1.7rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 14px;
            letter-spacing: -0.3px;
        }
        .vision-text {
            font-size: 1.02rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-weight: 300;
        }

        @media (max-width: 991px) {
            .about-hero-title {
                font-size: 2.1rem;
            }
            .about-story-card {
                min-height: 380px;
            }
            .story-content {
                padding: 24px;
            }
            .about-mission-card, .about-vision-card {
                padding: 28px 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar border-bottom">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo_motoverify.png?v=<?php echo time(); ?>" alt="Motoverify">
            </a>
            
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <a href="../views/cliente/catalogo.php" class="nav-link-custom">
                    Catálogo
                </a>

                <a href="../views/cliente/repuestos.php" class="nav-link-custom d-none d-sm-inline">
                    Repuestos
                </a>

                <a href="#quienes-somos" class="nav-link-custom d-none d-lg-inline">
                    Quiénes Somos
                </a>

                <?php 
                $cart_count_public = 0;
                if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
                    foreach ($_SESSION['carrito'] as $it) {
                        $cart_count_public += (int)($it['cantidad'] ?? 1);
                    }
                }
                ?>

                <?php if ($cart_count_public > 0): ?>
                    <a href="../views/cliente/carrito.php" class="btn-header-cart" title="Ver mi carrito de compras">
                        Carrito <span class="badge rounded-pill bg-primary"><?= $cart_count_public ?></span>
                    </a>
                <?php endif; ?>

                <div class="vr my-auto mx-1 text-secondary opacity-25 d-none d-sm-block" style="height: 22px;"></div>

                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])): ?>
                    <?php if ($_SESSION['rol'] === 'Cliente'): ?>
                        <a href="../views/cliente/dashboard.php" class="btn-header-register">
                            Mi Panel (<?= htmlspecialchars($_SESSION['nombre'] ?? 'Cliente') ?>)
                        </a>
                    <?php elseif ($_SESSION['rol'] === 'Administrador'): ?>
                        <a href="../views/admin/dashboard.php" class="btn-header-register">
                            Panel Admin
                        </a>
                    <?php elseif ($_SESSION['rol'] === 'Vendedor'): ?>
                        <a href="../views/vendedor/dashboard.php" class="btn-header-register">
                            Panel Vendedor
                        </a>
                    <?php endif; ?>
                    <a href="../controllers/logout.php" class="btn-header-login text-danger" title="Cerrar Sesión">
                        Salir
                    </a>
                <?php else: ?>
                    <a href="#" class="btn-header-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                        Iniciar Sesión
                    </a>
                    <a href="#" class="btn-header-register" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Registrarse
                    </a>
                <?php endif; ?>
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
            <canvas id="antigravityCanvas" class="antigravity-dots-canvas"></canvas>
        </div>

        <div class="hero-content container">
            <h1 class="hero-title">Búsqueda</h1>
            
            <div class="search-container">
                <form id="searchForm" method="GET" action="index.php">
                    <div class="search-card">
                        <div class="search-main-row">
                            <i class="fa-solid fa-magnifying-glass search-icon-leading"></i>
                            <input type="text" name="search" class="search-input-field" placeholder="Nombre, referencia o código del repuesto..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                            <button type="button" class="camera-button-modern" title="Búsqueda por foto IA" data-bs-toggle="modal" data-bs-target="#iaModal">
                                <i class="fa-solid fa-camera"></i>
                            </button>
                        </div>
                        <div class="search-footer-row">
                            <span class="filter-btn-trigger" data-bs-toggle="modal" data-bs-target="#filtrosModal">
                                <i class="fa-solid fa-sliders filter-icon"></i>
                                Filtros <?= (!empty($marca_id) || !empty($categoria_id) || !empty($modelo)) ? '<span class="badge bg-primary ms-1 py-1 px-2" style="font-size: 0.7rem;">Activos</span>' : '' ?>
                                <i class="fa-solid fa-chevron-down chevron-icon"></i>
                            </span>

                            <a href="../views/cliente/repuestos.php" class="search-sublink">
                                Búsqueda de repuestos <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Botón explorador de catálogo limpio tipo píldora -->
            <div class="text-center mt-3">
                <a href="../views/cliente/catalogo.php" class="explore-catalog-pill">
                    <i class="fa-regular fa-compass"></i>
                    <span>Explorar catálogo y fichas técnicas</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
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
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="index.php" class="btn btn-outline-secondary">Limpiar filtros</a>
                        <a href="../views/cliente/catalogo.php" class="btn btn-primary"><i class="fa-solid fa-store me-1"></i> Ir al Catálogo Completo</a>
                    </div>
                </div>
            <?php elseif (!empty($repuestos)): ?>
                <?php foreach ($repuestos as $repuesto): ?>
                    <a href="ficha_tecnica.php?id=<?= htmlspecialchars($repuesto['id_repuesto']) ?>" class="result-item">
                        <div class="result-icon" style="overflow: hidden; background: white;">
                            <?php 
                            $icon_url = !empty($repuesto['imagen_url']) ? $repuesto['imagen_url'] : (!empty($repuesto['marca_imagen_url']) ? $repuesto['marca_imagen_url'] : (!empty($repuesto['categoria_imagen_url']) ? $repuesto['categoria_imagen_url'] : ''));
                            if ($icon_url): 
                                $fit = !empty($repuesto['imagen_url']) ? 'cover' : 'contain; padding: 4px;';
                            ?>
                                <img src="../<?= htmlspecialchars($icon_url) ?>" alt="Icono" style="width: 100%; height: 100%; object-fit: <?= $fit ?> border-radius: 50%;">
                            <?php else: ?>
                                <i class="fa-solid fa-gears" style="color: #004ba8;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="result-text">
                            <?= htmlspecialchars($repuesto['nombre']) ?> 
                            
                            <?php 
                            $subtitles = [];
                            if(!empty($repuesto['marca_nombre'])) $subtitles[] = 'Marca: ' . htmlspecialchars($repuesto['marca_nombre']);
                            if(!empty($repuesto['categoria_nombre'])) $subtitles[] = 'Categoría: ' . htmlspecialchars($repuesto['categoria_nombre']);
                            ?>
                            <?php if(!empty($subtitles)): ?>
                                <small class="text-muted d-block mt-1" style="font-size: 0.85em; font-weight: 500;"><?= implode(' &nbsp;|&nbsp; ', $subtitles) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="result-arrow">
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
                <div class="text-center mt-4">
                    <a href="../views/cliente/catalogo.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-layer-group me-1"></i> Ver catálogo completo con fotos y detalles
                    </a>
                </div>
            <?php else: ?>
                 <div class="no-results">
                    <p>Utiliza la barra de búsqueda o la cámara IA para encontrar repuestos.</p>
                    <div class="mt-3">
                        <a href="../views/cliente/catalogo.php" class="btn btn-primary rounded-pill px-4 py-2">
                            <i class="fa-solid fa-store me-2"></i> Explorar Catálogo de Repuestos
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Quiénes Somos Section (Estilo Profesional e Inspirado en Referencia) -->
    <section id="quienes-somos" class="about-section">
        <div class="container py-2">
            <!-- Encabezado superior con dos columnas -->
            <div class="row align-items-start mb-5 g-4">
                <div class="col-lg-6">
                    <div class="about-eyebrow">Quiénes Somos</div>
                    <h2 class="about-hero-title">Impulsamos el futuro del motociclismo con tecnología y precisión</h2>
                    <a href="../views/cliente/catalogo.php" class="btn-about-cta">
                        Explorar Catálogo
                    </a>
                </div>
                <div class="col-lg-6">
                    <p class="about-hero-desc">
                        En <strong>Motoverify</strong> transformamos la experiencia de búsqueda y compra de repuestos para motocicletas. Combinamos verificación visual inteligente con inteligencia artificial, fichas técnicas certificadas y un equipo humano apasionado por la ingeniería, garantizando que cada taller, almacén y motociclista reciba siempre la pieza exacta con máxima rapidez y total garantía.
                    </p>
                </div>
            </div>

            <!-- Grid de Contenido: Historia a la izquierda, Misión y Visión apiladas a la derecha -->
            <div class="row g-4 align-items-stretch">
                <!-- Columna Izquierda: Tarjeta Grande con Foto de Historia -->
                <div class="col-lg-7">
                    <div class="card about-story-card">
                        <div class="story-image-bg" style="background-image: url('assets/images/about_us_team.jpg');"></div>
                        <div class="story-gradient-overlay"></div>
                        <div class="story-content">
                            <h3 class="story-title">Nuestra Historia</h3>
                            <p class="story-text">
                                Motoverify nació con la convicción de eliminar la incertidumbre al adquirir repuestos. Respaldados por algoritmos de verificación inteligente y la dedicación de nuestro talento humano, conectamos a mecánicos, distribuidores y apasionados de las dos ruedas con la información técnica más confiable y un catálogo de repuestos garantizados en todo el país.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Tarjetas Apiladas (Misión y Visión) -->
                <div class="col-lg-5">
                    <div class="about-stacked-cards">
                        <!-- Tarjeta Misión -->
                        <div class="card about-mission-card">
                            <h3 class="mission-title">Nuestra Misión</h3>
                            <p class="mission-text">
                                Proveer soluciones tecnológicas e innovadoras para la identificación, venta y verificación de repuestos de motocicletas, garantizando la mayor precisión, velocidad y confianza en cada transacción comercial.
                            </p>
                        </div>

                        <!-- Tarjeta Visión -->
                        <div class="card about-vision-card">
                            <h3 class="vision-title">Nuestra Visión</h3>
                            <p class="vision-text">
                                Ser la plataforma líder a nivel nacional e internacional en la distribución y administración de repuestos de motocicletas, destacando por nuestra inteligencia artificial, excelencia en servicio y compromiso con nuestro talento humano.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
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

    <!-- Modal IA (Reconocimiento Visual y Código de Barras) -->
    <div class="modal fade" id="iaModal" tabindex="-1" aria-labelledby="iaModalLabel" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-microchip me-2"></i>Reconocimiento Visual y Barras</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseIaModal"></button>
          </div>
          <div class="modal-body text-center py-4">
            
            <!-- Contenedor Cámara en Vivo -->
            <div id="cameraContainer" class="position-relative mb-3" style="display: none;">
                <div id="qrReader" style="width: 100%; min-height: 300px;"></div>
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
                    <i class="fa-solid fa-barcode mb-2" style="font-size: 2rem;"></i>
                    <i class="fa-solid fa-image ms-2 mb-2" style="font-size: 2rem;"></i>
                    <p class="mb-2 fw-semibold">Escanea un código de barras o toma una foto</p>
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

            const qrReaderElement = document.getElementById('qrReader');
            let html5QrCode = null;

            // Función para detener la cámara
            function stopMediaTracks() {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then((ignore) => {
                        html5QrCode.clear();
                    }).catch((err) => {
                        console.error("Failed to stop QR scanner", err);
                    });
                }
            }

            // Función para iniciar la cámara
            function startCamera() {
                stopMediaTracks();
                
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("qrReader");
                }
                
                previewContainer.style.display = 'none';
                cameraContainer.style.display = 'block';
                btnAnalyze.disabled = true;
                photoBlob = null;
                statusDiv.classList.add('d-none');
                errorMsg.classList.add('d-none');
                successMsg.classList.add('d-none');
                
                const facingMode = useFrontCamera ? "user" : "environment";
                
                html5QrCode.start(
                    { facingMode: facingMode },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText, decodedResult) => {
                        // CÓDIGO ENCONTRADO
                        stopMediaTracks();
                        searchBarcode(decodedText);
                    },
                    (errorMessage) => {
                        // ignorar
                    })
                .catch((err) => {
                    console.error("No se pudo iniciar la cámara", err);
                    alert("Error al acceder a la cámara. Asegúrate de dar los permisos.");
                });
            }

            // Función reutilizable para buscar código de barras
            function searchBarcode(decodedText) {
                statusDiv.classList.remove('d-none');
                loadingDiv.classList.remove('d-none');
                errorMsg.classList.add('d-none');
                successMsg.classList.add('d-none');
                loadingDiv.innerHTML = '<div class="spinner-border me-2" role="status"></div> Buscando código en la base de datos...';
                
                const formData = new FormData();
                formData.append('qr_text', decodedText);
                
                fetch('../controllers/qr_search.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    loadingDiv.classList.add('d-none');
                    if (data.status === 'success') {
                        successMsg.textContent = '¡Código válido! Redirigiendo a ' + data.repuesto_nombre;
                        successMsg.classList.remove('d-none');
                        setTimeout(() => {
                            window.location.href = 'ficha_tecnica.php?id=' + data.repuesto_id;
                        }, 1500);
                    } else {
                        window.location.href = 'index.php?search=' + encodeURIComponent(decodedText);
                    }
                })
                .catch(err => {
                    loadingDiv.classList.add('d-none');
                    window.location.href = 'index.php?search=' + encodeURIComponent(decodedText);
                });
            }

            // Eventos de Cámara
            btnStartCamera.addEventListener('click', startCamera);

            btnSwitchCamera.addEventListener('click', () => {
                useFrontCamera = !useFrontCamera;
                startCamera();
            });

            // Tomar la foto manual (fallback IA)
            btnSnapPhoto.addEventListener('click', () => {
                const videoElement = document.querySelector('#qrReader video');
                if (!videoElement) return;
                
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;
                canvas.getContext('2d').drawImage(videoElement, 0, 0);
                
                // Convertir el canvas a Blob (imagen)
                canvas.toBlob((blob) => {
                    photoBlob = blob;
                    const imageUrl = URL.createObjectURL(blob);
                    
                    imagePreview.src = imageUrl;
                    
                    // Apagar cámara y mostrar preview
                    stopMediaTracks();
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
                stopMediaTracks();
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

            // Función reutilizable para manejar el archivo de imagen
            function handleImageFile(file) {
                if (file) {
                    if (!html5QrCode) {
                        html5QrCode = new Html5Qrcode("qrReader");
                    }
                    
                    statusDiv.classList.remove('d-none');
                    loadingDiv.classList.remove('d-none');
                    errorMsg.classList.add('d-none');
                    successMsg.classList.add('d-none');
                    loadingDiv.innerHTML = '<div class="spinner-border me-2" role="status"></div> Buscando código de barras en la imagen...';
                    btnAnalyze.disabled = true;

                    // 1. Intentar leer un código de barras de la imagen localmente
                    html5QrCode.scanFile(file, false)
                        .then(decodedText => {
                            // Encontró un código de barras, buscarlo
                            searchBarcode(decodedText);
                        })
                        .catch(err => {
                            // No encontró código, proceder a mostrar imagen para IA
                            loadingDiv.classList.add('d-none');
                            statusDiv.classList.add('d-none');
                            
                            photoBlob = file;
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                imagePreview.src = e.target.result;
                                uploadPlaceholder.style.display = 'none';
                                imagePreview.style.display = 'block';
                                btnRetakePhoto.classList.remove('d-none');
                                btnAnalyze.disabled = false;
                            }
                            reader.readAsDataURL(file);
                        });
                }
            }

            // Manejar selección de archivo (Fallback original)
            fileInput.addEventListener('change', function() {
                handleImageFile(this.files[0]);
            });

            // --- DRAG AND DROP ---
            uploadPlaceholder.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadPlaceholder.style.border = '2px dashed #0d6efd';
                uploadPlaceholder.style.backgroundColor = '#f8f9fa';
            });
            uploadPlaceholder.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadPlaceholder.style.border = 'none';
                uploadPlaceholder.style.backgroundColor = 'transparent';
            });
            uploadPlaceholder.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadPlaceholder.style.border = 'none';
                uploadPlaceholder.style.backgroundColor = 'transparent';
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    if (file.type.startsWith('image/')) {
                        handleImageFile(file);
                    }
                }
            });

            // --- PASTE ---
            document.addEventListener('paste', (e) => {
                if (iaModal.classList.contains('show') && uploadPlaceholder.style.display !== 'none') {
                    if (e.clipboardData && e.clipboardData.files && e.clipboardData.files.length > 0) {
                        const file = e.clipboardData.files[0];
                        if (file.type.startsWith('image/')) {
                            handleImageFile(file);
                        }
                    }
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
                fetch('../controllers/ai_analyze.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loadingDiv.classList.add('d-none');
                    
                    if (data.status === 'success') {
                        // Éxito: Mostrar qué detectó la IA y a qué repuesto coincidió
                        let msg = 'IA detectó: "' + data.ai_detected + '" → Repuesto: "' + data.repuesto_nombre + '"';
                        successMsg.innerHTML = '<strong>¡Repuesto identificado!</strong><br><small>' + msg + '</small><br><small class="text-muted">Redirigiendo...</small>';
                        successMsg.classList.remove('d-none');
                        
                        setTimeout(() => {
                            window.location.href = 'ficha_tecnica.php?id=' + data.repuesto_id;
                        }, 2500);
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
                                Usuario o Email <i class="fa-solid fa-user ms-1 text-primary"></i>
                            </label>
                            <input type="text" id="loginEmail" name="email" class="form-control-custom" placeholder="Ingresa tu usuario o correo" required>

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

                            <div class="text-center mt-3 pt-3 border-top">
                                <span class="text-muted small">¿No tienes una cuenta de cliente?</span><br>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal" class="fw-bold text-primary small text-decoration-none">
                                    <i class="fa-solid fa-user-plus me-1"></i> Regístrate gratis aquí
                                </a>
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

                fetch('../controllers/login_action.php', {
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

            // Abrir automáticamente el modal si se solicita en la URL (?login=1 o ?register=1)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login') === '1') {
                const myLoginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                myLoginModal.show();
            } else if (urlParams.get('register') === '1') {
                const myRegisterModal = new bootstrap.Modal(document.getElementById('registerModal'));
                myRegisterModal.show();
            }
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

                fetch('../controllers/forgot_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnForgotSpinner.classList.add('d-none');
                    btnForgotText.classList.remove('d-none');
                    
                    if (data.status === 'success') {
                        forgotSuccessMsgBox.classList.remove('d-none');
                        
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

    <!-- Register Modal -->
    <div class="modal fade modal-register" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent">
                <div class="modal-login-card">
                    
                    <div class="modal-login-bg-top">
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.06); font-size: 80px; top: -10px; left: -10px;"></i>
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(255,255,255,0.06); font-size: 105px; top: -20px; right: -20px;"></i>
                    </div>
                    
                    <div class="modal-login-bg-bottom">
                        <i class="fa-solid fa-gear position-absolute" style="color: rgba(0,0,0,0.03); font-size: 150px; bottom: -30px; left: -30px;"></i>
                        <i class="fa-solid fa-motorcycle position-absolute" style="color: rgba(0,0,0,0.03); font-size: 105px; bottom: 20px; right: 10px;"></i>
                    </div>

                    <div class="login-content">
                        <div class="btn-close-card" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </div>

                        <div class="avatar-container">
                            <div class="avatar-circle">
                                <i class="fa-solid fa-user-plus"></i>
                                <div class="avatar-badge"><i class="fa-solid fa-gear"></i></div>
                            </div>
                        </div>

                        <h2 class="login-title">Crear cuenta</h2>

                        <!-- Stepper de 2 Secciones -->
                        <div class="register-stepper">
                            <div class="step-item active" id="stepTab1" role="button" title="Ir a Sección 1">
                                <div class="step-circle">
                                    <span class="step-number">1</span>
                                    <i class="fa-solid fa-check step-check d-none"></i>
                                </div>
                                <div class="step-text">
                                    <span class="step-sub">Sección 1</span>
                                    <span class="step-title">Datos de Cuenta</span>
                                </div>
                            </div>
                            <div class="step-connector" id="stepConnector"></div>
                            <div class="step-item" id="stepTab2" role="button" title="Ir a Sección 2">
                                <div class="step-circle">
                                    <span class="step-number">2</span>
                                    <i class="fa-solid fa-check step-check d-none"></i>
                                </div>
                                <div class="step-text">
                                    <span class="step-sub">Sección 2</span>
                                    <span class="step-title">Contacto y Entrega</span>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message Banner -->
                        <div class="alert-error d-none" id="registerErrorMsg">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <span id="registerErrorText">Error</span>
                        </div>

                        <form id="ajaxRegisterForm" novalidate>
                            <input type="hidden" name="action" value="register">

                            <!-- SECCIÓN 1: DATOS DE LA CUENTA Y ACCESO -->
                            <div class="form-section active" id="formSection1">
                                <div class="section-badge mb-3">
                                    <i class="fa-solid fa-user-shield me-1"></i> Credenciales de acceso y datos personales
                                </div>

                                <div class="row g-3">
                                    <!-- Nombre Completo -->
                                    <div class="col-12">
                                        <label for="modalRegNombre" class="form-label">
                                            <span>Nombre Completo <span class="text-danger">*</span></span>
                                            <i class="fa-solid fa-user label-tag-icon"></i>
                                        </label>
                                        <input type="text" id="modalRegNombre" name="nombre" class="form-control-custom" placeholder="Ej: Carlos Ramírez" required autocomplete="name">
                                    </div>

                                    <!-- Correo Electrónico -->
                                    <div class="col-12">
                                        <label for="modalRegEmail" class="form-label">
                                            <span>Usuario o Email <span class="text-danger">*</span></span>
                                            <i class="fa-solid fa-envelope label-tag-icon"></i>
                                        </label>
                                        <input type="email" id="modalRegEmail" name="correo" class="form-control-custom" placeholder="cliente@correo.com" required autocomplete="email">
                                    </div>

                                    <!-- Contraseña -->
                                    <div class="col-12 col-md-6">
                                        <label for="modalRegPassword" class="form-label">
                                            <span>Contraseña <span class="text-danger">*</span></span>
                                            <i class="fa-solid fa-lock label-tag-icon"></i>
                                        </label>
                                        <div class="position-relative">
                                            <input type="password" id="modalRegPassword" name="password" class="form-control-custom pe-5" placeholder="Mínimo 6 caracteres" minlength="6" required autocomplete="new-password">
                                            <button type="button" class="toggle-password-btn" data-target="modalRegPassword" title="Mostrar/ocultar contraseña" tabindex="-1">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Confirmar Contraseña -->
                                    <div class="col-12 col-md-6">
                                        <label for="modalRegPasswordConfirm" class="form-label">
                                            <span>Confirmar Contraseña <span class="text-danger">*</span></span>
                                            <i class="fa-solid fa-shield-halved label-tag-icon"></i>
                                        </label>
                                        <div class="position-relative">
                                            <input type="password" id="modalRegPasswordConfirm" name="password_confirm" class="form-control-custom pe-5" placeholder="Repite tu contraseña" minlength="6" required autocomplete="new-password">
                                            <button type="button" class="toggle-password-btn" data-target="modalRegPasswordConfirm" title="Mostrar/ocultar contraseña" tabindex="-1">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón Siguiente a Sección 2 -->
                                <div class="mt-4 pt-1">
                                    <button type="button" class="btn-submit d-flex align-items-center justify-content-center gap-2" id="btnNextStep">
                                        <span>Continuar a Contacto y Entrega</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: DATOS DE CONTACTO Y ENVÍO -->
                            <div class="form-section" id="formSection2">
                                <div class="section-badge mb-3">
                                    <i class="fa-solid fa-truck-fast me-1"></i> Información para facturación y entrega de repuestos
                                </div>

                                <div class="row g-3">
                                    <!-- Cédula / Documento -->
                                    <div class="col-12 col-md-6">
                                        <label for="modalRegDocumento" class="form-label">
                                            <span>Cédula / Documento</span>
                                            <i class="fa-solid fa-id-card label-tag-icon"></i>
                                        </label>
                                        <input type="text" id="modalRegDocumento" name="documento" class="form-control-custom" placeholder="Ej: 1083456789">
                                    </div>

                                    <!-- Teléfono / Celular -->
                                    <div class="col-12 col-md-6">
                                        <label for="modalRegTelefono" class="form-label">
                                            <span>Teléfono / Celular</span>
                                            <i class="fa-solid fa-phone label-tag-icon"></i>
                                        </label>
                                        <input type="tel" id="modalRegTelefono" name="telefono" class="form-control-custom" placeholder="Ej: 3101234567" autocomplete="tel">
                                    </div>

                                    <!-- Ciudad / Municipio -->
                                    <div class="col-12">
                                        <label for="modalRegCiudad" class="form-label">
                                            <span>Ciudad / Municipio</span>
                                            <i class="fa-solid fa-city label-tag-icon"></i>
                                        </label>
                                        <input type="text" id="modalRegCiudad" name="ciudad" class="form-control-custom" placeholder="Ej: Neiva, Huila">
                                    </div>

                                    <!-- Dirección de Entrega -->
                                    <div class="col-12">
                                        <label for="modalRegDireccion" class="form-label">
                                            <span>Dirección de Entrega</span>
                                            <i class="fa-solid fa-map-location-dot label-tag-icon"></i>
                                        </label>
                                        <input type="text" id="modalRegDireccion" name="direccion" class="form-control-custom" placeholder="Calle, Carrera, Número, Barrio o Conjunto" autocomplete="street-address">
                                    </div>
                                </div>

                                <!-- Botones Atrás y Finalizar -->
                                <div class="d-flex align-items-center gap-2 mt-4 pt-1">
                                    <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-1" id="btnPrevStep" style="border-radius: 50px; height: 50px; min-width: 105px; border-width: 2px;">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        <span>Volver</span>
                                    </button>
                                    <button type="submit" class="btn-submit flex-grow-1" id="btnRegisterSubmit">
                                        <span id="btnRegisterText">Crear Mi Cuenta</span>
                                        <span class="spinner-border spinner-border-sm d-none ms-2" id="btnRegisterSpinner" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Footer: Iniciar sesión -->
                            <div class="text-center mt-3 pt-3 border-top" style="border-color: #e2e8f0 !important;">
                                <span class="text-muted small">¿Ya tienes una cuenta registrada?</span><br>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal" class="fw-bold text-primary small text-decoration-none d-inline-flex align-items-center gap-1 mt-1">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Iniciar sesión aquí
                                </a>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Register AJAX Script with 2-Section Stepper Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const regForm = document.getElementById('ajaxRegisterForm');
            const regErrorMsgBox = document.getElementById('registerErrorMsg');
            const regErrorMsgText = document.getElementById('registerErrorText');
            const btnRegSubmit = document.getElementById('btnRegisterSubmit');
            const btnRegText = document.getElementById('btnRegisterText');
            const btnRegSpinner = document.getElementById('btnRegisterSpinner');

            const section1 = document.getElementById('formSection1');
            const section2 = document.getElementById('formSection2');
            const stepTab1 = document.getElementById('stepTab1');
            const stepTab2 = document.getElementById('stepTab2');
            const stepConnector = document.getElementById('stepConnector');
            const btnNext = document.getElementById('btnNextStep');
            const btnPrev = document.getElementById('btnPrevStep');

            function showRegError(msg) {
                regErrorMsgText.textContent = msg;
                regErrorMsgBox.classList.remove('d-none');
            }

            function hideRegError() {
                regErrorMsgBox.classList.add('d-none');
            }

            // Validar campos de la Sección 1
            function validateStep1() {
                hideRegError();
                const nombre = document.getElementById('modalRegNombre').value.trim();
                const correo = document.getElementById('modalRegEmail').value.trim();
                const pass = document.getElementById('modalRegPassword').value;
                const passConf = document.getElementById('modalRegPasswordConfirm').value;

                if (!nombre || !correo || !pass || !passConf) {
                    showRegError('Por favor completa todos los campos obligatorios de la Sección 1 (*).');
                    return false;
                }

                if (nombre.length < 3) {
                    showRegError('El nombre completo debe tener al menos 3 caracteres.');
                    document.getElementById('modalRegNombre').focus();
                    return false;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(correo)) {
                    showRegError('Ingresa un correo electrónico válido (ejemplo: usuario@correo.com).');
                    document.getElementById('modalRegEmail').focus();
                    return false;
                }

                if (pass.length < 6) {
                    showRegError('La contraseña debe tener al menos 6 caracteres.');
                    document.getElementById('modalRegPassword').focus();
                    return false;
                }

                if (pass !== passConf) {
                    showRegError('Las contraseñas no coinciden. Por favor verifícalas.');
                    document.getElementById('modalRegPasswordConfirm').focus();
                    return false;
                }

                return true;
            }

            // Avanzar a Sección 2
            function goToStep2() {
                if (!validateStep1()) return;

                hideRegError();
                section1.classList.remove('active');
                section2.classList.add('active');

                stepTab1.classList.remove('active');
                stepTab1.classList.add('completed');
                stepTab2.classList.add('active');
                stepConnector.classList.add('completed');

                document.getElementById('modalRegDocumento').focus();
            }

            // Volver a Sección 1
            function goToStep1() {
                hideRegError();
                section2.classList.remove('active');
                section1.classList.add('active');

                stepTab2.classList.remove('active');
                stepTab1.classList.remove('completed');
                stepTab1.classList.add('active');
                stepConnector.classList.remove('completed');
            }

            btnNext.addEventListener('click', goToStep2);
            btnPrev.addEventListener('click', goToStep1);

            // Clic directo en las pestañas del Stepper
            stepTab1.addEventListener('click', goToStep1);
            stepTab2.addEventListener('click', function() {
                if (validateStep1()) {
                    goToStep2();
                }
            });

            // Toggle password reveal
            document.querySelectorAll('#registerModal .toggle-password-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Envío del formulario
            regForm.addEventListener('submit', function(e) {
                e.preventDefault();
                hideRegError();

                if (!validateStep1()) {
                    goToStep1();
                    return;
                }

                btnRegSubmit.disabled = true;
                btnRegText.textContent = 'Creando cuenta...';
                btnRegSpinner.classList.remove('d-none');

                const formData = new FormData(regForm);

                fetch('../controllers/cliente_auth_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnRegSubmit.disabled = false;
                    btnRegText.textContent = 'Crear Mi Cuenta';
                    btnRegSpinner.classList.add('d-none');

                    if (data.status === 'success') {
                        // Cerrar modal
                        const registerModalEl = document.getElementById('registerModal');
                        const modalInstance = bootstrap.Modal.getInstance(registerModalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '¡Bienvenido a Motoverify!',
                            text: data.message,
                            confirmButtonColor: '#062b80',
                            confirmButtonText: 'Ir a mi Panel de Cliente',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = data.redirect || '../views/cliente/dashboard.php';
                        });
                    } else {
                        showRegError(data.message || 'No se pudo crear la cuenta.');
                        if (data.message && (data.message.includes('correo') || data.message.includes('contraseña') || data.message.includes('Nombre'))) {
                            goToStep1();
                        }
                    }
                })
                .catch(err => {
                    btnRegSubmit.disabled = false;
                    btnRegText.textContent = 'Crear Mi Cuenta';
                    btnRegSpinner.classList.add('d-none');
                    showRegError('Error de conexión al procesar el registro.');
                });
            });

            // Limpiar al cerrar modal
            document.getElementById('registerModal').addEventListener('hidden.bs.modal', function () {
                regForm.reset();
                hideRegError();
                goToStep1();
                btnRegSubmit.disabled = false;
                btnRegText.textContent = 'Crear Mi Cuenta';
                btnRegSpinner.classList.add('d-none');
            });
        });
    </script>
    
    <!-- Live Search AJAX Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        const resultsList = document.querySelector('.results-list');
        
        if (searchInput && resultsList) {
            let timeoutId;
            // Store the initial content to restore it if the user clears the search
            const defaultContent = resultsList.innerHTML;

            searchInput.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const query = this.value.trim();
                
                // Show loading state (optional, visual feedback)
                resultsList.style.opacity = '0.6';

                if (query.length === 0) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('ajax', '1');
                    url.searchParams.delete('search'); // Remove search parameter to get history
                    
                    fetch(url.toString())
                        .then(response => response.text())
                        .then(html => {
                            resultsList.innerHTML = html;
                            resultsList.style.opacity = '1';
                        })
                        .catch(err => {
                            console.error("Error en búsqueda ajax:", err);
                            resultsList.style.opacity = '1';
                        });
                    return;
                }

                timeoutId = setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('ajax', '1');
                    url.searchParams.set('search', query);
                    
                    fetch(url.toString())
                        .then(response => response.text())
                        .then(html => {
                            resultsList.innerHTML = html;
                            resultsList.style.opacity = '1';
                        })
                        .catch(err => {
                            console.error("Error en búsqueda ajax:", err);
                            resultsList.style.opacity = '1';
                        });
                }, 300); // 300ms debounce
            });
        }
    });
    </script>
    
    <!-- Antigravity Interactive Dots & Parallax Physics Engine -->
    <script>
    (function() {
        const hero = document.querySelector('.hero-section');
        const canvas = document.getElementById('antigravityCanvas');
        if (!hero || !canvas) return;

        const ctx = canvas.getContext('2d');
        const gears = hero.querySelectorAll('.gear-icon');

        let width = 0;
        let height = 0;
        let dpr = window.devicePixelRatio || 1;
        let particles = [];
        let animationFrameId = null;

        const mouse = {
            x: null,
            y: null,
            radius: 140, // Radio de influencia de repulsión
            isActive: false
        };

        class Particle {
            constructor(baseX, baseY, isGrid = true) {
                this.baseX = baseX;
                this.baseY = baseY;
                this.x = baseX + (Math.random() - 0.5) * 4;
                this.y = baseY + (Math.random() - 0.5) * 4;
                this.vx = 0;
                this.vy = 0;
                this.isGrid = isGrid;
                
                // Variación de tamaño según tipo
                this.baseRadius = isGrid ? (Math.random() * 0.8 + 2.2) : (Math.random() * 1.5 + 1.5);
                this.radius = this.baseRadius;
                
                // Opacidad y coloración Google Antigravity
                this.baseAlpha = isGrid ? 0.35 : (Math.random() * 0.35 + 0.15);
                this.alpha = this.baseAlpha;
                
                // Inercia / masa
                this.density = Math.random() * 0.8 + 0.6;
                
                // Movimiento orgánico flotante
                this.floatAngle = Math.random() * Math.PI * 2;
                this.floatSpeed = Math.random() * 0.02 + 0.008;
                this.floatDist = isGrid ? (Math.random() * 2 + 1) : (Math.random() * 6 + 3);
            }

            update() {
                // Si el ratón está cerca, efecto repulsión antigravedad
                if (mouse.isActive && mouse.x !== null && mouse.y !== null) {
                    const dx = mouse.x - this.x;
                    const dy = mouse.y - this.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < mouse.radius) {
                        const forceDirectionX = dx / distance;
                        const forceDirectionY = dy / distance;
                        const force = (mouse.radius - distance) / mouse.radius; // 0 a 1
                        
                        // Empuje hacia afuera (repulsión estilo Anti-Gravity)
                        const repelStrength = 16 * this.density;
                        this.vx -= forceDirectionX * force * repelStrength;
                        this.vy -= forceDirectionY * force * repelStrength;
                        
                        // Crece y se ilumina sutilmente al interactuar
                        this.radius = this.baseRadius + force * 2.5;
                        this.alpha = Math.min(0.9, this.baseAlpha + force * 0.55);
                    } else {
                        // Suavizar retorno de tamaño y brillo
                        this.radius += (this.baseRadius - this.radius) * 0.08;
                        this.alpha += (this.baseAlpha - this.alpha) * 0.05;
                    }
                } else {
                    this.radius += (this.baseRadius - this.radius) * 0.08;
                    this.alpha += (this.baseAlpha - this.alpha) * 0.05;
                }

                // Fuerza de resorte elástico para volver a su posición base
                this.floatAngle += this.floatSpeed;
                const targetX = this.baseX + Math.cos(this.floatAngle) * this.floatDist;
                const targetY = this.baseY + Math.sin(this.floatAngle) * this.floatDist;

                const homeDx = targetX - this.x;
                const homeDy = targetY - this.y;

                this.vx += homeDx * 0.045; // Constante del resorte
                this.vy += homeDy * 0.045;

                // Fricción / amortiguación suave
                this.vx *= 0.85;
                this.vy *= 0.85;

                this.x += this.vx;
                this.y += this.vy;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, Math.max(0.5, this.radius), 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${this.alpha})`;
                ctx.fill();
            }
        }

        function initParticles() {
            particles = [];
            const rect = hero.getBoundingClientRect();
            width = rect.width;
            height = rect.height;

            dpr = window.devicePixelRatio || 1;
            canvas.width = width * dpr;
            canvas.height = height * dpr;
            ctx.resetTransform();
            ctx.scale(dpr, dpr);

            // 1. Grid izquierdo (como el mostrado en la imagen de referencia)
            const leftCols = Math.min(8, Math.floor(width / 70));
            const leftRows = 7;
            const leftSpacingX = 22;
            const leftSpacingY = 22;
            const leftStartX = 40;
            const leftStartY = 30;

            for (let r = 0; r < leftRows; r++) {
                for (let c = 0; c < leftCols; c++) {
                    particles.push(new Particle(
                        leftStartX + c * leftSpacingX,
                        leftStartY + r * leftSpacingY,
                        true
                    ));
                }
            }

            // 2. Grid derecho
            const rightCols = Math.min(8, Math.floor(width / 70));
            const rightRows = 7;
            const rightSpacingX = 22;
            const rightSpacingY = 22;
            const rightStartX = Math.max(leftStartX + leftCols * leftSpacingX + 200, width - 210);
            const rightStartY = 70;

            if (width > 600) {
                for (let r = 0; r < rightRows; r++) {
                    for (let c = 0; c < rightCols; c++) {
                        particles.push(new Particle(
                            rightStartX + c * rightSpacingX,
                            rightStartY + r * rightSpacingY,
                            true
                        ));
                    }
                }
            }

            // 3. Partículas flotantes ambientales dispersas estilo Google Anti-Gravity
            const ambientCount = Math.floor(width / 28); // ~30 a 45 partículas
            for (let i = 0; i < ambientCount; i++) {
                particles.push(new Particle(
                    Math.random() * width,
                    Math.random() * height,
                    false
                ));
            }
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);

            // Dibujar y actualizar partículas
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }

            animationFrameId = requestAnimationFrame(animate);
        }

        // Manejo de eventos de cursor
        function onPointerMove(clientX, clientY) {
            const rect = hero.getBoundingClientRect();
            mouse.x = clientX - rect.left;
            mouse.y = clientY - rect.top;
            mouse.isActive = true;

            // Parallax interactivo sutil de engranajes
            const normX = (mouse.x / width) * 2 - 1; // -1 a 1
            const normY = (mouse.y / height) * 2 - 1; // -1 a 1

            gears.forEach((gear, idx) => {
                const depth = (idx + 1) * 6;
                const sign = idx % 2 === 0 ? -1 : 1;
                const moveX = normX * depth * sign;
                const moveY = normY * depth * 0.75 * sign;
                gear.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        }

        hero.addEventListener('mousemove', function(e) {
            onPointerMove(e.clientX, e.clientY);
        });

        hero.addEventListener('mouseleave', function() {
            mouse.isActive = false;
            mouse.x = null;
            mouse.y = null;

            // Restablecer engranajes con suavidad
            gears.forEach(gear => {
                gear.style.transform = 'translate(0px, 0px)';
            });
        });

        // Soporte táctil para pantallas táctiles / móviles
        hero.addEventListener('touchmove', function(e) {
            if (e.touches && e.touches.length > 0) {
                onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: true });

        hero.addEventListener('touchend', function() {
            mouse.isActive = false;
            mouse.x = null;
            mouse.y = null;
            gears.forEach(gear => {
                gear.style.transform = 'translate(0px, 0px)';
            });
        });

        // Redimensionamiento responsive
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                initParticles();
            }, 150);
        });

        // Iniciar motor de partículas
        initParticles();
        animate();
    })();
    </script>

</body>
</html>
