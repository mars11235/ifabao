<?php
// Header premium con paleta IFABAO
$usuario_logueado = isset($_SESSION['user_id']);
$es_admin = $usuario_logueado && ($_SESSION['user_type'] ?? '') === 'admin';
$contador_carrito = 0;

if ($usuario_logueado && !empty($_SESSION['carrito'])) {
    $contador_carrito = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFABAO - Bellas Artes Oruro</title>
    
    <!-- Fuentes Google -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/premium.css">
</head>
<body>
    <!-- Partículas de fondo -->
    <div id="particles-js" class="particles-container"></div>

    <!-- Header IFABAO CON LOGO -->
    <header class="header-premium" id="mainHeader">
        <div class="header-content">
            <div class="logo-premium">
                <!-- LOGO INSTITUCIONAL IFABAO -->
                <div class="logo-institucion">
                    <img src="imagenes/imh.jpg" alt="IFABAO - Bellas Artes Oruro" class="logo-img"
                         onerror="this.style.display='none'">
                </div>
                <div class="brand-text">
                    <h1>Bellas Artes Oruro</h1>
                    <p>Instituto de Formación Artística</p>
                </div>
            </div>
            
            <nav class="nav-premium">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Inicio
                </a>
                <a href="galeria.php" class="nav-link">
                    <i class="fas fa-palette"></i> Galería
                </a>
                <a href="artistas.php" class="nav-link">
                    <i class="fas fa-users"></i> Artistas
                </a>
                
                <?php if($usuario_logueado): ?>
                    <?php if($es_admin): ?>
                        <a href="admin.php" class="nav-link">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    
                    <a href="carrito.php" class="nav-link cart-item">
                        <i class="fas fa-shopping-cart"></i> Carrito
                        <?php if($contador_carrito > 0): ?>
                            <span class="cart-badge"><?= $contador_carrito ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="perfil.php" class="nav-link">
                        <i class="fas fa-user"></i> Perfil
                    </a>
                    
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Ingresar
                    </a>
                    <a href="register.php" class="btn-premium">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <style>
    /* ESTILOS PARA EL LOGO INSTITUCIONAL */
    .logo-institucion {
        display: flex;
        align-items: center;
        margin-right: 1.5rem;
    }

    .logo-img {
        height: 65px;
        width: auto;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        border: 2px solid rgba(255,255,255,0.1);
    }

    .logo-img:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .brand-text h1 {
        font-size: 1.4rem;
        margin: 0;
        color: white;
        font-weight: 700;
    }

    .brand-text p {
        font-size: 0.9rem;
        margin: 0;
        color: rgba(255,255,255,0.8);
        font-weight: 400;
    }

    /* Header scrolled */
    .header-premium.scrolled .logo-img {
        height: 55px;
    }

    .header-premium.scrolled .brand-text h1 {
        font-size: 1.2rem;
    }

    .header-premium.scrolled .brand-text p {
        font-size: 0.8rem;
    }

    /* Para móviles */
    @media (max-width: 768px) {
        .logo-institucion {
            margin-right: 1rem;
        }
        
        .logo-img {
            height: 50px;
        }
        
        .brand-text h1 {
            font-size: 1.1rem;
        }
        
        .brand-text p {
            font-size: 0.8rem;
        }
        
        .header-premium.scrolled .logo-img {
            height: 45px;
        }
        
        .header-premium.scrolled .brand-text h1 {
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .logo-premium {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }
        
        .logo-institucion {
            margin-right: 0;
            justify-content: center;
        }
        
        .brand-text h1 {
            font-size: 1rem;
        }
        
        .brand-text p {
            font-size: 0.7rem;
        }
    }
    </style>

    <script>
    // Efecto scroll header
    window.addEventListener('scroll', function() {
        const header = document.getElementById('mainHeader');
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    </script>