<?php
// Incluimos la conexión a la base de datos para poder buscar información
require_once '../config/database.php';

// Verificamos que en la dirección web (URL) venga el ID del repuesto que queremos ver
$id = $_GET['id'] ?? null;

// Si no nos dieron ningún ID, detenemos todo y mostramos un error
if (!$id) {
    die("Error: No se especificó el ID del repuesto.");
}

// Iniciamos o retomamos la sesión (para recordar si el usuario está conectado o su historial)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Si el usuario no tiene un "historial" de visitas creado, se lo creamos vacío
if (!isset($_SESSION['historial'])) {
    $_SESSION['historial'] = [];
}
// Convertimos el ID a un número entero por seguridad
$id_int = intval($id);
// Si este repuesto NO está en su historial reciente...
if (!in_array($id_int, $_SESSION['historial'])) {
    // Lo agregamos al principio de la lista
    array_unshift($_SESSION['historial'], $id_int);
    // Si su historial ya tiene más de 5 repuestos, borramos el más antiguo
    if (count($_SESSION['historial']) > 5) {
        array_pop($_SESSION['historial']);
    }
} else {
    // Si el repuesto YA estaba en su historial, lo sacamos de donde estaba 
    // y lo volveamos a poner al principio (porque lo acaba de ver otra vez)
    $key = array_search($id_int, $_SESSION['historial']);
    unset($_SESSION['historial'][$key]);
    array_unshift($_SESSION['historial'], $id_int);
}

// 1. Buscamos toda la información del repuesto en la base de datos
// También traemos el nombre de su "Marca" y el nombre de su "Categoría"
$query = "SELECT r.*, 
          m.nombre as marca_nombre, 
          c.nombre as categoria_nombre 
          FROM REPUESTO r
          LEFT JOIN MARCA m ON r.id_marca = m.id_marca
          LEFT JOIN CATEGORIA c ON r.id_categoria = c.id_categoria
          WHERE r.id_repuesto = ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id); // Ponemos el ID del repuesto en la búsqueda
$stmt->execute();
$result = $stmt->get_result();

// Si la búsqueda no arrojó resultados, el repuesto no existe
if ($result->num_rows === 0) {
    die("Error: Repuesto no encontrado en la base de datos.");
}

// Extraemos los datos del repuesto que encontramos
$repuesto = $result->fetch_assoc();

// 1b. Buscamos la imagen principal asociada a este repuesto
$queryImagen = "SELECT img.ruta_archivo 
                FROM IMAGEN img 
                INNER JOIN REPUESTO_IMAGEN ri ON img.id_imagen = ri.id_imagen 
                WHERE ri.id_repuesto = ? AND img.estado = 'ACTIVO'
                ORDER BY ri.imagen_principal DESC, ri.orden ASC 
                LIMIT 1";
$stmtImg = $conn->prepare($queryImagen);
$stmtImg->bind_param("i", $id);
$stmtImg->execute();
$resultImg = $stmtImg->get_result();
$imagen_repuesto = null;
if ($resultImg->num_rows > 0) {
    $filaImg = $resultImg->fetch_assoc();
    $imagen_repuesto = $filaImg['ruta_archivo'];
}
$stmtImg->close();

// 2. Ubicación: Por ahora es un texto fijo, pero en el futuro podría venir de la base de datos
$ubicacion_texto = "Bodega Principal"; // Estático por defecto

// 3. Obtenemos la lista de motos compatibles con este repuesto
$motos_compatibles_array = [];
if (!empty($repuesto['compatibilidades'])) {
    // Separamos el texto por comas para tener cada moto por separado
    $motos_compatibles_array = array_map('trim', explode(',', $repuesto['compatibilidades']));
}
// Juntamos la lista para mostrarla bonita, o decimos que es Universal si no hay nada
$motos_compatibles_texto = count($motos_compatibles_array) > 0 ? implode(", ", $motos_compatibles_array) : "Universal / No especificado";

// 4. Preparamos los textos finales que se van a mostrar en la página.
// Usamos "htmlspecialchars" para evitar que se ejecute código malicioso si alguien guardó algo raro en la base de datos.
$codigo_mostrar = htmlspecialchars(!empty($repuesto['codigo_barras']) ? $repuesto['codigo_barras'] : ($repuesto['codigo_interno'] ?? 'S/N'));
$codigo_real_db = !empty($repuesto['codigo_barras']) ? $repuesto['codigo_barras'] : '';
$nombre = htmlspecialchars($repuesto['nombre'] ?? 'Sin Nombre');
$marca = htmlspecialchars($repuesto['marca_nombre'] ?? 'Sin Marca');
$categoria = htmlspecialchars($repuesto['categoria_nombre'] ?? 'General');
$descripcion = !empty($repuesto['descripcion']) ? nl2br(htmlspecialchars($repuesto['descripcion'])) : "Sin descripción detallada.";
$caracteristicas = !empty($repuesto['caracteristicas']) ? nl2br(htmlspecialchars($repuesto['caracteristicas'])) : "No se especificaron características adicionales.";
$observaciones = !empty($repuesto['observaciones']) ? nl2br(htmlspecialchars($repuesto['observaciones'])) : "Ninguna observación adicional.";
$referencia = htmlspecialchars($repuesto['referencia_fabricante'] ?? 'N/A');
$estado = htmlspecialchars($repuesto['estado'] ?? 'ACTIVO');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nombre ?> - Ficha Técnica</title>
    
    <!-- Archivos de diseño y tipos de letra externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Aquí definimos los colores y estilos visuales de la página -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        /* Colores principales guardados como variables para usarlos fácilmente */
        :root {
            --color-dark-blue: #041f5e;
            --color-light-blue-box: #91bfdf;
            --color-text-navy: #0b1a45;
            --color-icon-blue: #1a65d6;
        }

        /* --- Barra Superior (Header) --- */
        .top-navbar {
            background-color: var(--color-dark-blue);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 40%;
        }

        .header-search-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
        }

        .search-pill {
            background-color: white;
            border-radius: 50px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 400px;
            color: #888;
        }
        .search-pill i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        .search-pill input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
            color: #333;
        }

        .btn-close-custom {
            width: 45px;
            height: 45px;
            background-color: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff3333;
            font-size: 1.5rem;
            text-decoration: none;
            border: 2px solid white;
            transition: all 0.2s;
        }
        .btn-close-custom:hover {
            background-color: rgba(255,255,255,0.1);
            color: #ff3333;
        }

        /* --- Contenedor Principal --- */
        .main-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* --- Columna Izquierda (Imagen y destacados) --- */
        .left-column {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 0;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .image-box {
            background-color: var(--color-light-blue-box);
            padding: 30px;
            padding-top: 100px;
            position: relative;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 420px;
        }

        .codigo-badge {
            position: absolute;
            top: 30px;
            left: 30px;
            color: var(--color-text-navy);
            z-index: 2;
        }
        .codigo-badge span {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .codigo-badge span::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #007bff;
            border-radius: 50%;
        }
        .codigo-badge h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 5px 0 0 0;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.5px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 320px;
            object-fit: contain;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.85);
            padding: 12px;
            filter: drop-shadow(0px 15px 25px rgba(0,0,0,0.2));
        }
        
        .arrow-btn {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        .highlights-box {
            padding: 25px;
            background: white;
        }
        .highlight-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        .highlight-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f4fc;
            color: var(--color-text-navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            border: 1px solid #d9e2ef;
        }
        .highlight-text h6 {
            font-weight: 700;
            color: var(--color-text-navy);
            margin-bottom: 4px;
            font-size: 0.9rem;
        }
        .highlight-text p {
            font-size: 0.75rem;
            color: #666;
            margin: 0;
            line-height: 1.4;
        }

        /* --- Columna Derecha (Información y Detalles) --- */
        .right-column {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 35px 40px;
            height: 100%;
        }

        .section-title {
            color: var(--color-text-navy);
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 35px;
        }

        .info-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .info-item:nth-last-child(-n+2) {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-icon {
            color: var(--color-text-navy);
            font-size: 1.4rem;
            margin-top: 2px;
            width: 25px;
            text-align: center;
        }
        .info-details h6 {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--color-text-navy);
            margin-bottom: 4px;
        }
        .info-details p {
            font-size: 0.9rem;
            color: #555;
            margin: 0;
        }

        .product-desc {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .product-bullets {
            padding-left: 20px;
            margin-bottom: 35px;
        }
        .product-bullets li {
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 8px;
            position: relative;
            list-style-type: none;
        }
        .product-bullets li::before {
            content: '•';
            color: #1a65d6; /* Blue bullet */
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
            font-size: 1.2rem;
            line-height: 0.8;
            position: relative;
            top: 2px;
        }

        /* --- Acordeones (Cajas que se expanden y contraen) --- */
        .accordion-item {
            border: 1px solid #eee;
            border-radius: 8px !important;
            margin-bottom: 15px;
            overflow: hidden;
            background-color: #f9fbfd;
        }
        .accordion-button {
            background-color: #f9fbfd;
            color: var(--color-text-navy);
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: none !important;
            padding: 15px 20px;
            border: none;
        }
        .accordion-button:not(.collapsed) {
            background-color: #f9fbfd;
            color: var(--color-text-navy);
        }
        .accordion-button::after {
            background-size: 1rem;
        }
        .accordion-icon {
            width: 30px;
            height: 30px;
            background: #eef2f7;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-navy);
            margin-right: 12px;
        }

        .accordion-body {
            background: white;
            padding: 0;
            border-top: 1px solid #eee;
        }

        .specs-table {
            width: 100%;
            margin: 0;
        }
        .specs-table tr {
            border-bottom: 1px solid #eee;
        }
        .specs-table tr:last-child {
            border-bottom: none;
        }
        .specs-table td {
            padding: 12px 20px;
            font-size: 0.85rem;
            color: #444;
        }
        .specs-table td:first-child {
            width: 50%;
        }
        .specs-table td:last-child {
            font-weight: 500;
            color: #222;
        }

    </style>
</head>
<body>

    <!-- Inicio de la Barra Superior -->
    <header class="top-navbar">
        <!-- Título del Repuesto -->
        <h1 class="header-title"><?= $nombre ?></h1>
        
        <!-- Buscador Falso (Solo es visual para regresar atrás) -->
        <div class="header-search-container">
            <div class="search-pill" onclick="goBack()" style="cursor: pointer;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Búsqueda" readonly style="cursor: pointer; background: transparent; border: none; outline: none; color: white;">
            </div>
        </div>

        <!-- Botón de Cerrar (Equis roja) -->
        <a href="javascript:void(0)" onclick="goBack()" class="btn-close-custom" title="Cerrar y volver">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </header>

    <!-- Inicio del Contenedor Principal (Donde está toda la información) -->
    <div class="main-container">
        <div class="row g-4">
            
            <!-- Columna Izquierda (Muestra la imagen y los destacados) -->
            <div class="col-lg-5">
                <div class="left-column">
                    <div class="image-box">
                        <!-- Pequeña etiqueta con el código de barras/interno -->
                        <div class="codigo-badge">
                            <span>Código</span>
                            <h3><?= $codigo_mostrar ?></h3>
                            <?php if (!empty($codigo_real_db)): ?>
                            <!-- Botón para descargar el código de barras como imagen -->
                            <button onclick="descargarCodigoBarras('<?= addslashes($codigo_real_db) ?>')" class="btn btn-sm mt-2" style="background-color: rgba(255,255,255,0.6); color: var(--color-text-navy); font-weight: 600; border-radius: 20px; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(4px);">
                                <i class="fa-solid fa-barcode me-1"></i> Código de Barras
                            </button>
                            <?php endif; ?>
                        </div>
                        
                            <!-- Imagen del repuesto (si tiene imagen registrada la muestra, sino muestra un icono de engranaje) -->
                            <?php if (!empty($imagen_repuesto)): ?>
                                <img src="..<?= htmlspecialchars($imagen_repuesto) ?>" alt="<?= $nombre ?>" class="product-image">
                            <?php else: ?>
                                <i class="fa-solid fa-gear" style="font-size: 150px; color: rgba(255,255,255,0.7); filter: drop-shadow(0px 10px 10px rgba(0,0,0,0.1));"></i>
                            <?php endif; ?>
                            
                            <!-- Botón decorativo de flecha -->
                            <div class="arrow-btn">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                    </div>
                    
                    <!-- Caja de textos destacados en la parte inferior de la imagen -->
                    <div class="highlights-box">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="highlight-item">
                                    <div class="highlight-icon"><i class="fa-solid fa-shield-halved"></i></div>
                                    <div class="highlight-text">
                                        <h6>Alta resistencia</h6>
                                        <p>Material forjado de máxima durabilidad.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="highlight-item">
                                    <div class="highlight-icon"><i class="fa-solid fa-gear"></i></div>
                                    <div class="highlight-text">
                                        <h6>Precisión garantizada</h6>
                                        <p>Fabricación con tolerancias exactas para un rendimiento óptimo.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="highlight-item">
                                    <div class="highlight-icon"><i class="fa-regular fa-circle-check"></i></div>
                                    <div class="highlight-text">
                                        <h6>Calidad asegurada</h6>
                                        <p>Probado bajo estándares exigentes para tu tranquilidad.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha (Muestra todos los detalles técnicos) -->
            <div class="col-lg-7">
                <div class="right-column">
                    
                    <h4 class="section-title">Información del repuesto</h4>
                    
                    <!-- Cuadrícula con los datos principales -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-shield"></i></div>
                            <div class="info-details">
                                <h6>Marca:</h6>
                                <p><?= $marca ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-border-all"></i></div>
                            <div class="info-details">
                                <h6>Categoría:</h6>
                                <p><?= $categoria ?></p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-motorcycle"></i></div>
                            <div class="info-details">
                                <h6>Modelos compatibles:</h6>
                                <p><?= $motos_compatibles_texto ?></p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="info-details">
                                <h6>Ubicación:</h6>
                                <p><?= $ubicacion_texto ?></p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-barcode"></i></div>
                            <div class="info-details">
                                <h6>Ref. Fabricante:</h6>
                                <p><?= $referencia ?></p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="info-details">
                                <h6>Estado:</h6>
                                <p><?= $estado ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Descripción larga del producto -->
                    <h4 class="section-title">Detalles del producto</h4>
                    <p class="product-desc"><?= $descripcion ?></p>

                    <!-- Secciones colapsables (Acordeones) para información técnica extra -->
                    <div class="accordion" id="specsAccordion">
                        
                        <!-- Caja de Características Principales -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <div class="accordion-icon"><i class="fa-solid fa-list-check"></i></div>
                                    Características Principales
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#specsAccordion">
                                <div class="accordion-body p-3 text-muted" style="font-size: 0.9rem;">
                                    <?= $caracteristicas ?>
                                </div>
                            </div>
                        </div>

                        <!-- Caja de Observaciones -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <div class="accordion-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                                    Observaciones Adicionales
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#specsAccordion">
                                <div class="accordion-body p-3 text-muted" style="font-size: 0.9rem;">
                                    <?= $observaciones ?>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
            
        </div>
    </div>

    <!-- Scripts necesarios para hacer que funcionen los botones y los acordeones -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para regresar a la página anterior
        function goBack() {
            // Si el navegador sabe de dónde venimos, nos regresa ahí
            if (document.referrer && document.referrer.includes(window.location.hostname)) {
                window.history.back();
            } else {
                // Si entraron directo a este enlace, verificamos si es administrador
                <?php if (isset($_SESSION['user_id'])): ?>
                    window.location.href = '../views/admin/dashboard.php'; // Lo mandamos al panel
                <?php else: ?>
                    window.location.href = 'index.php'; // Lo mandamos al inicio
                <?php endif; ?>
            }
        }

        // Función que genera y descarga una imagen con el código de barras
        function descargarCodigoBarras(texto) {
            if (texto === 'S/N' || !texto) {
                alert('Este repuesto no tiene un código asignado.');
                return;
            }
            
            // Cambiamos el texto del botón mientras carga la imagen
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Descargando...';
            btn.disabled = true;

            // Usamos un servicio externo para generar el dibujo del código de barras
            const url = `https://bwipjs-api.metafloor.com/?bcid=code128&text=${encodeURIComponent(texto)}&scale=3&includetext=true`;
            
            // Descargamos la imagen sin abrir otra pestaña
            fetch(url)
                .then(response => response.blob())
                .then(blob => {
                    const blobUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = `CodigoBarras_${texto}.png`; // Nombre del archivo a guardar
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(blobUrl);
                    
                    // Volvemos el botón a la normalidad
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                })
                .catch(error => {
                    // Si algo falla, intentamos abrir la imagen en otra pestaña como plan B
                    console.error('Error al descargar código de barras:', error);
                    window.open(url, '_blank');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        }
    </script>
</body>
</html>
