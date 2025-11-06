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

// Procesar recuperación de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $email = sanitizarEntrada($_POST['email'] ?? '');
        
        if (empty($email) || !validarEmail($email)) {
            $error = 'Por favor ingresa un correo electrónico válido';
        } else {
            // Simular envío de email (en sistema real, enviar email con link de recuperación)
            $success = "Se ha enviado un enlace de recuperación a <strong>{$email}</strong>. Revisa tu bandeja de entrada y sigue las instrucciones.";
            
            // En un sistema real, aquí se:
            // 1. Verificaría que el email existe en la base de datos
            // 2. Generaría un token de recuperación
            // 3. Enviaría un email con el link de recuperación
            // 4. Registraría la solicitud en la base de datos
            
            error_log("Solicitud de recuperación de contraseña para: " . $email);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .recovery-container {
            max-width: 400px;
            margin: 100px auto 50px;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .recovery-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="recovery-container">
        <div class="recovery-icon">🔐</div>
        <h2 style="color: var(--primary); margin-bottom: 1rem;">Recuperar Contraseña</h2>
        <p style="color: #666; margin-bottom: 2rem;">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
        
        <?php if($success): ?>
            <div class="success">
                <?php echo $success; ?>
                <p style="margin-top: 1rem;">
                    <a href="login.php" class="btn" style="display: block; text-align: center;">Volver al Login</a>
                </p>
            </div>
        <?php else: ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required 
                       placeholder="tu@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <button type="submit" class="btn" style="width: 100%; background: var(--primary);">
                Enviar Enlace de Recuperación
            </button>
        </form>
        
        <div class="info">
            <strong>💡 ¿No recibes el email?</strong>
            <p style="margin: 0.5rem 0 0 0;">Revisa tu carpeta de spam o contacta a soporte técnico.</p>
        </div>
        
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 2rem;">
            <a href="login.php" style="color: var(--primary);">← Volver al Login</a>
        </p>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>