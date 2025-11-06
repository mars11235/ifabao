<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Verificar que el carrito no esté vacío
if (empty($_SESSION['carrito'])) {
    $_SESSION['error_checkout'] = "Tu carrito está vacío";
    header('Location: carrito.php');
    exit;
}

$db = new Database();
$error = '';
$success = '';

// Verificar disponibilidad de obras
foreach ($_SESSION['carrito'] as $item) {
    if (!$db->verificarDisponibilidadObra($item['id'])) {
        $error = "La obra '{$item['titulo']}' ya no está disponible";
        break;
    }
}

if (!empty($error)) {
    $_SESSION['error_carrito'] = $error;
    header('Location: carrito.php');
    exit;
}

// Calcular totales
$subtotal = 0;
$envio = 50;
foreach ($_SESSION['carrito'] as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

// Envío gratuito para compras mayores a 2000 Bs.
if ($subtotal > 2000) {
    $envio = 0;
}

$total = $subtotal + $envio;

// Procesar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pedido']) && validateCSRF($_POST['csrf_token'] ?? '')) {
    $nombre = sanitizarEntrada($_POST['nombre'] ?? '');
    $email = sanitizarEntrada($_POST['email'] ?? '');
    $telefono = sanitizarEntrada($_POST['telefono'] ?? '');
    $direccion = sanitizarEntrada($_POST['direccion'] ?? '');
    $ciudad = sanitizarEntrada($_POST['ciudad'] ?? '');
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    
    // Validaciones básicas
    $errores = [];
    if (strlen($nombre) < 2) $errores[] = "Nombre debe tener al menos 2 caracteres";
    if (!validarEmail($email)) $errores[] = "Email no válido";
    if (!validarTelefonoBoliviano($telefono)) $errores[] = "Teléfono boliviano no válido";
    if (strlen($direccion) < 10) $errores[] = "Dirección debe tener al menos 10 caracteres";
    if (empty($ciudad)) $errores[] = "Selecciona una ciudad";
    if (!in_array($metodo_pago, ['qr', 'transferencia'])) $errores[] = "Selecciona método de pago válido";
    
    if (empty($errores)) {
        // Simular éxito del pedido
        $numero_pedido = 'PED-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        
        $success = "
            <h3>¡Pedido procesado exitosamente!</h3>
            <p><strong>Número de pedido:</strong> {$numero_pedido}</p>
            <p><strong>Total:</strong> Bs. " . number_format($total, 2) . "</p>
            <p><strong>Método de pago:</strong> " . ($metodo_pago === 'qr' ? 'QR Bolivia' : 'Transferencia') . "</p>
            <p>Te hemos enviado un correo con los detalles a <strong>{$email}</strong></p>
        ";
        
        // Limpiar carrito
        $_SESSION['carrito'] = [];
    } else {
        $error = implode("<br>", $errores);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .checkout-container { margin-top: 100px; padding: 2rem 0; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 400px; gap: 2rem; }
        .checkout-form, .order-summary { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-section { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .payment-methods { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0; }
        .payment-method { border: 2px solid #ddd; padding: 1rem; border-radius: 5px; text-align: center; cursor: pointer; }
        .payment-method.selected { border-color: #8B4513; background: #f9f5f0; }
        .payment-method input { display: none; }
        .order-item { display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid #eee; }
        .order-total { font-size: 1.2rem; font-weight: bold; color: #8B4513; border-bottom: none; }
        .success-message { background: #e8f5e8; color: #2e7d32; padding: 2rem; border-radius: 10px; text-align: center; margin: 2rem 0; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; }
        
        @media (max-width: 768px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .form-row, .payment-methods { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container checkout-container">
        <h1>✅ Finalizar Compra</h1>
        
        <?php if($success): ?>
            <div class="success-message">
                <?= $success ?>
                <div style="margin-top: 2rem;">
                    <a href="galeria.php" style="padding: 1rem 2rem; background: #8B4513; color: white; text-decoration: none; border-radius: 5px; margin: 0 0.5rem;">
                        Seguir Comprando
                    </a>
                </div>
            </div>
        <?php else: ?>
        
        <?php if($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <!-- Formulario -->
            <div class="checkout-form">
                <form method="POST" id="checkout-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <!-- Información de contacto -->
                    <div class="form-section">
                        <h3>📞 Información de Contacto</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre completo *</label>
                                <input type="text" name="nombre" required 
                                       value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>"
                                       minlength="2">
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" required 
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Teléfono *</label>
                            <input type="tel" name="telefono" required 
                                   placeholder="71234567"
                                   pattern="[67][0-9]{7}"
                                   value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
                        </div>
                    </div>
                    
                    <!-- Dirección de envío -->
                    <div class="form-section">
                        <h3>🏠 Dirección de Envío</h3>
                        <div class="form-group">
                            <label>Dirección completa *</label>
                            <textarea name="direccion" rows="3" required 
                                      placeholder="Calle, número, zona..."
                                      minlength="10"><?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Ciudad *</label>
                            <select name="ciudad" required>
                                <option value="">Selecciona tu ciudad</option>
                                <option value="oruro" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'oruro') ? 'selected' : 'selected' ?>>Oruro</option>
                                <option value="lapaz" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'lapaz') ? 'selected' : '' ?>>La Paz</option>
                                <option value="cochabamba" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'cochabamba') ? 'selected' : '' ?>>Cochabamba</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Método de pago -->
                    <div class="form-section">
                        <h3>💳 Método de Pago</h3>
                        <div class="payment-methods">
                            <label class="payment-method" onclick="selectPayment('qr')">
                                <input type="radio" name="metodo_pago" value="qr" required>
                                <div>📱 QR Bolivia</div>
                            </label>
                            
                            <label class="payment-method" onclick="selectPayment('transferencia')">
                                <input type="radio" name="metodo_pago" value="transferencia" required>
                                <div>🏦 Transferencia</div>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="procesar_pedido" style="width: 100%; padding: 1rem; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 1.1rem;">
                        ✅ Confirmar y Pagar Bs. <?= number_format($total, 2) ?>
                    </button>
                </form>
            </div>
            
            <!-- Resumen del pedido -->
            <div class="order-summary">
                <h3>Resumen de tu Pedido</h3>
                
                <?php foreach ($_SESSION['carrito'] as $item): ?>
                <div class="order-item">
                    <div>
                        <strong><?= htmlspecialchars($item['titulo']) ?></strong>
                        <br>
                        <small>Por: <?= htmlspecialchars($item['artista']) ?></small>
                        <br>
                        <small><?= $item['cantidad'] ?> x Bs. <?= number_format($item['precio'], 2) ?></small>
                    </div>
                    <div>Bs. <?= number_format($item['precio'] * $item['cantidad'], 2) ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="order-item">
                    <div>Subtotal:</div>
                    <div>Bs. <?= number_format($subtotal, 2) ?></div>
                </div>
                
                <div class="order-item">
                    <div>Envío:</div>
                    <div>
                        <?php if ($envio > 0): ?>
                            Bs. <?= number_format($envio, 2) ?>
                        <?php else: ?>
                            <span style="color: #28a745;">GRATIS</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-item order-total">
                    <div>Total:</div>
                    <div>Bs. <?= number_format($total, 2) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function selectPayment(method) {
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Seleccionar QR por defecto
        document.querySelector('input[value="qr"]').checked = true;
        document.querySelector('.payment-method').classList.add('selected');
        
        // Validar formulario
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const telefono = document.querySelector('input[name="telefono"]').value;
            const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
            
            if (!/^[67][0-9]{7}$/.test(telefono)) {
                alert('Ingresa un teléfono boliviano válido (7xxxxxxx o 6xxxxxxx)');
                e.preventDefault();
                return;
            }
            
            if (!metodoPago) {
                alert('Selecciona un método de pago');
                e.preventDefault();
                return;
            }
            
            if (!confirm('¿Confirmar pago de Bs. <?= number_format($total, 2) ?>?')) {
                e.preventDefault();
            }
        });
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>