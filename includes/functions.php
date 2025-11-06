<?php
// functions.php - SOLO FUNCIONES QUE NO ESTÁN EN CONFIG.PHP

/**
 * Validar y procesar imagen de obra
 */
function procesarImagenObra($archivo_imagen) {
    $resultado = subirImagen($archivo_imagen);
    
    if (isset($resultado['error'])) {
        return ['error' => $resultado['error']];
    }
    
    // Validar dimensiones mínimas
    if ($resultado['ancho'] < 400 || $resultado['alto'] < 400) {
        // Eliminar archivo subido
        if (file_exists($resultado['ruta'])) {
            unlink($resultado['ruta']);
        }
        return ['error' => 'La imagen debe tener al menos 400x400 píxeles'];
    }
    
    return ['success' => $resultado['ruta']];
}

/**
 * Generar datos de contacto seguros para artistas
 */
function generarContactoArtista($artista_id, $artista_nombre) {
    return [
        'email' => "artista{$artista_id}@ifabao.com",
        'telefono' => "+591 " . rand(60000000, 79999999),
        'nombre' => $artista_nombre
    ];
}

/**
 * Validar formulario de obra
 */
function validarFormularioObra($datos, $imagen) {
    $errores = [];
    
    if (strlen($datos['titulo'] ?? '') < 3) {
        $errores[] = "El título debe tener al menos 3 caracteres";
    }
    
    if (strlen($datos['descripcion'] ?? '') < 10) {
        $errores[] = "La descripción debe tener al menos 10 caracteres";
    }
    
    $precio = floatval($datos['precio'] ?? 0);
    if ($precio <= 0 || $precio > 100000) {
        $errores[] = "El precio debe ser entre 0.01 y 100,000 Bs.";
    }
    
    if (empty($datos['categoria_id']) || intval($datos['categoria_id']) <= 0) {
        $errores[] = "Selecciona una categoría válida";
    }
    
    if (!isset($imagen['error']) || $imagen['error'] !== UPLOAD_ERR_OK) {
        $errores[] = "Debes seleccionar una imagen válida";
    }
    
    return $errores;
}

/**
 * Formatear mensajes de éxito/error
 */
function mostrarMensaje($mensaje, $tipo = 'success') {
    $clase = $tipo === 'success' ? 'alert-success' : 'alert-error';
    return "<div class='alert {$clase}'>{$mensaje}</div>";
}

/**
 * Redireccionar con mensaje flash
 */
function redireccionarConMensaje($url, $mensaje, $tipo = 'success') {
    $_SESSION['flash_message'] = [
        'texto' => $mensaje,
        'tipo' => $tipo
    ];
    header('Location: ' . $url);
    exit;
}

/**
 * Obtener mensaje flash
 */
function obtenerMensajeFlash() {
    if (isset($_SESSION['flash_message'])) {
        $mensaje = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $mensaje;
    }
    return null;
}

/**
 * Mostrar mensaje flash si existe
 */
function mostrarMensajeFlash() {
    $mensaje = obtenerMensajeFlash();
    if ($mensaje) {
        return mostrarMensaje($mensaje['texto'], $mensaje['tipo']);
    }
    return '';
}

/**
 * Verificar si el usuario tiene permisos de artista
 */
function usuarioEsArtista() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artista';
}

/**
 * Verificar si el usuario es administrador
 */
function usuarioEsAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Obtener ID del usuario logueado de forma segura
 */
function obtenerUsuarioId() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Verificar si el usuario está logueado
 */
function usuarioLogueado() {
    return isset($_SESSION['user_id']);
}

/**
 * Generar URL segura para el sitio
 */
function url($ruta = '') {
    return SITE_URL . '/' . ltrim($ruta, '/');
}

/**
 * Generar ruta de archivo segura
 */
function asset($archivo = '') {
    return url($archivo);
}

/**
 * Debug function para desarrollo
 */
function debug($data, $die = false) {
    if (ENVIRONMENT === 'development') {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) die();
    }
}
?>