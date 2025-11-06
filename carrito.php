<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$carrito = $_SESSION['carrito'];

// Procesar acciones del carrito
if (isset($_GET['agregar']) && validateCSRF($_GET['csrf_token'] ?? '')) {
    $obra_id = intval($_GET['agregar']);
    
    if ($db->verificarDisponibilidadObra($obra_id)) {
        $obra = $db->obtenerObraPorId($obra_id);
        
        if ($obra && $obra['estado'] === 'disponible') {
            if (isset($carrito[$obra_id])) {
                if ($carrito[$obra_id]['cantidad'] < 10) {
                    $carrito[$obra_id]['cantidad']++;
                } else {
                    $_SESSION['error_carrito'] = "Límite máximo de 10 unidades por obra";
                }
            } else {
                $carrito[$obra_id] = [
                    'id' => $obra['id'],
                    'titulo' => $obra['titulo'],
                    'precio' => $obra['precio'],
                    'imagen' => $obra['imagen'] ?? 'images/obra-default.jpg',
                    'cantidad' => 1,
                    'artista' => $obra['artista_nombre']
                ];
            }
            
            $_SESSION['carrito'] = $carrito;
            $_SESSION['success_carrito'] = "Obra agregada al carrito";
        } else {
            $_SESSION['error_carrito'] = "La obra no está disponible";
        }
    } else {
        $_SESSION['error_carrito'] = "La obra ya no está disponible";
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'carrito.php'));
    exit();
}

// Eliminar obra del carrito
if (isset($_GET['eliminar']) && validateCSRF($_GET['csrf_token'] ?? '')) {
    $obra_id = intval($_GET['eliminar']);
    
    if (isset($carrito[$obra_id])) {
        unset($carrito[$obra_id]);
        $_SESSION['carrito'] = $carrito;
        $_SESSION['success_carrito'] = "Obra eliminada del carrito";
    }
    
    header('Location: carrito.php');
    exit();
}

// Actualizar cantidades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_carrito']) && validateCSRF($_POST['csrf_token'] ?? '')) {
    foreach ($_POST['cantidades'] as $obra_id => $cantidad) {
        $obra_id = intval($obra_id);
        $cantidad = intval($cantidad);
        
        if (isset($carrito[$obra_id])) {
            if ($cantidad > 0 && $cantidad <= 10) {
                $carrito[$obra_id]['cantidad'] = $cantidad;
            } else {
                unset($carrito[$obra_id]);
            }
        }
    }
    
    $_SESSION['carrito'] = $carrito;
    $_SESSION['success_carrito'] = "Carrito actualizado";
    header('Location: carrito.php');
    exit();
}

// Vaciar carrito
if (isset($_GET['vaciar']) && validateCSRF($_GET['csrf_token'] ?? '')) {
    $_SESSION['carrito'] = [];
    $carrito = [];
    $_SESSION['success_carrito'] = "Carrito vaciado";
    header('Location: carrito.php');
    exit();
}

// Calcular totales
$subtotal = 0;
$envio = 50;
$total_items = 0;

foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
    $total_items += $item['cantidad'];
}
$total = $subtotal + $envio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .carrito-container { max-width: 1000px; margin: 100px auto; padding: 0 20px; }
        .carrito-header { text-align: center; margin-bottom: 2rem; }
        .carrito-item {
            display: flex; align-items: center; background: white; padding: 1.5rem;
            margin-bottom: 1rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            gap: 1.5rem;
        }
        .carrito-imagen { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; }
        .carrito-info { flex: 1; }
        .carrito-precio { font-size: 1.2rem; font-weight: bold; color: #8B4513; margin: 0 1rem; }
        .cantidad-input { width: 70px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .carrito-total { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem; }
        .total-line { display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid #eee; }
        .total-final { font-size: 1.5rem; font-weight: bold; border-bottom: none; margin-top: 1rem; }
        .carrito-vacio { text-align: center; padding: 4rem 2rem; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .carrito-actions { display: flex; gap: 1rem; justify-content: center; margin: 2rem 0; flex-wrap: wrap; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .carrito-item { flex-direction: column; text-align: center; gap: 1rem; }
            .carrito-precio { margin: 0.5rem 0; }
            .carrito-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="carrito-container">
        <div class="carrito-header">
            <h1>🛒 Carrito de Compras</h1>
            <p>Gestiona tus obras seleccionadas</p>
        </div>
        
        <!-- Mensajes -->
        <?php if (isset($_SESSION['success_carrito'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_carrito'] ?></div>
            <?php unset($_SESSION['success_carrito']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_carrito'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error_carrito'] ?></div>
            <?php unset($_SESSION['error_carrito']); ?>
        <?php endif; ?>
        
        <?php if (empty($carrito)): ?>
            <div class="carrito-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>¡Descubre obras increíbles en nuestro catálogo!</p>
                <a href="galeria.php" style="display: inline-block; margin-top: 1rem; padding: 1rem 2rem; background: #8B4513; color: white; text-decoration: none; border-radius: 5px;">
                    Ver Catálogo
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="form-carrito">
                <input type="hidden" name="actualizar_carrito" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <?php foreach ($carrito as $item): ?>
                <div class="carrito-item">
                    <img src="<?= htmlspecialchars($item['imagen']) ?>" 
                         alt="<?= htmlspecialchars($item['titulo']) ?>" 
                         class="carrito-imagen"
                         onerror="this.src='images/obra-default.jpg'">
                    
                    <div class="carrito-info">
                        <h3><?= htmlspecialchars($item['titulo']) ?></h3>
                        <p><strong>Artista:</strong> <?= htmlspecialchars($item['artista']) ?></p>
                        <p><strong>Precio unitario:</strong> Bs. <?= number_format($item['precio'], 2) ?></p>
                    </div>
                    
                    <span class="carrito-precio">
                        Bs. <?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                    </span>
                    
                    <input type="number" 
                           name="cantidades[<?= $item['id'] ?>]" 
                           value="<?= $item['cantidad'] ?>" 
                           min="1" 
                           max="10"
                           class="cantidad-input">
                    
                    <a href="carrito.php?eliminar=<?= $item['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                       style="background: #e74c3c; color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none;"
                       onclick="return confirm('¿Eliminar esta obra del carrito?')">
                        ❌
                    </a>
                </div>
                <?php endforeach; ?>
                
                <div class="carrito-actions">
                    <button type="submit" style="padding: 1rem 2rem; background: #8B4513; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        🔄 Actualizar
                    </button>
                    <a href="carrito.php?vaciar=1&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                       style="padding: 1rem 2rem; background: #95a5a6; color: white; text-decoration: none; border-radius: 5px;"
                       onclick="return confirm('¿Vaciar todo el carrito?')">
                        🗑️ Vaciar
                    </a>
                    <a href="galeria.php" style="padding: 1rem 2rem; background: #666; color: white; text-decoration: none; border-radius: 5px;">
                        ← Seguir Comprando
                    </a>
                </div>
            </form>
            
            <div class="carrito-total">
                <h2 style="text-align: center; margin-bottom: 1.5rem;">Resumen del Pedido</h2>
                
                <div class="total-line">
                    <span>Subtotal (<?= $total_items ?> obras):</span>
                    <span>Bs. <?= number_format($subtotal, 2) ?></span>
                </div>
                
                <div class="total-line">
                    <span>Envío:</span>
                    <span>Bs. <?= number_format($envio, 2) ?></span>
                </div>
                
                <div class="total-line total-final">
                    <span>Total:</span>
                    <span>Bs. <?= number_format($total, 2) ?></span>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="checkout.php" style="padding: 1rem 2rem; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-size: 1.1rem;">
                        ✅ Proceder al Pago
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirmación para vaciar carrito
        const vaciarBtn = document.querySelector('a[href*="vaciar=1"]');
        if (vaciarBtn) {
            vaciarBtn.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de vaciar todo el carrito?')) {
                    e.preventDefault();
                }
            });
        }
        
        // Validar formulario
        const form = document.getElementById('form-carrito');
        if (form) {
            form.addEventListener('submit', function(e) {
                const inputs = form.querySelectorAll('.cantidad-input');
                let carritoVacio = true;
                
                inputs.forEach(input => {
                    if (parseInt(input.value) > 0) {
                        carritoVacio = false;
                    }
                });
                
                if (carritoVacio) {
                    e.preventDefault();
                    alert('El carrito no puede estar vacío');
                }
            });
        }
    });
    </script>
</body>
</html>