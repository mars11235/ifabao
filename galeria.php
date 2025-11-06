<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Parámetros de filtro optimizados
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$categoria_id = intval($_GET['categoria'] ?? 0) ?: null;
$artista_id = intval($_GET['artista'] ?? 0) ?: null;
$busqueda = sanitizarEntrada($_GET['busqueda'] ?? '');

$limite = 12;
$offset = ($pagina - 1) * $limite;

// Obtener datos
$obras = $db->obtenerObras($limite, $pagina, $categoria_id, $artista_id);
$total_obras = count($db->obtenerObras(1000, 1, $categoria_id, $artista_id));
$total_paginas = ceil($total_obras / $limite);
$categorias = $db->obtenerCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --azul-oscuro: #1C242A;
            --gris-claro: #D5D7DD;
            --beige: #C2B39E;
            --naranja-principal: #E88E33;
            --naranja-oscuro: #B34614;
            --shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .galeria-container { 
            margin-top: 100px; 
            padding: 2rem 0; 
            background: var(--gris-claro);
            min-height: 100vh;
        }
        .galeria-header { 
            text-align: center; 
            margin-bottom: 3rem; 
            color: var(--azul-oscuro);
        }
        
        .filtros-gallery {
            background: white; 
            padding: 1.5rem; 
            border-radius: 10px;
            box-shadow: var(--shadow); 
            margin-bottom: 2rem;
            border: 1px solid var(--gris-claro);
        }
        .filtros-row {
            display: grid; 
            grid-template-columns: 2fr 1fr 1fr auto;
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
        
        .galeria-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem; 
            padding: 1rem 0;
        }
        .obra-card {
            background: white; 
            border-radius: 10px; 
            overflow: hidden;
            box-shadow: var(--shadow); 
            transition: transform 0.3s ease;
            border: 1px solid var(--gris-claro);
        }
        .obra-card:hover { 
            transform: translateY(-5px); 
            border-color: var(--naranja-principal);
        }
        .card-image {
            position: relative; 
            width: 100%; 
            height: 250px; 
            overflow: hidden;
            background: var(--beige);
        }
        .card-image img {
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .obra-card:hover .card-image img { 
            transform: scale(1.05); 
        }
        .card-badge {
            position: absolute; 
            top: 10px; 
            right: 10px;
            background: var(--naranja-principal); 
            color: white; 
            padding: 0.3rem 0.6rem;
            border-radius: 15px; 
            font-size: 0.8rem; 
            font-weight: bold;
        }
        .card-content { 
            padding: 1.5rem; 
        }
        .card-content h3 { 
            color: var(--azul-oscuro); 
            margin-bottom: 0.5rem; 
        }
        .artist { 
            color: var(--naranja-oscuro); 
            font-weight: 500; 
            margin-bottom: 0.5rem; 
        }
        .price { 
            color: var(--naranja-principal); 
            font-size: 1.3rem; 
            font-weight: bold; 
            margin-bottom: 1rem; 
        }
        .card-actions { 
            display: flex; 
            gap: 0.5rem; 
            margin-top: 1rem; 
        }
        .btn-details { 
            background: var(--gris-claro); 
            color: var(--azul-oscuro); 
            flex: 1; 
            text-align: center; 
            padding: 0.8rem; 
            text-decoration: none; 
            border-radius: 5px; 
            transition: all 0.3s ease;
        }
        .btn-details:hover {
            background: var(--beige);
        }
        .btn-cart { 
            background: var(--naranja-principal); 
            color: white; 
            padding: 0.8rem; 
            text-decoration: none; 
            border-radius: 5px; 
            transition: all 0.3s ease;
        }
        .btn-cart:hover {
            background: var(--naranja-oscuro);
        }
        
        .pagination {
            display: flex; 
            justify-content: center; 
            gap: 0.5rem;
            margin-top: 3rem; 
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
        
        @media (max-width: 768px) {
            .filtros-row { 
                grid-template-columns: 1fr; 
            }
            .galeria-grid { 
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            }
            .galeria-container { 
                margin-top: 80px; 
                padding: 1rem 0; 
            }
        }
        @media (max-width: 480px) {
            .galeria-grid { 
                grid-template-columns: 1fr; 
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container galeria-container">
        <div class="galeria-header">
            <h1>Galería de Obras</h1>
            <p>Descubre todas nuestras obras de arte exclusivas de IFABAO</p>
        </div>
        
        <!-- Filtros -->
        <div class="filtros-gallery">
            <form method="GET" id="filtros-form">
                <div class="filtros-row">
                    <div class="filter-group">
                        <label>Buscar Obras</label>
                        <input type="text" name="busqueda" placeholder="Título, artista, técnica..." value="<?= $busqueda ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Categoría</label>
                        <select name="categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Ordenar por</label>
                        <select name="orden">
                            <option value="recientes">Más recientes</option>
                            <option value="precio-bajo">Precio: Menor a mayor</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" style="background: var(--naranja-principal); color: white; padding: 0.8rem; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($busqueda || $categoria_id): ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="galeria.php" style="color: var(--naranja-principal); text-decoration: none;">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Resultados -->
        <div style="text-align: center; margin-bottom: 2rem; background: white; padding: 1rem; border-radius: 10px;">
            <h3 style="color: var(--azul-oscuro);">Encontramos <span style="color: var(--naranja-principal);"><?= $total_obras ?></span> obras</h3>
        </div>
        
        <?php if(empty($obras)): ?>
            <div style="text-align: center; padding: 4rem 2rem; color: #666; background: white; border-radius: 10px;">
                <h3 style="color: var(--azul-oscuro); margin-bottom: 1rem;">No hay obras disponibles</h3>
                <p>
                    <?= $busqueda || $categoria_id ? 
                        'No se encontraron obras con esos filtros.' : 
                        'Próximamente nuevas obras.' 
                    ?>
                </p>
                <a href="galeria.php" style="display: inline-block; margin-top: 1rem; padding: 0.8rem 1.5rem; background: var(--naranja-principal); color: white; text-decoration: none; border-radius: 5px;">
                    Ver Todas las Obras
                </a>
            </div>
        <?php else: ?>
            <div class="galeria-grid">
                <?php foreach($obras as $obra): ?>
                <div class="obra-card">
                    <div class="card-image">
                        <img src="<?= htmlspecialchars($obra['imagen'] ?? 'images/obra-default.jpg') ?>" 
                             alt="<?= htmlspecialchars($obra['titulo']) ?>"
                             onerror="this.src='images/obra-default.jpg'">
                        
                        <?php if($obra['destacada'] ?? false): ?>
                            <div class="card-badge">⭐ Destacada</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-content">
                        <h3><?= htmlspecialchars($obra['titulo']) ?></h3>
                        <p class="artist">Por: <?= htmlspecialchars($obra['artista_nombre']) ?></p>
                        <p class="price">Bs. <?= number_format($obra['precio'], 2) ?></p>
                        
                        <div class="card-actions">
                            <a href="obra.php?id=<?= $obra['id'] ?>" class="btn-details">
                                Ver Detalles
                            </a>
                            <?php if(($obra['estado'] ?? '') === 'disponible'): ?>
                                <a href="carrito.php?agregar=<?= $obra['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                   class="btn-cart"
                                   onclick="return confirm('¿Agregar al carrito?')">
                                    <i class="fas fa-shopping-cart"></i> Agregar
                                </a>
                            <?php else: ?>
                                <span style="background: #6c757d; color: white; padding: 0.8rem; border-radius: 5px;">
                                    No Disponible
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="galeria.php?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                        « Anterior
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="galeria.php?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <a href="galeria.php?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                        Siguiente »
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit al cambiar selects
        document.querySelector('select[name="categoria"]')?.addEventListener('change', function() {
            document.getElementById('filtros-form').submit();
        });
        
        document.querySelector('select[name="orden"]')?.addEventListener('change', function() {
            document.getElementById('filtros-form').submit();
        });
    });
    </script>
</body>
</html>