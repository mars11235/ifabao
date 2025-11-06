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
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRF($_POST['csrf_token'] ?? '')) {
    $correo = sanitizarEntrada($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($correo) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } elseif (!validarEmail($correo)) {
        $error = 'Correo electrónico no válido';
    } else {
        $usuario = $db->login($correo, $password);
        
        if ($usuario && !isset($usuario['error'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombre'];
            $_SESSION['user_type'] = $usuario['tipo'];
            $_SESSION['login_time'] = time();
            
            session_regenerate_id(true);
            
            // Redirigir según tipo de usuario
            $redirect = match($usuario['tipo']) {
                'admin' => 'admin.php',
                'artista', 'comprador' => 'dashboard.php',
                default => 'index.php'
            };
            header("Location: $redirect");
            exit;
        } else {
            $error = $usuario['error'] ?? 'Correo o contraseña incorrectos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px; margin: 100px auto; padding: 2rem;
            background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .login-title { text-align: center; color: #8B4513; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input {
            width: 100%; padding: 0.8rem; border: 2px solid #ddd; border-radius: 5px;
            font-size: 1rem; transition: border-color 0.3s;
        }
        .form-group input:focus { border-color: #8B4513; outline: none; }
        .btn-primary {
            width: 100%; padding: 1rem; background: #8B4513; color: white;
            border: none; border-radius: 5px; font-size: 1rem; cursor: pointer;
        }
        .error { background: #f8d7da; color: #721c24; padding: 0.8rem; border-radius: 5px; margin-bottom: 1rem; }
        .demo-accounts { background: #e3f2fd; padding: 1rem; border-radius: 5px; margin-top: 2rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section style="padding: 80px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
           <div class="login-container">
    <!-- Logo en formularios -->
    <div class="form-logo">
        <img src="images/logo-ifabao.png" alt="IFABAO" class="form-logo-img">
    </div>
    <h2 class="login-title">Iniciar Sesión</h2>
    <!-- ... resto del formulario ... -->
</div>

<style>
.form-logo {
    text-align: center;
    margin-bottom: 1.5rem;
}

.form-logo-img {
    height: 70px;
    width: auto;
    border-radius: 8px;
}
</style>
                <h2 class="login-title">Iniciar Sesión</h2>
                
                <?php if ($error): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label for="correo">Correo Electrónico:</label>
                        <input type="email" id="correo" name="correo" required 
                               value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>"
                               placeholder="tu@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="••••••••" minlength="6">
                    </div>
                    
                    <button type="submit" class="btn-primary">Iniciar Sesión</button>
                </form>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="recuperar_password.php" style="color: #8B4513;">¿Olvidaste tu contraseña?</a>
                </div>

                <p style="text-align: center; margin-top: 1rem;">
                    ¿No tienes cuenta? <a href="register.php" style="color: #8B4513;">Regístrate aquí</a>
                </p>

                <!-- Cuentas demo -->
                <div class="demo-accounts">
                    <h4>Cuentas de Demo:</h4>
                    <p><strong>Artista:</strong> artista@ifabao.com / password</p>
                    <p><strong>Admin:</strong> admin@ifabao.com / password</p>
                    <p><strong>Comprador:</strong> comprador@ifabao.com / password</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>