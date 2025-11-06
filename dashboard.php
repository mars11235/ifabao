<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    redireccionarConMensaje('login.php', 'Debes iniciar sesión para acceder al dashboard', 'error');
}

$db = new Database();
$success = '';
$error = '';

// Obtener información del artista CORREGIDA
$artista = null;
$tiene_perfil_artista = false;

if (usuarioEsArtista()) {
    $tiene_perfil_artista = $db->verificarPerfilArtista($_SESSION['user_id']);
    
    if ($tiene_perfil_artista) {
        $artista = $db->obtenerArtistaPorUsuarioId($_SESSION['user_id']);
    }
}

// Mostrar mensajes flash
$mensajeFlash = mostrarMensajeFlash();
if ($mensajeFlash) {
    $success = $mensajeFlash;
}

// Procesar creación de perfil de artista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_perfil_artista']) && validateCSRF($_POST['csrf_token'] ?? '')) {
    $nombre_artistico = sanitizarEntrada($_POST['nombre_artistico'] ?? '');
    $biografia = sanitizarEntrada($_POST['biografia'] ?? '');
    $tecnica_principal = sanitizarEntrada($_POST['tecnica_principal'] ?? '');
    
    // Validaciones
    $errores = [];
    if (strlen($nombre_artistico) < 2) $errores[] = "Nombre artístico debe tener al menos 2 caracteres";
    if (strlen($biografia) < 10) $errores[] = "Biografía debe tener al menos 10 caracteres";
    if (empty($tecnica_principal)) $errores[] = "Selecciona una técnica principal";
    
    if (empty($errores)) {
        $datos_artista = [
            'nombre_artistico' => $nombre_artistico,
            'biografia' => $biografia,
            'tecnica_principal' => $tecnica_principal
        ];
        
        $artista_id = $db->crearPerfilArtista($_SESSION['user_id'], $datos_artista);
        
        if ($artista_id) {
            $success = "¡Perfil de artista creado exitosamente!";
            $tiene_perfil_artista = true;
            $artista = $db->obtenerArtistaPorUsuarioId($_SESSION['user_id']);
            $_POST = [];
        } else {
            $error = "Error al crear el perfil de artista";
        }
    } else {
        $error = implode("<br>", $errores);
    }
}

// Obtener obras solo si es artista con perfil
$misObras = [];
$categorias = [];

if ($tiene_perfil_artista && $artista) {
    $misObras = $db->obtenerObrasPorArtista($artista['id'], false);
    $categorias = $db->obtenerCategorias();
} else {
    // Modo demo - crear artista temporal
    $artista = [
        'id' => 1,
        'nombre_artistico' => $_SESSION['user_name'] . ' Artista',
        'biografia' => 'Artista talentoso de la comunidad IFABAO',
        'total_obras' => 0
    ];
}

$misObras = $artista ? ($db->verificarConexion() && method_exists($db, 'obtenerObrasPorArtista') ? 
    $db->obtenerObrasPorArtista($artista['id'], false) : []) : [];
$categorias = $db->obtenerCategorias();

// Procesar nueva obra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_obra']) && validateCSRF($_POST['csrf_token'] ?? '')) {
    if (!$artista) {
        $error = "No tienes perfil de artista configurado";
    } else {
        $titulo = sanitizarEntrada($_POST['titulo'] ?? '');
        $descripcion = sanitizarEntrada($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $tecnica = sanitizarEntrada($_POST['tecnica'] ?? '');
        $dimensiones = sanitizarEntrada($_POST['dimensiones'] ?? '');
        $ano_creacion = intval($_POST['ano_creacion'] ?? date('Y'));
        
        // Validaciones básicas
        $errores = [];
        if (strlen($titulo) < 3) $errores[] = "Título debe tener al menos 3 caracteres";
        if (strlen($descripcion) < 10) $errores[] = "Descripción debe tener al menos 10 caracteres";
        if ($precio <= 0 || $precio > 100000) $errores[] = "Precio debe ser entre 0 y 100,000 Bs";
        if ($categoria_id <= 0) $errores[] = "Selecciona una categoría válida";
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) $errores[] = "Selecciona una imagen válida";
        
        if (empty($errores)) {
            $resultado_imagen = subirImagen($_FILES['imagen']);
            
            if (isset($resultado_imagen['success'])) {
                $obra_id = $db->agregarObra($artista['id'], [
                    'categoria_id' => $categoria_id,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'tecnica' => $tecnica,
                    'dimensiones' => $dimensiones,
                    'ano_creacion' => $ano_creacion
                ], $resultado_imagen['success']);
                
                if ($obra_id) {
                    $success = "¡Obra publicada! En revisión por administrador.";
                    $misObras = $db->obtenerObrasPorArtista($artista['id'], false);
                    $_POST = [];
                } else {
                    $error = "Error al guardar la obra";
                    if (file_exists($resultado_imagen['success'])) {
                        unlink($resultado_imagen['success']);
                    }
                }
            } else {
                $error = $resultado_imagen['error'] ?? "Error al procesar imagen";
            }
        } else {
            $error = implode("<br>", $errores);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard { margin-top: 80px; padding: 2rem 0; }
        .dashboard-header { background: linear-gradient(135deg, #8B4513, #D2691E); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 10px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #8B4513; }
        .tab-container { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .tabs { display: flex; background: #f5f5f5; border-bottom: 1px solid #ddd; }
        .tab { padding: 1rem 2rem; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab.active { border-bottom-color: #8B4513; background: white; font-weight: bold; }
        .tab:hover { background: #e9e9e9; }
        .tab-content { padding: 2rem; display: none; }
        .tab-content.active { display: block; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .obra-card { border: 1px solid #eee; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; display: flex; gap: 1rem; align-items: start; }
        .obra-imagen { width: 100px; height: 80px; object-fit: cover; border-radius: 5px; flex-shrink: 0; }
        .estado-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .estado-revision { background: #fff3cd; color: #856404; } .estado-disponible { background: #d4edda; color: #155724; }
        .preview-imagen { max-width: 200px; max-height: 150px; margin-top: 0.5rem; border-radius: 5px; display: none; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; } .alert-error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .obra-card { flex-direction: column; }
            .obra-imagen { width: 100%; height: 120px; }
            .tabs { flex-direction: column; }
            .tab { padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1>¡Hola, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h1>
                <p>Bienvenido a tu panel de artista</p>
            </div>
        </div>
        
        <div class="container">
            <?php if ($artista): ?>
            <!-- Estadísticas -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?= count($misObras) ?></div>
                    <div>Total de Obras</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count(array_filter($misObras, fn($obra) => $obra['estado'] === 'disponible')) ?>
                    </div>
                    <div>Obras Disponibles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        Bs. <?= number_format(array_sum(array_map(fn($obra) => $obra['estado'] === 'vendida' ? $obra['precio'] : 0, $misObras)), 0) ?>
                    </div>
                    <div>Ingresos Totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count(array_filter($misObras, fn($obra) => $obra['estado'] === 'revision')) ?>
                    </div>
                    <div>En Revisión</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Sistema de pestañas -->
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="openTab('obras')">Mis Obras</div>
                    <?php if ($artista): ?>
                    <div class="tab" onclick="openTab('agregar')">Agregar Obra</div>
                    <?php endif; ?>
                    <div class="tab" onclick="openTab('perfil')">Mi Perfil</div>
                </div>
                
                <!-- Pestaña: Mis Obras -->
                <div id="obras" class="tab-content active">
                    <h3>Mis Obras Publicadas</h3>
                    
                    <?php if(!empty($misObras)): ?>
                        <?php foreach($misObras as $obra): ?>
                        <div class="obra-card">
                            <img src="<?= htmlspecialchars($obra['imagen'] ?? 'images/obra-default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($obra['titulo']) ?>" 
                                 class="obra-imagen"
                                 onerror="this.src='images/obra-default.jpg'">
                            
                            <div style="flex: 1;">
                                <h4><?= htmlspecialchars($obra['titulo']) ?></h4>
                                <p><strong>Precio:</strong> Bs. <?= number_format($obra['precio'], 2) ?></p>
                                <p><strong>Categoría:</strong> <?= htmlspecialchars($obra['categorias'] ?? 'Sin categoría') ?></p>
                                
                                <span class="estado-badge estado-<?= $obra['estado'] ?>">
                                    <?= match($obra['estado']) {
                                        'revision' => '📝 En Revisión',
                                        'disponible' => '✅ Disponible',
                                        'vendida' => '💰 Vendida',
                                        default => $obra['estado']
                                    } ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: #666;">
                            <h4>No tienes obras publicadas</h4>
                            <p><?= $artista ? 'Agrega tu primera obra' : 'Configura tu perfil de artista' ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($artista): ?>
                <!-- Pestaña: Agregar Obra -->
                <div id="agregar" class="tab-content">
                    <h3>Agregar Nueva Obra</h3>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="form-agregar-obra">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="form-group">
                            <label>Título de la Obra *</label>
                            <input type="text" name="titulo" required placeholder="Ej: Paisaje Andino"
                                   value="<?= isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : '' ?>"
                                   minlength="3" maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción *</label>
                            <textarea name="descripcion" rows="4" required placeholder="Describe tu obra..."
                                      minlength="10" maxlength="1000"><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Precio (Bs.) *</label>
                                <input type="number" name="precio" step="0.01" min="0.01" max="100000" required 
                                       placeholder="1500.00" 
                                       value="<?= isset($_POST['precio']) ? htmlspecialchars($_POST['precio']) : '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Categoría *</label>
                                <select name="categoria_id" required>
                                    <option value="">Selecciona categoría</option>
                                    <?php foreach($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Técnica utilizada</label>
                                <input type="text" name="tecnica" placeholder="Ej: Óleo sobre lienzo"
                                       value="<?= isset($_POST['tecnica']) ? htmlspecialchars($_POST['tecnica']) : '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Dimensiones</label>
                                <input type="text" name="dimensiones" placeholder="Ej: 50x70 cm"
                                       value="<?= isset($_POST['dimensiones']) ? htmlspecialchars($_POST['dimensiones']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Año de creación</label>
                            <input type="number" name="ano_creacion" min="1900" max="<?= date('Y') ?>" 
                                   value="<?= isset($_POST['ano_creacion']) ? htmlspecialchars($_POST['ano_creacion']) : date('Y') ?>">
                        </div>

                        <div class="form-group">
                            <label>Imagen de la Obra *</label>
                            <input type="file" name="imagen" accept="image/*" required onchange="previewImage(this)">
                            <img id="preview" class="preview-imagen" src="" alt="Vista previa">
                        </div>
                        
                        <button type="submit" name="agregar_obra" style="width: 100%; padding: 1rem; background: #4CAF50; color: white; border: none; border-radius: 5px;">
                            🎨 Publicar Obra
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Pestaña: Mi Perfil -->
                <div id="perfil" class="tab-content">
                    <h3>Mi Perfil</h3>
                    <div style="background: #f9f9f9; padding: 2rem; border-radius: 5px;">
                        <div class="form-row">
                            <div>
                                <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['user_email'] ?? 'No disponible') ?></p>
                                <p><strong>Tipo de cuenta:</strong> <?= htmlspecialchars($_SESSION['user_type']) ?></p>
                            </div>
                            <div>
                                <p><strong>ID de Usuario:</strong> <?= $_SESSION['user_id'] ?></p>
                                <p><strong>Último acceso:</strong> <?= date('d/m/Y H:i') ?></p>
                            </div>
                        </div>
                        
                        <?php if ($artista): ?>
                            <hr style="margin: 1.5rem 0;">
                            <h4>Información de Artista</h4>
                            <p><strong>Total de obras:</strong> <?= count($misObras) ?></p>
                            <p><strong>Obras disponibles:</strong> <?= count(array_filter($misObras, fn($obra) => $obra['estado'] === 'disponible')) ?></p>
                        <?php else: ?>
                            <div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-radius: 5px;">
                                <p>Configura tu perfil de artista para publicar obras</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    
    function previewImage(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            if (input.files[0].size > 5 * 1024 * 1024) {
                alert('La imagen es demasiado grande. Máximo 5MB permitido.');
                input.value = '';
                preview.style.display = 'none';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }
    
    // Validación del formulario
    document.getElementById('form-agregar-obra')?.addEventListener('submit', function(e) {
        const titulo = document.querySelector('input[name="titulo"]').value.trim();
        const descripcion = document.querySelector('textarea[name="descripcion"]').value.trim();
        const precio = document.querySelector('input[name="precio"]').value;
        const categoria = document.querySelector('select[name="categoria_id"]').value;
        const imagen = document.querySelector('input[name="imagen"]').files[0];
        
        if (titulo.length < 3) {
            alert('El título debe tener al menos 3 caracteres');
            e.preventDefault();
            return;
        }
        
        if (descripcion.length < 10) {
            alert('La descripción debe tener al menos 10 caracteres');
            e.preventDefault();
            return;
        }
        
        if (precio <= 0 || precio > 100000) {
            alert('El precio debe ser mayor a 0 y menor a 100,000 Bs.');
            e.preventDefault();
            return;
        }
        
        if (!categoria) {
            alert('Debes seleccionar una categoría');
            e.preventDefault();
            return;
        }
        
        if (!imagen) {
            alert('Debes seleccionar una imagen para la obra');
            e.preventDefault();
            return;
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>