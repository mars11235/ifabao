<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$usuario_id = $_SESSION['user_id'];

// Obtener información del usuario
$usuario_info = [
    'nombre' => $_SESSION['user_name'],
    'email' => $_SESSION['user_email'] ?? 'No disponible',
    'tipo' => $_SESSION['user_type'],
    'fecha_registro' => date('Y-m-d H:i:s', $_SESSION['login_time'] ?? time())
];

// Simular datos de pedidos (en sistema real, vendrían de la BD)
$pedidos = [
    [
        'id' => 'PED-2024-001',
        'fecha' => '2024-01-15',
        'total' => 2550.00,
        'estado' => 'completado',
        'items' => 2
    ],
    [
        'id' => 'PED-2024-002', 
        'fecha' => '2024-01-20',
        'total' => 1800.00,
        'estado' => 'en_proceso',
        'items' => 1
    ]
];

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil']) && validateCSRF($_POST['csrf_token'] ?? '')) {
    $nombre = sanitizarEntrada($_POST['nombre'] ?? '');
    $telefono = sanitizarEntrada($_POST['telefono'] ?? '');
    
    if (strlen($nombre) < 2) {
        $_SESSION['error'] = "El nombre debe tener al menos 2 caracteres";
    } else {
        // En sistema real, actualizar en BD
        $_SESSION['user_name'] = $nombre;
        $_SESSION['success'] = "Perfil actualizado correctamente";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .perfil-container { margin-top: 100px; padding: 2rem 0; background: #f8f9fa; min-height: 100vh; }
        .perfil-header { background: linear-gradient(135deg, #8B4513, #D2691E); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .perfil-grid { display: grid; grid-template-columns: 300px 1fr; gap: 2rem; }
        .perfil-sidebar { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .perfil-avatar { width: 120px; height: 120px; border-radius: 50%; background: #8B4513; color: white; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 1rem; }
        .pedido-card { background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 1rem; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        .estado-completado { background: #d4edda; color: #155724; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem; }
        .estado-proceso { background: #fff3cd; color: #856404; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem; }
        
        @media (max-width: 768px) {
            .perfil-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="perfil-container">
        <div class="perfil-header">
            <div class="container">
                <h1>Mi Perfil</h1>
                <p>Gestiona tu información y revisa tu actividad</p>
            </div>
        </div>
        
        <div class="container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="perfil-grid">
                <!-- Sidebar -->
                <div class="perfil-sidebar">
                    <div class="perfil-avatar">
                        <?= strtoupper(substr($usuario_info['nombre'], 0, 1)) ?>
                    </div>
                    <h3 style="text-align: center;"><?= htmlspecialchars($usuario_info['nombre']) ?></h3>
                    <p style="text-align: center; color: #666; margin-bottom: 2rem;"><?= ucfirst($usuario_info['tipo']) ?></p>
                    
                    <nav style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="#info" style="padding: 1rem; background: #f8f9fa; border-radius: 5px; text-decoration: none; color: #333;">📋 Información Personal</a>
                        <a href="#pedidos" style="padding: 1rem; background: #f8f9fa; border-radius: 5px; text-decoration: none; color: #333;">📦 Mis Pedidos</a>
                        <a href="#seguridad" style="padding: 1rem; background: #f8f9fa; border-radius: 5px; text-decoration: none; color: #333;">🔒 Seguridad</a>
                    </nav>
                </div>
                
                <!-- Contenido principal -->
                <div>
                    <!-- Información Personal -->
                    <div id="info" style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
                        <h3>Información Personal</h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.5rem 0;">
                                <div>
                                    <label>Nombre Completo</label>
                                    <input type="text" name="nombre" value="<?= htmlspecialchars($usuario_info['nombre']) ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px;">
                                </div>
                                <div>
                                    <label>Email</label>
                                    <input type="email" value="<?= htmlspecialchars($usuario_info['email']) ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; background: #f5f5f5;" readonly>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.5rem 0;">
                                <div>
                                    <label>Teléfono</label>
                                    <input type="tel" name="telefono" placeholder="+591 XXX XXX" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px;">
                                </div>
                                <div>
                                    <label>Tipo de Cuenta</label>
                                    <input type="text" value="<?= ucfirst($usuario_info['tipo']) ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; background: #f5f5f5;" readonly>
                                </div>
                            </div>
                            
                            <button type="submit" name="actualizar_perfil" style="padding: 1rem 2rem; background: #8B4513; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                💾 Guardar Cambios
                            </button>
                        </form>
                    </div>
                    
                    <!-- Mis Pedidos -->
                    <div id="pedidos" style="background: white; padding: 2rem; border-radius: 10px;">
                        <h3>Mis Pedidos Recientes</h3>
                        
                        <?php if (!empty($pedidos)): ?>
                            <?php foreach($pedidos as $pedido): ?>
                            <div class="pedido-card">
                                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
                                    <div>
                                        <h4 style="margin: 0;">Pedido #<?= $pedido['id'] ?></h4>
                                        <p style="margin: 0; color: #666;">Fecha: <?= $pedido['fecha'] ?></p>
                                    </div>
                                    <span class="<?= $pedido['estado'] === 'completado' ? 'estado-completado' : 'estado-proceso' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; justify-content: between; align-items: center;">
                                    <div>
                                        <p style="margin: 0;"><strong>Total:</strong> Bs. <?= number_format($pedido['total'], 2) ?></p>
                                        <p style="margin: 0;"><strong>Items:</strong> <?= $pedido['items'] ?> obra(s)</p>
                                    </div>
                                    <a href="mis_pedidos.php?pedido=<?= $pedido['id'] ?>" style="padding: 0.5rem 1rem; background: #8B4513; color: white; text-decoration: none; border-radius: 5px;">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div style="text-align: center; margin-top: 2rem;">
                                <a href="mis_pedidos.php" style="padding: 1rem 2rem; background: #666; color: white; text-decoration: none; border-radius: 5px;">
                                    Ver Todos mis Pedidos
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 3rem; color: #666;">
                                <h4>No tienes pedidos aún</h4>
                                <p>¡Descubre obras increíbles en nuestra galería!</p>
                                <a href="galeria.php" style="display: inline-block; margin-top: 1rem; padding: 1rem 2rem; background: #8B4513; color: white; text-decoration: none; border-radius: 5px;">
                                    Explorar Galería
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>