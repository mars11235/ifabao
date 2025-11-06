<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();
$estadisticas = $db->obtenerEstadisticas();
$obras = $db->obtenerObras(8);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --azul-oscuro: #1C242A;
            --gris-claro: #D5D7DD;
            --beige: #C2B39E;
            --naranja-principal: #E88E33;
            --naranja-oscuro: #B34614;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--gris-claro); 
            color: var(--azul-oscuro);
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        /* Hero Section IFABAO - MEJORADO */
        .hero {
            background: linear-gradient(135deg, var(--naranja-principal), var(--naranja-oscuro));
            color: white; 
            padding: 140px 0 80px; 
            text-align: center;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }
        
        .hero .container {
            position: relative;
            z-index: 2;
        }
        
        .hero-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .hero-logo-img {
            height: 100px;
            width: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 3px solid rgba(255,255,255,0.2);
        }
        
        .hero h1 { 
            font-size: 3.2rem; 
            margin-bottom: 1.5rem; 
            text-shadow: 3px 3px 10px rgba(0,0,0,0.7);
            font-weight: 800;
            line-height: 1.2;
        }
        
        .hero p { 
            font-size: 1.3rem; 
            margin-bottom: 2.5rem; 
            opacity: 0.95; 
            text-shadow: 2px 2px 5px rgba(0,0,0,0.5);
            font-weight: 400;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn {
            display: inline-flex; 
            align-items: center; 
            gap: 10px;
            padding: 15px 30px; 
            text-decoration: none; 
            border-radius: 50px; 
            font-weight: bold;
            transition: transform 0.3s ease;
            border: 2px solid transparent;
        }
        
        .btn-primary {
            background: var(--beige); 
            color: var(--azul-oscuro);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary:hover { 
            transform: translateY(-3px); 
            background: white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .btn-secondary {
            background: transparent; 
            border: 2px solid white; 
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary:hover {
            background: white;
            color: var(--azul-oscuro);
            transform: translateY(-3px);
        }
        
        .hero-actions {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Stats */
        .stats-section { 
            padding: 80px 0; 
            background: white;
        }
        .stats-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        .stat-card {
            text-align: center; 
            padding: 2rem; 
            background: white;
            border-radius: 15px; 
            box-shadow: var(--shadow);
            border: 2px solid var(--gris-claro);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--naranja-principal);
        }
        
        .stat-number { 
            font-size: 2.5rem; 
            font-weight: bold; 
            color: var(--naranja-principal); 
            margin-bottom: 0.5rem;
        }
        
        .stat-card div {
            font-size: 1.1rem;
            color: var(--azul-oscuro);
            font-weight: 600;
        }
        
        /* Featured */
        .featured { 
            padding: 80px 0; 
            background: var(--gris-claro);
        }
        .section-title {
            text-align: center; 
            font-size: 2.5rem; 
            margin-bottom: 3rem;
            position: relative;
            color: var(--azul-oscuro);
            font-weight: 700;
        }
        .section-title::after {
            content: ''; 
            position: absolute; 
            bottom: -10px; 
            left: 50%;
            transform: translateX(-50%); 
            width: 80px; 
            height: 4px;
            background: var(--naranja-principal);
        }
        .art-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem; 
            margin-bottom: 3rem;
        }
        .art-card {
            background: white; 
            border-radius: 15px; 
            overflow: hidden;
            box-shadow: var(--shadow); 
            transition: transform 0.3s ease;
            border: 1px solid var(--gris-claro);
        }
        .art-card:hover { 
            transform: translateY(-10px); 
            border-color: var(--naranja-principal);
        }
        .art-image {
            position: relative; 
            padding-bottom: 75%; 
            overflow: hidden;
            background: var(--beige);
        }
        .art-image img {
            position: absolute; 
            top: 0; 
            left: 0;
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .art-card:hover .art-image img {
            transform: scale(1.05);
        }
        
        .art-content { padding: 1.5rem; }
        .art-title { 
            font-size: 1.2rem; 
            font-weight: bold; 
            margin-bottom: 0.5rem; 
            color: var(--azul-oscuro);
            line-height: 1.3;
        }
        .art-price { 
            font-size: 1.3rem; 
            font-weight: bold; 
            color: var(--naranja-principal); 
            margin-bottom: 1rem;
        }
        
        .art-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-details {
            flex: 1;
            padding: 0.8rem;
            background: var(--gris-claro);
            color: var(--azul-oscuro);
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-details:hover {
            background: var(--beige);
        }
        
        .btn-buy {
            flex: 1;
            padding: 0.8rem;
            background: var(--naranja-principal);
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-buy:hover {
            background: var(--naranja-oscuro);
        }
        
        /* CTA */
        .cta-section {
            background: linear-gradient(135deg, var(--azul-oscuro), #2A343A);
            color: white; 
            padding: 80px 0; 
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.2rem; }
            .hero p { font-size: 1.1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .hero { margin-top: 70px; padding: 100px 0 60px; }
            .section-title { font-size: 2rem; }
            .hero-actions { flex-direction: column; align-items: center; }
            .btn { width: 250px; justify-content: center; }
            .cta-section h2 { font-size: 1.8rem; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 1.8rem; }
            .hero p { font-size: 1rem; }
            .art-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section IFABAO - MEJORADO -->
    <section class="hero">
        <div class="container">
            <!-- Logo en el hero -->
            <div class="hero-logo">
                <img src="imagenes/imh.jpg" alt="IFABAO - Bellas Artes Oruro" class="hero-logo-img"
                     onerror="this.style.display='none'">
            </div>
            
            <h1>Descubre el Alma del Arte Boliviano</h1>
            <p>Obras exclusivas de talentosos artistas de Bellas Artes Oruro</p>
            <div class="hero-actions">
                <a href="galeria.php" class="btn btn-primary">
                    <i class="fas fa-palette"></i> Explorar Galería
                </a>
                <a href="artistas.php" class="btn btn-secondary">
                    <i class="fas fa-users"></i> Conocer Artistas
                </a>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_obras'] ?></div>
                    <div>Obras de Arte</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_artistas'] ?></div>
                    <div>Artistas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_categorias'] ?></div>
                    <div>Categorías</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div>Arte Auténtico</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured -->
    <section class="featured">
        <div class="container">
            <h2 class="section-title">Obras Destacadas</h2>
            
            <div class="art-grid">
                <?php foreach($obras as $obra): ?>
                <div class="art-card">
                    <div class="art-image">
                        <img src="<?= htmlspecialchars($obra['imagen'] ?? 'images/obra-default.jpg') ?>" 
                             alt="<?= htmlspecialchars($obra['titulo']) ?>"
                             onerror="this.src='images/obra-default.jpg'">
                    </div>
                    <div class="art-content">
                        <h3 class="art-title"><?= htmlspecialchars($obra['titulo']) ?></h3>
                        <p style="color: #666; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($obra['artista_nombre']) ?>
                        </p>
                        <div class="art-price">Bs. <?= number_format($obra['precio'], 2) ?></div>
                        <div class="art-actions">
                            <a href="obra.php?id=<?= $obra['id'] ?>" class="btn-details">
                                Ver Detalles
                            </a>
                            <a href="carrito.php?agregar=<?= $obra['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                               class="btn-buy">
                                Comprar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center;">
                <a href="galeria.php" class="btn" style="background: var(--naranja-principal); color: white;">
                    <i class="fas fa-th-large"></i> Ver Todas las Obras
                </a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>¿Eres Artista?</h2>
            <p>
                Únete a nuestra comunidad y comparte tu talento con el mundo
            </p>
            <a href="register.php?tipo=artista" class="btn" style="background: var(--naranja-principal); color: white;">
                <i class="fas fa-rocket"></i> Comenzar Mi Viaje Artístico
            </a>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>