<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Obtener parámetros de filtro de forma segura
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$busqueda = isset($_GET['busqueda']) ? sanitizarEntrada($_GET['busqueda']) : '';
$tecnica = isset($_GET['tecnica']) ? sanitizarEntrada($_GET['tecnica']) : '';

$limite = 12;
$offset = ($pagina - 1) * $limite;

// Obtener artistas con filtros CORREGIDOS
try {
    $artistas = $db->obtenerArtistas(1000);
    
    // Aplicar filtros de forma más robusta
    if (!empty($busqueda) || !empty($tecnica)) {
        $artistas = array_filter($artistas, function($artista) use ($busqueda, $tecnica) {
            $coincideBusqueda = true;
            $coincideTecnica = true;
            
            if (!empty($busqueda)) {
                $textos = [
                    strtolower($artista['nombre_artistico'] ?? ''),
                    strtolower($artista['biografia'] ?? ''),
                    strtolower($artista['nombre_completo'] ?? ''),
                    strtolower($artista['tecnica_principal'] ?? '')
                ];
                
                $coincideBusqueda = false;
                $busquedaLower = strtolower($busqueda);
                
                foreach ($textos as $texto) {
                    if (strpos($texto, $busquedaLower) !== false) {
                        $coincideBusqueda = true;
                        break;
                    }
                }
            }
            
            if (!empty($tecnica)) {
                $tecnicasArtista = [
                    strtolower($artista['tecnica_principal'] ?? ''),
                    strtolower($artista['tecnica'] ?? '')
                ];
                
                $coincideTecnica = false;
                $tecnicaLower = strtolower($tecnica);
                
                foreach ($tecnicasArtista as $tec) {
                    if (strpos($tec, $tecnicaLower) !== false) {
                        $coincideTecnica = true;
                        break;
                    }
                }
            }
            
            return $coincideBusqueda && $coincideTecnica;
        });
        
        $artistas = array_values($artistas);
    }
    
    $total_artistas = count($artistas);
    
} catch (Exception $e) {
    error_log("Error en artistas.php: " . $e->getMessage());
    $artistas = $db->obtenerArtistasDemo();
    $total_artistas = count($artistas);
}

// Aplicar paginación
$artistas_paginados = array_slice($artistas, $offset, $limite);
$total_paginas = ceil($total_artistas / $limite);
$usando_demo = !$db->verificarConexion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artistas - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --azul-oscuro: #1C242A;
            --gris-claro: #D5D7DD;
            --beige: #C2B39E;
            --naranja-principal: #E88E33;
            --naranja-oscuro: #B34614;
        }
        
        .artistas-section {
            margin-top: 100px;
            padding: 2rem 0;
            background: var(--gris-claro);
            min-height: 100vh;
        }
        
        .hero {
            background: var(--gris-claro);
            padding: 120px 0 60px;
            text-align: center;
            margin-top: 80px;
        }
        
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--azul-oscuro);
        }
        
        .filter-group input, .filter-group select {
            padding: 0.8rem;
            border: 1px solid var(--gris-claro);
            border-radius: 5px;
            background: white;
        }
        
        .artistas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .artista-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid var(--gris-claro);
            animation: fadeInUp 0.6s ease;
        }
        
        .artista-card:hover {
            transform: translateY(-10px);
            border-color: var(--naranja-principal);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .artista-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: var(--beige);
        }
        
        .artista-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .artista-card:hover .artista-image {
            transform: scale(1.05);
        }
        
        .artista-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--naranja-principal);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .artista-content {
            padding: 1.5rem;
        }
        
        .artista-content h3 {
            color: var(--azul-oscuro);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .artista-tecnica {
            color: var(--naranja-oscuro);
            font-weight: 500;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .artista-bio {
            color: #666;
            line-height: 1.5;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .artista-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .obras-count {
            background: var(--gris-claro);
            color: var(--azul-oscuro);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 3rem 0;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gris-claro);
            border-radius: 5px;
            text-decoration: none;
            color: var(--azul-oscuro);
            background: white;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: var(--naranja-principal);
            color: white;
            border-color: var(--naranja-principal);
        }
        
        .pagination .current {
            background: var(--naranja-principal);
            color: white;
            border-color: var(--naranja-principal);
        }
        
        .pagination .disabled {
            background: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .demo-warning {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            border: 1px solid #ffeaa7;
        }
        
        .cta-section {
            background: white;
            padding: 3rem 2rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 3rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .artistas-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .artistas-section {
                margin-top: 80px;
                padding: 1rem 0;
            }
        }
        
        @media (max-width: 480px) {
            .artistas-grid {
                grid-template-columns: 1fr;
            }
            
            .artista-stats {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .artista-stats .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 1rem; color: var(--azul-oscuro);">Nuestros Artistas</h1>
            <p style="text-align: center; max-width: 600px; margin: 0 auto; color: #666;">
                Descubre el talento de los artistas bolivianos que forman parte de IFABAO. 
                Cada artista tiene una historia única que contar a través de su arte.
            </p>
        </div>
    </section>

    <!-- Artistas Section -->
    <section class="artistas-section">
        <div class="container">
            <?php if ($usando_demo): ?>
                <div class="demo-warning">
                    <p>⚠️ Estás viendo datos de demostración. Conecta la base de datos para ver la información real de los artistas.</p>
                </div>
            <?php endif; ?>
            
            <!-- Filtros de Búsqueda -->
            <div class="search-filters">
                <form method="GET" action="" id="filtrosForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="busqueda">Buscar Artista</label>
                            <input type="text" id="busqueda" name="busqueda" 
                                   placeholder="Nombre del artista, técnica..."
                                   value="<?php echo $busqueda; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="tecnica">Técnica</label>
                            <select id="tecnica" name="tecnica">
                                <option value="">Todas las técnicas</option>
                                <option value="pintura" <?php echo $tecnica === 'pintura' ? 'selected' : ''; ?>>Pintura</option>
                                <option value="escultura" <?php echo $tecnica === 'escultura' ? 'selected' : ''; ?>>Escultura</option>
                                <option value="fotografia" <?php echo $tecnica === 'fotografia' ? 'selected' : ''; ?>>Fotografía</option>
                                <option value="digital" <?php echo $tecnica === 'digital' ? 'selected' : ''; ?>>Arte Digital</option>
                                <option value="textil" <?php echo $tecnica === 'textil' ? 'selected' : ''; ?>>Arte Textil</option>
                                <option value="ceramica" <?php echo $tecnica === 'ceramica' ? 'selected' : ''; ?>>Cerámica</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn" style="background: var(--naranja-principal); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; cursor: pointer;">
                                🔍 Buscar
                            </button>
                            <?php if (!empty($busqueda) || !empty($tecnica)): ?>
                                <a href="artistas.php" class="btn" style="background: #666; margin-top: 0.5rem; display: block; text-align: center; color: white; text-decoration: none; padding: 0.8rem; border-radius: 5px;">Limpiar Filtros</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Estadísticas -->
            <div style="text-align: center; margin-bottom: 2rem; background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                <h3 style="color: var(--azul-oscuro);">Encontramos <span style="color: var(--naranja-principal);"><?php echo $total_artistas; ?></span> artista<?php echo $total_artistas !== 1 ? 's' : ''; ?> en IFABAO</h3>
                <?php if (!empty($busqueda) || !empty($tecnica)): ?>
                    <p style="color: #666;">
                        <?php if (!empty($busqueda)): ?>
                            Búsqueda: "<?php echo htmlspecialchars($busqueda); ?>"
                        <?php endif; ?>
                        <?php if (!empty($tecnica)): ?>
                            <?php echo !empty($busqueda) ? ' | ' : ''; ?>Técnica: <?php echo ucfirst($tecnica); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <?php if (empty($artistas_paginados)): ?>
                <div class="empty-state">
                    <h3 style="color: var(--azul-oscuro); margin-bottom: 1rem;">No se encontraron artistas</h3>
                    <p style="color: #666; margin-bottom: 1.5rem;">No hay artistas que coincidan con tu búsqueda. Intenta con otros filtros.</p>
                    <a href="artistas.php" class="btn" style="background: var(--naranja-principal); color: white; text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 5px; display: inline-block;">Ver Todos los Artistas</a>
                </div>
            <?php else: ?>
                <div class="artistas-grid" id="artistasGrid">
                    <?php foreach ($artistas_paginados as $index => $artista): ?>
                        <div class="artista-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="artista-image-container">
                                <img src="<?php echo htmlspecialchars($artista['imagen'] ?? 'images/artista-default.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($artista['nombre_artistico']); ?>"
                                     class="artista-image"
                                     onerror="this.src='images/artista-default.jpg'">
                                
                                <?php if (($artista['total_obras'] ?? 0) > 5): ?>
                                    <span class="artista-badge">⭐ Destacado</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="artista-content">
                                <h3><?php echo htmlspecialchars($artista['nombre_artistico']); ?></h3>
                                <div class="artista-tecnica">
                                    🎨 <?php echo htmlspecialchars($artista['tecnica_principal'] ?? $artista['tecnica'] ?? 'Arte Visual'); ?>
                                </div>
                                <div class="artista-bio">
                                    <?php 
                                    $biografia = $artista['biografia'] ?? 'Artista talentoso con una visión única del arte boliviano contemporáneo. Su trabajo refleja la riqueza cultural y la diversidad de nuestra tierra.';
                                    echo htmlspecialchars(strlen($biografia) > 120 ? substr($biografia, 0, 120) . '...' : $biografia);
                                    ?>
                                </div>
                                <div class="artista-stats">
                                    <span class="obras-count">
                                        <?php echo $artista['total_obras'] ?? 0; ?> obra<?php echo ($artista['total_obras'] ?? 0) !== 1 ? 's' : ''; ?>
                                    </span>
                                    <a href="artista_perfil.php?id=<?php echo $artista['id']; ?>" class="btn" style="background: var(--naranja-principal); color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px;">
                                        Ver Perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina > 1): ?>
                        <a href="artistas.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" aria-label="Página anterior">« Anterior</a>
                    <?php else: ?>
                        <span class="disabled">« Anterior</span>
                    <?php endif; ?>
                    
                    <?php 
                    $inicio = max(1, $pagina - 2);
                    $fin = min($total_paginas, $pagina + 2);
                    
                    if ($inicio > 1) {
                        echo '<a href="artistas.php?' . http_build_query(array_merge($_GET, ['pagina' => 1])) . '">1</a>';
                        if ($inicio > 2) echo '<span>...</span>';
                    }
                    
                    for ($i = $inicio; $i <= $fin; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="artistas.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1) echo '<span>...</span>'; ?>
                        <a href="artistas.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>"><?php echo $total_paginas; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($pagina < $total_paginas): ?>
                        <a href="artistas.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" aria-label="Página siguiente">Siguiente »</a>
                    <?php else: ?>
                        <span class="disabled">Siguiente »</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Llamada a la acción -->
            <div class="cta-section">
                <h3 style="color: var(--azul-oscuro);">¿Eres artista y quieres unirte a IFABAO?</h3>
                <p style="margin-bottom: 1.5rem; color: #666;">Forma parte de nuestra comunidad y muestra tu talento al mundo.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn" style="background: var(--naranja-oscuro); color: white; text-decoration: none; padding: 1rem 2rem; border-radius: 5px; display: inline-block;">
                        🎨 Publicar mis Obras
                    </a>
                <?php else: ?>
                    <a href="register.php?tipo=artista" class="btn" style="background: var(--naranja-oscuro); color: white; text-decoration: none; padding: 1rem 2rem; border-radius: 5px; display: inline-block;">
                        🎨 Registrarme como Artista
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>