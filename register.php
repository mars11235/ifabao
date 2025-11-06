<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$success = '';
$error = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRF($_POST['csrf_token'] ?? '')) {
    $nombre = sanitizarEntrada($_POST['nombre'] ?? '');
    $email = sanitizarEntrada($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $telefono = sanitizarEntrada($_POST['telefono'] ?? '');
    $ciudad = sanitizarEntrada($_POST['ciudad'] ?? '');
    
    // Validaciones
    $errores = [];
    
    if (strlen($nombre) < 2) $errores[] = "Nombre debe tener al menos 2 caracteres";
    if (!validarEmail($email)) $errores[] = "Email no válido";
    if (strlen($password) < 6) $errores[] = "Contraseña debe tener al menos 6 caracteres";
    if ($password !== $confirm_password) $errores[] = "Las contraseñas no coinciden";
    if (!in_array($tipo, ['artista', 'comprador'])) $errores[] = "Selecciona un tipo de cuenta";
    if (empty($ciudad)) $errores[] = "Selecciona una ciudad";
    if (!empty($telefono) && !validarTelefonoBoliviano($telefono)) $errores[] = "Teléfono no válido";
    
    if (empty($errores)) {
        $resultado = $db->registrarUsuario([
            'nombre' => $nombre,
            'correo' => $email,
            'password' => $password,
            'tipo' => $tipo,
            'telefono' => $telefono,
            'ciudad' => $ciudad
        ]);
        
        if ($resultado['success']) {
            $success = "¡Cuenta creada! " . $resultado['message'];
            $_POST = []; // Limpiar formulario
        } else {
            $error = $resultado['message'];
        }
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
    <title>Registro - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 500px; margin: 100px auto; padding: 2rem;
            background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group select {
            width: 100%; padding: 0.8rem; border: 2px solid #ddd; border-radius: 5px;
        }
        .form-group input:focus, .form-group select:focus { border-color: #8B4513; outline: none; }
        .success { background: #e8f5e8; color: #2e7d32; padding: 0.8rem; border-radius: 5px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 0.8rem; border-radius: 5px; margin-bottom: 1rem; }
        .user-type { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0; }
        .user-type-option {
            border: 2px solid #ddd; padding: 1.5rem; border-radius: 8px; text-align: center;
            cursor: pointer; transition: all 0.3s; background: white;
        }
        .user-type-option:hover, .user-type-option.selected { border-color: #8B4513; background: #f9f5f0; }
        .user-type-option input { display: none; }
        .user-type-icon { font-size: 2rem; margin-bottom: 0.5rem; display: block; }
        .password-strength { margin-top: 0.5rem; font-size: 0.8rem; }
        .strength-weak { color: #dc3545; } .strength-strong { color: #28a745; }
        
        @media (max-width: 768px) {
            .user-type { grid-template-columns: 1fr; }
            .register-container { margin: 80px auto; padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="register-container">
        <h2 style="text-align: center; color: #8B4513; margin-bottom: 2rem;">Crear Cuenta</h2>
        
        <?php if($success): ?>
            <div class="success">
                <?= $success ?>
                <p style="margin-top: 1rem;">
                    <a href="login.php" style="display: block; text-align: center; padding: 0.8rem; background: #8B4513; color: white; text-decoration: none; border-radius: 5px;">
                        Ir al Login
                    </a>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if(!$success): ?>
        <form method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="nombre" required placeholder="Tu nombre completo"
                       value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>"
                       minlength="2" maxlength="100">
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="tu@email.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono" placeholder="71234567"
                       value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Ciudad *</label>
                <select name="ciudad" required>
                    <option value="">Selecciona tu ciudad</option>
                    <option value="Oruro" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'Oruro') ? 'selected' : '' ?>>Oruro</option>
                    <option value="La Paz" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'La Paz') ? 'selected' : '' ?>>La Paz</option>
                    <option value="Cochabamba" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'Cochabamba') ? 'selected' : '' ?>>Cochabamba</option>
                    <option value="Santa Cruz" <?= (isset($_POST['ciudad']) && $_POST['ciudad'] == 'Santa Cruz') ? 'selected' : '' ?>>Santa Cruz</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tipo de Cuenta *</label>
                <div class="user-type">
                    <label class="user-type-option" onclick="selectUserType('artista')">
                        <input type="radio" name="tipo" value="artista" required 
                               <?= (isset($_POST['tipo']) && $_POST['tipo'] == 'artista') ? 'checked' : '' ?>>
                        <span class="user-type-icon">🎨</span>
                        <div><strong>Artista</strong></div>
                        <small>Vender mis obras</small>
                    </label>
                    
                    <label class="user-type-option" onclick="selectUserType('comprador')">
                        <input type="radio" name="tipo" value="comprador" required 
                               <?= (isset($_POST['tipo']) && $_POST['tipo'] == 'comprador') ? 'checked' : '' ?>>
                        <span class="user-type-icon">🛒</span>
                        <div><strong>Comprador</strong></div>
                        <small>Comprar arte</small>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Contraseña *</label>
                <input type="password" name="password" required minlength="6"
                       placeholder="Mínimo 6 caracteres" oninput="checkPasswordStrength(this.value)">
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label>Confirmar Contraseña *</label>
                <input type="password" name="confirm_password" required 
                       placeholder="Repite tu contraseña" oninput="checkPasswordMatch()">
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <button type="submit" style="width: 100%; padding: 1rem; background: #8B4513; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Crear Cuenta
            </button>
        </form>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 1rem;">
            ¿Ya tienes cuenta? <a href="login.php" style="color: #8B4513;">Inicia sesión aquí</a>
        </p>
    </div>

    <script>
    function selectUserType(type) {
        document.querySelectorAll('.user-type-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
    }
    
    function checkPasswordStrength(password) {
        const el = document.getElementById('password-strength');
        if (password.length === 0) {
            el.textContent = '';
        } else if (password.length < 6) {
            el.textContent = 'Débil - Mínimo 6 caracteres';
            el.className = 'password-strength strength-weak';
        } else {
            el.textContent = '✓ Contraseña válida';
            el.className = 'password-strength strength-strong';
        }
    }
    
    function checkPasswordMatch() {
        const password = document.querySelector('input[name="password"]').value;
        const confirm = document.querySelector('input[name="confirm_password"]').value;
        const el = document.getElementById('password-match');
        
        if (confirm.length === 0) {
            el.textContent = '';
        } else if (password === confirm) {
            el.textContent = '✓ Las contraseñas coinciden';
            el.className = 'password-strength strength-strong';
        } else {
            el.textContent = '✗ Las contraseñas no coinciden';
            el.className = 'password-strength strength-weak';
        }
    }
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        // Seleccionar tipo por defecto
        const selected = document.querySelector('input[name="tipo"]:checked');
        if (selected) {
            selected.parentElement.classList.add('selected');
        }
        
        // Validación del formulario
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirm = document.querySelector('input[name="confirm_password"]').value;
            const tipo = document.querySelector('input[name="tipo"]:checked');
            
            if (password.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                e.preventDefault();
                return;
            }
            
            if (password !== confirm) {
                alert('Las contraseñas no coinciden');
                e.preventDefault();
                return;
            }
            
            if (!tipo) {
                alert('Debes seleccionar un tipo de cuenta');
                e.preventDefault();
                return;
            }
            
            // Loading
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = 'Creando cuenta...';
            button.disabled = true;
        });
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>