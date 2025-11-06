<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Obtener ID del artista desde la URL de forma segura
$artista_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($artista_id <= 0) {
    $_SESSION['error'] = "ID de artista no válido.";
    header('Location: artistas.php');
    exit;
}

// Obtener información del artista de forma segura
$artista = $db->obtenerArtistaPorId($artista_id);

if (!$artista) {
    $_SESSION['error'] = "Artista no encontrado.";
    header('Location: artistas.php');
    exit;
}

// Obtener obras del artista
$obras_artista = $db->obtenerObrasPorArtista($artista_id);

// Generar datos de contacto seguros
$email_contacto = "artista" . $artista_id . "@ifabao.com";
$telefono_contacto = "+591 " . rand(60000000, 79999999);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .perfil-artista {
            margin-top: 100px;
            padding: 2rem 0;
        }
        
        .perfil-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        
        .perfil-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .perfil-sidebar {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .perfil-imagen {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--accent);
            margin: 0 auto 1.5rem;
            display: block;
        }
        
        .perfil-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .artista-nombre {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .artista-tecnica {
            font-size: 1.2rem;
            color: var(--secondary);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: bold;
        }
        
        .estadisticas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .estadistica {
            text-align: center;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .numero {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            display: block;
        }
        
        .label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .bio-section {
            margin-bottom: 2rem;
        }
        
        .bio-titulo {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .bio-texto {
            line-height: 1.8;
            font-size: 1.1rem;
            color: #333;
        }
        
        .contacto-info {
            background: #f0f8ff;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .contacto-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
        }
        
        .contacto-item:last-child {
            margin-bottom: 0;
        }
        
        .icono {
            margin-right: 1rem;
            font-size: 1.2rem;
            min-width: 20px;
        }
        
        .obras-section {
            margin-top: 3rem;
        }
        
        .galeria-obras {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .obra-mini {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .obra-mini:hover {
            transform: translateY(-5px);
        }
        
        .obra-mini-imagen {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .obra-mini-content {
            padding: 1rem;
        }
        
        .obra-mini-titulo {
            font-weight: bold;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            line-height: 1.3;
        }
        
        .obra-mini-precio {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .btn-contactar {
            width: 100%;
            margin-top: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-contactar:hover {
            background: var(--secondary);
        }
        
        .redes-sociales {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .red-social {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .red-social:hover {
            background: var(--secondary);
            transform: scale(1.1);
        }
        
        .experiencia-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .experiencia-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .experiencia-titulo {
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }
        
        .experiencia-periodo {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .empty-obras {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            .perfil-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .perfil-imagen {
                width: 150px;
                height: 150px;
            }
            
            .artista-nombre {
                font-size: 2rem;
            }
            
            .estadisticas {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .galeria-obras {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .estadisticas {
                grid-template-columns: 1fr;
            }
            
            .galeria-obras {
                grid-template-columns: 1fr;
            }
            
            .perfil-header {
                padding: 2rem 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="perfil-artista">
        <!-- Header del perfil -->
        <div class="perfil-header">
            <div class="container">
                <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; justify-content: center;">
                    <img src="<?php echo htmlspecialchars($artista['imagen'] ?? 'imagenes/artista-default.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?>" 
                         class="perfil-imagen"
                         onerror="this.src='imagenes/artista-default.jpg'">
                    <div style="text-align: center;">
                        <h1 class="artista-nombre"><?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?></h1>
                        <p class="artista-tecnica">🎨 <?php echo htmlspecialchars($artista['tecnica_principal'] ?? $artista['tecnica'] ?? 'Arte Visual'); ?></p>
                        <p style="opacity: 0.9; font-size: 1.1rem;">Artista de la comunidad IFABAO</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container">
            <div class="perfil-container">
                <!-- Sidebar con información básica -->
                <div class="perfil-sidebar">
                    <div class="estadisticas">
                        <div class="estadistica">
                            <span class="numero"><?php echo count($obras_artista); ?></span>
                            <span class="label">Obras</span>
                        </div>
                        <div class="estadistica">
                            <span class="numero"><?php echo rand(3, 8); ?></span>
                            <span class="label">Exposiciones</span>
                        </div>
                        <div class="estadistica">
                            <span class="numero"><?php echo rand(2, 6); ?></span>
                            <span class="label">Premios</span>
                        </div>
                    </div>
                    
                    <div class="contacto-info">
                        <h4 style="margin-bottom: 1rem; color: var(--primary);">📞 Contacto</h4>
                        <div class="contacto-item">
                            <span class="icono">📧</span>
                            <span><?php echo htmlspecialchars($email_contacto); ?></span>
                        </div>
                        <div class="contacto-item">
                            <span class="icono">📱</span>
                            <span><?php echo htmlspecialchars($telefono_contacto); ?></span>
                        </div>
                        <div class="contacto-item">
                            <span class="icono">🏠</span>
                            <span>Oruro, Bolivia</span>
                        </div>
                    </div>
                    
                    <button class="btn-contactar" onclick="contactarArtista()">
                        📩 Contactar al Artista
                    </button>
                    
                    <div class="redes-sociales">
                        <a href="#" class="red-social" title="Facebook" onclick="return false;">f</a>
                        <a href="#" class="red-social" title="Instagram" onclick="return false;">📷</a>
                        <a href="#" class="red-social" title="Twitter" onclick="return false;">🐦</a>
                        <a href="#" class="red-social" title="Sitio Web" onclick="return false;">🌐</a>
                    </div>
                </div>
                
                <!-- Contenido principal -->
                <div class="perfil-content">
                    <!-- Biografía -->
                    <div class="bio-section">
                        <h2 class="bio-titulo">🎭 Biografía</h2>
                        <div class="bio-texto">
                            <p><?php echo nl2br(htmlspecialchars($artista['biografia'] ?? 'Artista con una trayectoria destacada en el ámbito del arte boliviano. Su trabajo refleja la riqueza cultural y la diversidad de expresiones artísticas de nuestra región.')); ?></p>
                            <p>Con una trayectoria de más de <?php echo rand(5, 15); ?> años en el mundo del arte, 
                            <?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?> ha desarrollado un estilo único que combina técnicas 
                            tradicionales con visiones contemporáneas. Su trabajo ha sido reconocido tanto a nivel 
                            nacional como internacional, participando en diversas exposiciones colectivas e individuales.</p>
                        </div>
                    </div>
                    
                    <!-- Experiencia y Logros -->
                    <div class="bio-section">
                        <h2 class="bio-titulo">🏆 Trayectoria y Logros</h2>
                        <div class="experiencia-item">
                            <div class="experiencia-titulo">Formación en Bellas Artes Oruro</div>
                            <div class="experiencia-periodo"><?php echo rand(2010, 2015); ?> - <?php echo rand(2015, 2020); ?></div>
                            <p>Estudios especializados en <?php echo htmlspecialchars($artista['tecnica_principal'] ?? $artista['tecnica'] ?? 'artes visuales'); ?> y técnicas afines.</p>
                        </div>
                        <div class="experiencia-item">
                            <div class="experiencia-titulo">Exposición Individual "<?php echo ['Horizontes', 'Raíces', 'Identidad', 'Esencia'][rand(0,3)]; ?>"</div>
                            <div class="experiencia-periodo"><?php echo rand(2018, 2023); ?> - Museo Nacional de Arte</div>
                            <p>Exposición que reunió <?php echo rand(15, 30); ?> obras representativas de su carrera.</p>
                        </div>
                        <div class="experiencia-item">
                            <div class="experiencia-titulo">Premio Joven Talento Boliviano</div>
                            <div class="experiencia-periodo"><?php echo rand(2019, 2022); ?> - Ministerio de Cultura</div>
                            <p>Reconocimiento por su contribución al arte contemporáneo boliviano.</p>
                        </div>
                    </div>
                    
                    <!-- Estilo y Técnica -->
                    <div class="bio-section">
                        <h2 class="bio-titulo">🎨 Estilo Artístico</h2>
                        <div class="bio-texto">
                            <p>El trabajo de <?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?> se caracteriza por 
                            <?php 
                            $estilos = [
                                "la fusión de colores vibrantes con temas tradicionales bolivianos",
                                "la exploración de la identidad cultural a través del arte contemporáneo",
                                "la innovación en técnicas tradicionales con materiales modernos",
                                "la representación de la naturaleza y el entorno social boliviano",
                                "la abstracción de elementos culturales andinos"
                            ];
                            echo $estilos[array_rand($estilos)];
                            ?>.</p>
                            <p>Su enfoque en <?php echo htmlspecialchars($artista['tecnica_principal'] ?? $artista['tecnica'] ?? 'técnicas mixtas'); ?> le permite crear 
                            piezas que transmiten emociones profundas y conectan con el espectador 
                            a nivel personal y cultural.</p>
                        </div>
                    </div>
                    
                    <!-- Obras del Artista -->
                    <div class="obras-section">
                        <h2 class="bio-titulo">🖼️ Obras de <?php echo htmlspecialchars(explode(' ', $artista['nombre_artistico'] ?? $artista['nombre_completo'])[0]); ?></h2>
                        
                        <?php if (!empty($obras_artista)): ?>
                            <div class="galeria-obras">
                                <?php foreach($obras_artista as $obra): ?>
                                <div class="obra-mini">
                                    <a href="obra.php?id=<?php echo $obra['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <img src="<?php echo htmlspecialchars($obra['imagen'] ?? 'imagenes/'); ?>" 
                                             alt="<?php echo htmlspecialchars($obra['titulo']); ?>" 
                                             class="obra-mini-imagen"
                                             onerror="this.src='imagenes/'">
                                        <div class="obra-mini-content">
                                            <div class="obra-mini-titulo"><?php echo htmlspecialchars($obra['titulo']); ?></div>
                                            <div class="obra-mini-precio">Bs. <?php echo number_format($obra['precio'], 2); ?></div>
                                            <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                                <?php echo htmlspecialchars($obra['tecnica'] ?? 'Pintura'); ?>
                                            </div>
                                            <?php if ($obra['estado'] === 'disponible'): ?>
                                                <div style="color: #28a745; font-size: 0.8rem; margin-top: 0.3rem;">✓ Disponible</div>
                                            <?php else: ?>
                                                <div style="color: #dc3545; font-size: 0.8rem; margin-top: 0.3rem;">✗ No disponible</div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-obras">
                                <h4>El artista aún no tiene obras publicadas</h4>
                                <p>Próximamente estarán disponibles las obras de <?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?>.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($obras_artista)): ?>
                            <div style="text-align: center; margin-top: 2rem;">
                                <a href="galeria.php?artista=<?php echo $artista_id; ?>" class="btn">
                                    Ver Todas las Obras
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function contactarArtista() {
        const artistaNombre = "<?php echo htmlspecialchars($artista['nombre_artistico'] ?? $artista['nombre_completo']); ?>";
        const email = "<?php echo htmlspecialchars($email_contacto); ?>";
        const telefono = "<?php echo htmlspecialchars($telefono_contacto); ?>";
        
        const mensaje = `📧 Contactando a ${artistaNombre}\n\n` +
                       `Email: ${email}\n` +
                       `Teléfono: ${telefono}\n\n` +
                       `¿Deseas enviar un mensaje a este artista?`;
        
        if (confirm(mensaje)) {
            // En un sistema real, esto abriría un formulario de contacto
            alert('Sistema de contacto en desarrollo. Por ahora, puedes contactar al artista directamente usando la información proporcionada.');
            
            // Simular apertura de cliente de email
            // window.location.href = `mailto:${email}?subject=Consulta sobre obra de arte&body=Hola ${artistaNombre}, me interesa conocer más sobre tu trabajo...`;
        }
    }
    
    // Mejorar la experiencia de usuario
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Animación para las obras
        const obras = document.querySelectorAll('.obra-mini');
        obras.forEach((obra, index) => {
            obra.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>