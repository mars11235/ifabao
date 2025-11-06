<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener historial de pedidos del usuario
// Mostrar estado de pedidos
// Permitir descargar facturas
?>