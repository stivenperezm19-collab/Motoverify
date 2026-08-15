<?php
require_once '../config/database.php';

// Validar que exista el ID en la URL
$id = $_GET['id'] ?? null;

if (!$id) {
    die("Error: No se especificó el ID del repuesto.");
}

// 1. Obtener la información principal del repuesto
$query = "SELECT r.*, 
          m.nombre as marca_nombre, 
          c.nombre as categoria_nombre 
          FROM repuestos r
          LEFT JOIN marcas m ON r.marca_id = m.id
          LEFT JOIN catalogos c ON r.catalogo_id = c.id
          WHERE r.id = ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Repuesto no encontrado en la base de datos.");
}

$repuesto = $result->fetch_assoc();

// 2. Obtener ubicacion de bodega
$ubicacion_texto = "Bodega Principal";
if (isset($repuesto['ubicacion_id']) && !empty($repuesto['ubicacion_id'])) {
    $ubi_query = "SELECT * FROM ubicaciones WHERE id = ?";
    $stmt_ubi = $conn->prepare($ubi_query);
    $stmt_ubi->bind_param("i", $repuesto['ubicacion_id']);
    $stmt_ubi->execute();
    $res_ubi = $stmt_ubi->get_result();
    if ($ubi = $res_ubi->fetch_assoc()) {
        $ubicacion_texto = $ubi['nombre'] ?? '';
        if(isset($ubi['pasillo'])) $ubicacion_texto .= " > Pasillo " . $ubi['pasillo'];
        if(isset($ubi['estante'])) $ubicacion_texto .= " > Estante " . $ubi['estante'];
    }
} else if (isset($repuesto['ubicacion']) && !empty($repuesto['ubicacion'])) {
    $ubicacion_texto = $repuesto['ubicacion'];
}

// 3. Obtener modelos de moto compatibles
$motos_compatibles_array = [];
$comp_query = "SELECT modelo_moto FROM repuesto_compatibilidad WHERE repuesto_id = ?";
$stmt_comp = $conn->prepare($comp_query);
$stmt_comp->bind_param("i", $id);
$stmt_comp->execute();
$res_comp = $stmt_comp->get_result();
while ($row = $res_comp->fetch_assoc()) {
    $motos_compatibles_array[] = $row['modelo_moto'];
}
$motos_compatibles_texto = count($motos_compatibles_array) > 0 ? implode(", ", $motos_compatibles_array) : "Universal / No especificado";

// Preparar los datos reales o fallbacks estáticos para el diseño
$codigo_barras = htmlspecialchars($repuesto['codigo_barras'] ?? 'S/N');
$nombre = htmlspecialchars($repuesto['nombre']);
$marca = htmlspecialchars($repuesto['marca_nombre'] ?? 'Sin Marca');
$categoria = htmlspecialchars($repuesto['categoria_nombre'] ?? 'General');
$descripcion = !empty($repuesto['descripcion']) ? nl2br(htmlspecialchars($repuesto['descripcion'])) : "La biela es una pieza fundamental del motor que conecta el pistón con el cigüeñal, transformando el movimiento lineal en rotativo. Fabricada en acero forjado de alta resistencia, ofrece mayor durabilidad y rendimiento incluso en condiciones exigentes.";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nombre ?> - Ficha Técnica</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        /* Variables de color según el mockup */
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

        /* --- Columna Izquierda --- */
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
            position: relative;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }

        .codigo-badge {
            position: absolute;
            top: 30px;
            left: 30px;
            color: var(--color-text-navy);
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
            max-width: 90%;
            max-height: 80%;
            object-fit: contain;
            filter: drop-shadow(0px 20px 20px rgba(0,0,0,0.25));
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

        /* --- Columna Derecha --- */
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

        /* --- Accordions --- */
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

    <!-- Header -->
    <header class="top-navbar">
        <h1 class="header-title"><?= $nombre ?></h1>
        
        <div class="header-search-container">
            <div class="search-pill">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Búsqueda" readonly style="cursor: pointer;" onclick="window.location.href='index.php'">
            </div>
        </div>

        <a href="index.php" class="btn-close-custom" title="Cerrar y volver">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="row g-4">
            
            <!-- Left Column -->
            <div class="col-lg-5">
                <div class="left-column">
                    <div class="image-box">
                        <div class="codigo-badge">
                            <span>Código</span>
                            <h3><?= $codigo_barras ?></h3>
                        </div>
                        
                        <!-- Imagen estática de demostración ya que la BD no suele almacenar binarios de imagen completos para esto -->
                        <i class="fa-solid fa-gear" style="font-size: 150px; color: rgba(255,255,255,0.7); filter: drop-shadow(0px 10px 10px rgba(0,0,0,0.1));"></i>
                        
                        <div class="arrow-btn">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </div>
                    
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

            <!-- Right Column -->
            <div class="col-lg-7">
                <div class="right-column">
                    
                    <h4 class="section-title">Información del repuesto</h4>
                    
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

                        <!-- Datos estáticos (Simulados para el diseño) -->
                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-wrench"></i></div>
                            <div class="info-details">
                                <h6>Cilindraje:</h6>
                                <p>135cc, 180cc, 200cc</p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-layer-group"></i></div>
                            <div class="info-details">
                                <h6>Material:</h6>
                                <p>Acero forjado de alta resistencia</p>
                            </div>
                        </div>
                    </div>

                    <h4 class="section-title">Detalles del producto</h4>
                    <p class="product-desc"><?= $descripcion ?></p>
                    
                    <ul class="product-bullets">
                        <li>Diseñada para soportar altas cargas y temperaturas.</li>
                        <li>Acabado preciso para un ajuste perfecto y reducción de fricción.</li>
                        <li>Mejora la eficiencia del motor y prolonga su vida útil.</li>
                        <li>Repuesto original de calidad garantizada.</li>
                    </ul>

                    <!-- Accordion -->
                    <div class="accordion" id="specsAccordion">
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <div class="accordion-icon"><i class="fa-solid fa-file-lines"></i></div>
                                    Especificaciones técnicas
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#specsAccordion">
                                <div class="accordion-body">
                                    <table class="specs-table">
                                        <tr>
                                            <td>Longitud centro a centro:</td>
                                            <td>116.20 mm</td>
                                        </tr>
                                        <tr>
                                            <td>Diámetro cabeza de biela (mayor):</td>
                                            <td>47.00 mm</td>
                                        </tr>
                                        <tr>
                                            <td>Diámetro pie de biela (menor):</td>
                                            <td>20.00 mm</td>
                                        </tr>
                                        <tr>
                                            <td>Peso aproximado:</td>
                                            <td>325 g</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <div class="accordion-icon"><i class="fa-solid fa-border-all"></i></div>
                                    Compatibilidad detallada
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#specsAccordion">
                                <div class="accordion-body p-3 text-muted" style="font-size: 0.9rem;">
                                    Este repuesto aplica para las motocicletas listadas en el apartado superior. Verifique siempre el manual de servicio para tolerancias específicas.
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
            
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
