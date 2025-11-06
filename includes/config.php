<?php
// config.php - VERSIÓN CORREGIDA SIN FUNCIONES DUPLICADAS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Detección de entorno
$dominio = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('ENVIRONMENT', 
    strpos($dominio, 'localhost') !== false || 
    strpos($dominio, '127.0.0.1') !== false ? 'development' : 'production'
);

// Configuración de base de datos
if (ENVIRONMENT === 'production') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ifabao_prod');
    define('DB_USER', 'ifabao_user');
    define('DB_PASS', 'CambiarPorContraseñaSegura123!');
    define('DB_CHARSET', 'utf8mb4');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ifabao_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// Constantes de aplicación - CORREGIDAS PARA IFABAO
define('SITE_NAME', 'IFABAO - Bellas Artes Oruro');
define('SITE_URL', ENVIRONMENT === 'development' ? 'http://localhost/ifabao' : 'https://tudominio.com');
define('SITE_PATH', realpath(dirname(__FILE__) . '/..'));

// Configuración local boliviana
define('MONEDA', 'BOB');
define('SIMBOLO_MONEDA', 'Bs.');
define('COMISION_POR_DEFECTO', 15);
define('ENVIO_GRATIS_DESDE', 2000);

// Límites de la aplicación
define('MAX_SIZE_IMAGEN', 5 * 1024 * 1024);
define('MAX_OBRAS_POR_ARTISTA', 50);
define('SESSION_TIMEOUT', 1800);
define('UPLOAD_DIR', 'uploads/');
define('OBRAS_DIR', UPLOAD_DIR . 'obras/');
define('ARTISTAS_DIR', UPLOAD_DIR . 'artistas/');

// Manejo de errores
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// PALETA DE COLORES IFABAO - CORREGIDA
define('COLOR_AZUL_OSCURO', '#1C242A');
define('COLOR_GRIS_CLARO', '#D5D7DD');
define('COLOR_BEIGE', '#C2B39E');
define('COLOR_NARANJA_PRINCIPAL', '#E88E33');
define('COLOR_NARANJA_OSCURO', '#B34614');

// Configuración de efectos
define('ENABLE_ANIMATIONS', true);
define('ENABLE_PARTICLES', true);
define('ENABLE_GRADIENTS', true);

// Zona horaria
date_default_timezone_set('America/La_Paz');

// FUNCIONES ESENCIALES (solo las básicas, el resto en functions.php)
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generarCSRF() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function subirImagen($imagen, $directorio = OBRAS_DIR, $max_width = 2000, $max_height = 2000) {
    if (!isset($imagen['error']) || $imagen['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Error en la subida del archivo'];
    }
    
    if (!is_uploaded_file($imagen['tmp_name'])) {
        return ['error' => 'Archivo no válido'];
    }
    
    if ($imagen['size'] > MAX_SIZE_IMAGEN) {
        return ['error' => 'El archivo es demasiado grande (máximo 5MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $imagen['tmp_name']);
    finfo_close($finfo);
    
    $mime_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime_type, $mime_permitidos)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }
    
    $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $extensiones_permitidas)) {
        return ['error' => 'Extensión no permitida'];
    }
    
    if (!is_dir($directorio)) {
        if (!mkdir($directorio, 0755, true)) {
            return ['error' => 'No se pudo crear el directorio'];
        }
    }
    
    $nombre_archivo = uniqid('img_', true) . '.' . $extension;
    $ruta_completa = $directorio . $nombre_archivo;
    
    if (!move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
        return ['error' => 'Error al guardar la imagen'];
    }
    
    $dimensiones = getimagesize($ruta_completa);
    
    return [
        'success' => true,
        'ruta' => $ruta_completa,
        'nombre_archivo' => $nombre_archivo,
        'ancho' => $dimensiones[0],
        'alto' => $dimensiones[1],
        'mime_type' => $mime_type
    ];
}

function sanitizarEntrada($dato) {
    if ($dato === null) return null;
    
    if (is_array($dato)) {
        return array_map('sanitizarEntrada', $dato);
    }
    
    return htmlspecialchars(trim($dato), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function validarEmail($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

function validarTelefonoBoliviano($telefono) {
    $telefono = preg_replace('/[^0-9]/', '', strval($telefono));
    return preg_match('/^(591)?(6|7)[0-9]{7}$/', $telefono);
}

function validarPrecio($precio) {
    if (!is_numeric($precio)) return false;
    $precio = floatval($precio);
    return $precio >= 0 && $precio <= 1000000 ? round($precio, 2) : false;
}

function formatearPrecio($precio, $incluir_moneda = true) {
    $precio_validado = validarPrecio($precio);
    
    if ($precio_validado === false) {
        return 'Precio no válido';
    }
    
    $formateado = number_format($precio_validado, 2, ',', '.');
    
    if ($incluir_moneda) {
        return SIMBOLO_MONEDA . ' ' . $formateado;
    }
    
    return $formateado;
}

// Incluir funciones auxiliares (si existe)
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}
?>