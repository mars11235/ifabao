<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Verificar autorización de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    $_SESSION['error'] = "Acceso no autorizado al panel de administración";
    header('Location: login.php');
    exit;
}

$db = new Database();
$estadisticas = $db->obtenerEstadisticas();
$obras = $db->obtenerObras(50);
$artistas = $db->obtenerArtistas(50);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRF($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['cambiar_estado_obra'])) {
        $obra_id = intval($_POST['obra_id'] ?? 0);
        $nuevo_estado = sanitizarEntrada($_POST['nuevo_estado'] ?? '');
        
        if ($obra_id > 0 && in_array($nuevo_estado, ['revision', 'disponible', 'bloqueada'])) {
            $_SESSION['success_admin'] = "Estado de la obra actualizado";
        }
    }
    
    header('Location: admin.php');
    exit;
}

$success_admin = $_SESSION['success_admin'] ?? '';
$error_admin = $_SESSION['error_admin'] ?? '';
unset($_SESSION['success_admin'], $_SESSION['error_admin']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?= SITE_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container { margin-top: 80px; padding: 2rem 0; background: #f5f5f5; min-height: 100vh; }
        .admin-header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 2rem 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .admin-grid { display: grid; grid-template-columns: 250px 1fr; gap: 2rem; }
        .admin-sidebar { background: white; border-radius: 10px; padding: 1.5rem; }
        .admin-content { background: white; border-radius: 10px; padding: 2rem; }
        .nav-admin { list-style: none; padding: 0; }
        .nav-admin a { display: block; padding: 1rem; background: #f8f9fa; margin-bottom: 0.5rem; border-radius: 5px; text-decoration: none; color: #2c3e50; }
        .nav-admin a.active { background: #8B4513; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        @media (max-width: 768px) {
            .admin-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <div class="container">
                <h1>Panel de Administración</h1>
                <p>Bienvenido, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?></p>
            </div>
        </div>
        
        <div class="container">
            <?php if ($success_admin): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($success_admin) ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_obras'] ?></div>
                    <div>Obras</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_artistas'] ?></div>
                    <div>Artistas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_ventas'] ?></div>
                    <div>Ventas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Bs. <?= number_format($estadisticas['ingresos_totales'], 2) ?></div>
                    <div>Ingresos</div>
                </div>
            </div>
            
            <div class="admin-grid">
                <!-- Sidebar -->
                <div class="admin-sidebar">
                    <h3>Navegación</h3>
                    <ul class="nav-admin">
                        <li><a href="#" class="active" onclick="showTab('dashboard')">📊 Dashboard</a></li>
                        <li><a href="#" onclick="showTab('obras')">🎨 Obras</a></li>
                        <li><a href="#" onclick="showTab('artistas')">👥 Artistas</a></li>
                        <li><a href="#" onclick="showTab('config')">⚙️ Configuración</a></li>
                    </ul>
                </div>
                
                <!-- Contenido -->
                <div class="admin-content">
                    <!-- Dashboard -->
                    <div id="dashboard" class="tab-content active">
                        <h2>Resumen General</h2>
                        <p>Bienvenido al panel de administración de IFABAO.</p>
                    </div>
                    
                    <!-- Obras -->
                    <div id="obras" class="tab-content">
                        <h2>Gestionar Obras</h2>
                        <p>Total: <strong><?= count($obras) ?></strong> obras</p>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Obra</th>
                                    <th>Artista</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($obras as $obra): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($obra['titulo']) ?></strong></td>
                                    <td><?= htmlspecialchars($obra['artista_nombre']) ?></td>
                                    <td>Bs. <?= number_format($obra['precio'], 2) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'disponible' => 'badge-success',
                                            'revision' => 'badge-warning'
                                        ][$obra['estado']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= ucfirst($obra['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
                                            <select name="nuevo_estado" onchange="this.form.submit()">
                                                <option value="">Cambiar estado</option>
                                                <option value="disponible">Disponible</option>
                                                <option value="bloqueada">Bloquear</option>
                                            </select>
                                            <input type="hidden" name="cambiar_estado_obra" value="1">
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Otras pestañas -->
                    <div id="artistas" class="tab-content">
                        <h2>Gestionar Artistas</h2>
                        <p>Total: <strong><?= count($artistas) ?></strong> artistas</p>
                    </div>
                    
                    <div id="config" class="tab-content">
                        <h2>Configuración</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div style="margin-bottom: 1rem;">
                                <label>Comisión por Venta (%):</label>
                                <input type="number" name="comision" value="15" min="0" max="50">
                            </div>
                            <button type="submit" name="guardar_configuracion">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.nav-admin a').forEach(link => link.classList.remove('active'));
        
        document.getElementById(tabName).style.display = 'block';
        event.currentTarget.classList.add('active');
    }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>