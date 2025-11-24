<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

// --- 1. CONFIGURACIÓN DE RUTAS (CORREGIDO) ---
// Estamos en: .../OVNI/panel/dashboard
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']); 
// Subimos 2 niveles para llegar a: .../OVNI
$ruta_raiz   = dirname(dirname($ruta_actual));            
$APP_ROOT    = rtrim(str_replace('\\', '/', $ruta_raiz), '/');

// --- AQUÍ ESTABA EL ERROR ---
// Usamos /../../ para salir de dashboard y salir de panel
require_once __DIR__ . '/../../funciones.php'; 

// --- 2. SEGURIDAD: SOLO ADMIN ---
if (!isset($_SESSION['usuario'])) {
    header("Location: {$APP_ROOT}/login.php");
    exit;
}

// Verificamos el rol
if (($_SESSION['usuario']['rol'] ?? 'usuario') !== 'admin') {
    die("<div style='padding:50px;text-align:center;font-family:sans-serif;'>
            <h1>⛔ Acceso Denegado</h1>
            <p>No tienes permisos de Administrador para ver esta página.</p>
            <a href='dashboard.php'>Volver al Dashboard</a>
         </div>");
}

$msg = '';
$msgType = '';

// --- 3. PROCESAR ACCIONES ---

// Crear Usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = trim($_POST['nombre']);
    $email  = trim($_POST['email']);
    $pass   = $_POST['password'];
    $rol    = $_POST['rol'];

    if ($nombre && $email && $pass) {
        // Esta función debe existir en funciones.php
        $resultado = crear_usuario_nuevo($nombre, $email, $pass, $rol);
        if ($resultado === true) {
            $msg = "Usuario creado correctamente.";
            $msgType = "success";
        } else {
            $msg = "Error: " . $resultado;
            $msgType = "danger";
        }
    } else {
        $msg = "Todos los campos son obligatorios.";
        $msgType = "warning";
    }
}

// Eliminar Usuario
if (isset($_GET['borrar'])) {
    $idBorrar = (int)$_GET['borrar'];
    if ($idBorrar === (int)$_SESSION['usuario']['id_usuario']) {
        $msg = "No puedes eliminar tu propio usuario administrador.";
        $msgType = "danger";
    } else {
        if (eliminar_usuario($idBorrar)) {
            $msg = "Usuario eliminado.";
            $msgType = "success";
        }
    }
}

$listaUsuarios = obtener_todos_usuarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary">Usuarios y Permisos</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            &larr; Volver al Dashboard
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Nuevo Usuario</div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="crear">
                        
                        <div class="mb-3">
                            <label class="form-label small">Nombre / Oficina</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Ventas Mendoza">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control" required placeholder="usuario@empresa.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Contraseña</label>
                            <input type="password" name="password" class="form-control" required placeholder="******">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Rol / Permisos</label>
                            <select name="rol" class="form-select">
                                <option value="usuario">Usuario (Limitado)</option>
                                <option value="admin">Administrador (Total)</option>
                            </select>
                            <div class="form-text small">
                                <strong>Usuario:</strong> Solo ve dashboard y documentos.<br>
                                <strong>Admin:</strong> Puede crear/borrar usuarios.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary">Crear Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listaUsuarios as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white me-2" style="width:32px;height:32px;font-size:12px;">
                                            <?= strtoupper(substr($u['nombre_oficina'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($u['nombre_oficina']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email_usuario']) ?></td>
                                <td>
                                    <?php if ($u['rol'] === 'admin'): ?>
                                        <span class="badge bg-primary">ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">USUARIO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ((int)$u['id_usuario'] === (int)$_SESSION['usuario']['id_usuario']): ?>
                                        <span class="text-muted small italic">Tú</span>
                                    <?php else: ?>
                                        <a href="?borrar=<?= $u['id_usuario'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('¿Seguro que querés eliminar a este usuario?');">
                                           Eliminar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>