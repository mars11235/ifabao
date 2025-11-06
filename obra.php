<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Obtener ID de la obra de forma segura
$obra_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($obra_id <= 0) {
    header('Location: galeria.php');
    exit;
}

// Obtener información completa de la obra (ya incrementa vistas internamente)
$obra = $db->obtenerObraCompleta($obra_id);

if (!$obra) {
    $_SESSION['error'] = "Obra no encontrada";
    header('Location: galeria.php');
    exit;
}

// Obtener obras relacionadas SOLO si tenemos artista_id
$obras_relacionadas = [];
if (isset($obra['artista_id']) && $obra['artista_id'] > 0) {
    $obras_relacionadas = $db->obtenerObras(4, 1, null, $obra['artista_id']);
    $obras_relacionadas = array_filter($obras_relacionadas, function($obra_rel) use ($obra_id) {
        return $obra_rel['id'] != $obra_id;
    });
}

// Procesar agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito']) && validateCSRF($_POST['csrf_token'] ?? '')) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Debes iniciar sesión para agregar obras al carrito";
        header('Location: login.php');
        exit;
    }
    
    $cantidad = intval($_POST['cantidad'] ?? 1);
    
    if ($db->agregarAlCarrito($_SESSION['user_id'], $obra_id, $cantidad)) {
        $_SESSION['success'] = "Obra agregada al carrito exitosamente";
        header('Location: carrito.php');
        exit;
    } else {
        $_SESSION['error'] = "Error al agregar la obra al carrito";
    }
}

// NOTA: incrementarVistas() se llama automáticamente en obtenerObraCompleta()
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($obra['titulo']) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --azul-oscuro: #1C242A;
            --gris-claro: #D5D7DD;
            --beige: #C2B39E;
            --naranja-principal: #E88E33;
            --naranja-oscuro: #B34614;
        }
        
        .obra-container { 
            margin-top: 100px; 
            padding: 2rem 0; 
            background: var(--gris-claro);
            min-height: 100vh;
        }
        
        .obra-content { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 3rem; 
            margin-bottom: 3rem;
        }
        
        .obra-imagen-container {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .obra-imagen { 
            width: 100%; 
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .obra-info { 
            background: white; 
            padding: 2rem; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .obra-precio { 
            font-size: 2.5rem; 
            color: var(--naranja-principal); 
            font-weight: bold; 
            margin: 1rem 0; 
        }
        
        .btn-comprar { 
            width: 100%; 
            padding: 1rem; 
            background: var(--naranja-principal); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-comprar:hover {
            background: var(--naranja-oscuro);
            transform: translateY(-2px);
        }
        
        .obra-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        
        .obra-details p {
            margin: 0.5rem 0;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .obra-details p:last-child {
            border-bottom: none;
        }
        
        .artista-link {
            color: var(--naranja-principal);
            text-decoration: none;
            font-weight: bold;
        }
        
        .artista-link:hover {
            color: var(--naranja-oscuro);
        }
        
        .obras-relacionadas {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 2px solid var(--beige);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .obra-content { 
                grid-template-columns: 1fr; 
            }
            
            .obra-container {
                margin-top: 80px;
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container obra-container">
        <!-- Mensajes -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="obra-content">
            <!-- Imagen de la obra -->
            <div class="obra-imagen-container">
                <img src="<?= htmlspecialchars($obra['imagen'] ?? 'images/obra-default.jpg') ?>" 
                     alt="<?= htmlspecialchars($obra['titulo']) ?>" 
                     class="obra-imagen"
                     onerror="this.src='images/obra-default.jpg'">
                
                <?php if($obra['destacada'] ?? false): ?>
                    <div style="text-align: center; margin-top: 1rem;">
                        <span style="background: var(--naranja-principal); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                            ⭐ Obra Destacada
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Información de la obra -->
            <div class="obra-info">
                <h1 style="color: var(--azul-oscuro); margin-bottom: 0.5rem;"><?= htmlspecialchars($obra['titulo']) ?></h1>
                
                <p class="artista" style="font-size: 1.1rem; color: #666; margin-bottom: 1.5rem;">
                    <i class="fas fa-palette"></i> Por: 
                    <?php if (isset($obra['artista_id']) && $obra['artista_id'] > 0): ?>
                        <a href="artista_perfil.php?id=<?= $obra['artista_id'] ?>" class="artista-link">
                            <?= htmlspecialchars($obra['artista_nombre'] ?? 'Artista IFABAO') ?>
                        </a>
                    <?php else: ?>
                        <span class="artista-link"><?= htmlspecialchars($obra['artista_nombre'] ?? 'Artista IFABAO') ?></span>
                    <?php endif; ?>
                </p>
                
                <div class="obra-precio">Bs. <?= number_format($obra['precio'], 2) ?></div>
                
                <div class="obra-details">
                    <p><strong><i class="fas fa-brush"></i> Técnica:</strong> <?= htmlspecialchars($obra['tecnica'] ?? 'No especificada') ?></p>
                    <p><strong><i class="fas fa-ruler-combined"></i> Dimensiones:</strong> <?= htmlspecialchars($obra['dimensiones'] ?? 'No especificadas') ?></p>
                    <p><strong><i class="fas fa-calendar"></i> Año de creación:</strong> <?= htmlspecialchars($obra['ano_creacion'] ?? 'No especificado') ?></p>
                    <p><strong><i class="fas fa-tags"></i> Categorías:</strong> <?= htmlspecialchars($obra['categorias'] ?? 'Sin categorías') ?></p>
                    <p><strong><i class="fas fa-eye"></i> Vistas:</strong> <?= $obra['vistas'] ?? 0 ?></p>
                    <p><strong><i class="fas fa-info-circle"></i> Estado:</strong> 
                        <span style="color: <?= ($obra['estado'] ?? '') === 'disponible' ? '#28a745' : '#dc3545' ?>; font-weight: bold;">
                            <?= ucfirst($obra['estado'] ?? 'desconocido') ?>
                        </span>
                    </p>
                </div>
                
                <div class="obra-descripcion" style="margin: 2rem 0;">
                    <h3 style="color: var(--azul-oscuro); margin-bottom: 1rem;">
                        <i class="fas fa-align-left"></i> Descripción
                    </h3>
                    <p style="line-height: 1.6; color: #555;"><?= nl2br(htmlspecialchars($obra['descripcion'] ?? 'Sin descripción disponible')) ?></p>
                </div>
                
                <?php if (($obra['estado'] ?? '') === 'disponible'): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div style="margin: 1.5rem 0; display: flex; align-items: center; gap: 1rem;">
                        <label for="cantidad" style="font-weight: bold; color: var(--azul-oscuro);">
                            <i class="fas fa-shopping-cart"></i> Cantidad:
                        </label>
                        <select name="cantidad" id="cantidad" style="padding: 0.8rem; border: 2px solid var(--gris-claro); border-radius: 5px; background: white;">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" name="agregar_carrito" class="btn-comprar">
                        <i class="fas fa-cart-plus"></i> Agregar al Carrito
                    </button>
                </form>
                <?php else: ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1.5rem; border-radius: 10px; text-align: center;">
                    <i class="fas fa-times-circle"></i> 
                    <strong>Esta obra no está disponible actualmente</strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Obras relacionadas -->
        <?php if (!empty($obras_relacionadas) && isset($obra['artista_id']) && $obra['artista_id'] > 0): ?>
        <div class="obras-relacionadas">
            <h2 style="color: var(--azul-oscuro); margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-images"></i> Más obras de <?= htmlspecialchars($obra['artista_nombre'] ?? 'este artista') ?>
            </h2>
            <div class="art-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <?php foreach($obras_relacionadas as $obra_rel): ?>
                <div class="art-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                    <div class="art-image" style="position: relative; padding-bottom: 75%; overflow: hidden;">
                        <img src="<?= htmlspecialchars($obra_rel['imagen'] ?? 'images/obra-default.jpg') ?>" 
                             alt="<?= htmlspecialchars($obra_rel['titulo']) ?>"
                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.src='images/obra-default.jpg'">
                    </div>
                    <div class="art-content" style="padding: 1.5rem;">
                        <h3 class="art-title" style="color: var(--azul-oscuro); margin-bottom: 0.5rem; font-size: 1.1rem;">
                            <?= htmlspecialchars($obra_rel['titulo']) ?>
                        </h3>
                        <div class="art-price" style="color: var(--naranja-principal); font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            Bs. <?= number_format($obra_rel['precio'], 2) ?>
                        </div>
                        <a href="obra.php?id=<?= $obra_rel['id'] ?>" 
                           style="display: block; text-align: center; padding: 0.8rem; background: var(--gris-claro); color: var(--azul-oscuro); text-decoration: none; border-radius: 5px; transition: all 0.3s ease;">
                            Ver Detalles
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Navegación adicional -->
        <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--beige);">
            <a href="galeria.php" style="display: inline-block; margin: 0 0.5rem; padding: 1rem 2rem; background: var(--gris-claro); color: var(--azul-oscuro); text-decoration: none; border-radius: 5px;">
                <i class="fas fa-arrow-left"></i> Volver a la Galería
            </a>
            <?php if (isset($obra['artista_id']) && $obra['artista_id'] > 0): ?>
            <a href="artista_perfil.php?id=<?= $obra['artista_id'] ?>" style="display: inline-block; margin: 0 0.5rem; padding: 1rem 2rem; background: var(--naranja-principal); color: white; text-decoration: none; border-radius: 5px;">
                <i class="fas fa-user"></i> Ver Perfil del Artista
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Efectos de interactividad
    document.addEventListener('DOMContentLoaded', function() {
        // Animación suave al cargar
        const elementos = document.querySelectorAll('.art-card');
        elementos.forEach((elemento, index) => {
            elemento.style.opacity = '0';
            elemento.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                elemento.style.transition = 'all 0.5s ease';
                elemento.style.opacity = '1';
                elemento.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Efecto hover en tarjetas
        const cards = document.querySelectorAll('.art-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    </script>
</body>
</html>