<?php
declare(strict_types=1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURACIÓN
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']); 
$ruta_raiz   = dirname(dirname($ruta_actual)); 
$APP_ROOT    = rtrim(str_replace('\\', '/', $ruta_raiz), '/');

require_once __DIR__ . '/../../funciones.php';

// 2. SEGURIDAD
if (empty($_SESSION['usuario']['id_usuario'])) {
    header("Location: {$APP_ROOT}/login.php", true, 302);
    exit;
}

$user = $_SESSION['usuario'];

// VERIFICACIÓN DE ROL (Solo Admin)
if (($user['rol'] ?? 'usuario') !== 'admin') {
    // Si intenta entrar un no-admin, lo mandamos al dashboard
    header("Location: dashboard.php");
    exit;
}

// Helpers
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Datos Visuales
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$avatarUrl     = $APP_ROOT . '/assets/img/' . ($user['avatar_usuario'] ?? 'noavatar.png');
$colorAcento   = $user['color_tema'] ?? '#009640'; 

// 3. LÓGICA (Crear / Borrar)
$msg = '';
$msgType = '';

// Procesar Formulario (Crear)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $rol    = $_POST['rol'] ?? 'usuario';

    if ($nombre && $email && $pass) {
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

// Procesar Eliminación
if (isset($_GET['borrar'])) {
    $idBorrar = (int)$_GET['borrar'];
    if ($idBorrar === (int)$user['id_usuario']) {
        $msg = "No puedes eliminar tu propio usuario.";
        $msgType = "danger";
    } else {
        if (eliminar_usuario($idBorrar)) {
            $msg = "Usuario eliminado.";
            $msgType = "success";
        } else {
            $msg = "Error al eliminar.";
            $msgType = "danger";
        }
    }
}

// Obtener lista actualizada
$listaUsuarios = obtener_todos_usuarios();

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Usuarios | <?= h($nombreOficina) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    /* --- SISTEMA DE DISEÑO UNIFICADO --- */
    :root {
        --bg-body: #f4f6f8;
        --hero-bg: #1e293b;
        --primary: <?= $colorAcento ?>;
        --text-main: #334155;
        --text-muted: #64748b;
        --card-bg: #ffffff;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* 1. NAVBAR */
    .navbar-pro {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.75rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .brand-group { display: flex; align-items: center; gap: 12px; }
    .nav-logo { height: 32px; width: auto; }
    .brand-text { font-weight: 700; color: #0f172a; letter-spacing: -0.5px; font-size: 1.1rem; }
    .nav-user-img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; }

    /* 2. HERO */
    .hero-pattern {
        background-color: var(--hero-bg);
        background-image: radial-gradient(#ffffff 1px, transparent 1px);
        background-size: 30px 30px;
        padding: 3rem 0 7rem 0;
        color: white;
        position: relative;
        text-align: center;
        overflow: hidden;
    }
    .hero-pattern::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.85) 0%, rgba(30, 41, 59, 1) 100%);
        pointer-events: none;
    }
    .hero-content { position: relative; z-index: 2; }

    .btn-back {
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 50px;
        padding: 6px 20px;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px;
        transition: all 0.2s;
        margin-bottom: 1.5rem;
    }
    .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }

    /* 3. CONTENEDOR SOLAPADO */
    .content-overlap {
        max-width: 1200px;
        margin: -4rem auto 3rem auto;
        padding: 0 20px;
        position: relative;
        z-index: 10;
        width: 100%;
    }

    /* 4. TARJETAS */
    .app-card {
        background: var(--card-bg);
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        height: 100%;
    }
    
    .card-head {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 600;
        color: #0f172a;
        background: white;
        display: flex; justify-content: space-between; align-items: center;
    }

    .card-body-pad { padding: 1.5rem; }

    /* 5. TABLA LIMPIA */
    .table-clean { margin-bottom: 0; }
    .table-clean th {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 700;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
        background: #f8fafc;
    }
    .table-clean td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
        color: var(--text-main);
    }
    .table-clean tr:last-child td { border-bottom: none; }

    /* Badges */
    .badge-role {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .role-admin { background: #e0e7ff; color: #4f46e5; }
    .role-user { background: #f1f5f9; color: #64748b; }

    /* Formulario */
    .form-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
    }

    .btn-primary-theme {
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.7rem 1.5rem;
        font-weight: 600;
        width: 100%;
        transition: opacity 0.2s;
    }
    .btn-primary-theme:hover { opacity: 0.9; color: white; }

    .user-avatar-sm {
        width: 32px; height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        background: #eee;
    }

  </style>
</head>
<body>

    <nav class="navbar-pro">
        <div class="brand-group">
            <img src="<?= $APP_ROOT ?>/assets/img/logo.png" alt="OVNI" class="nav-logo">
            <span class="brand-text d-none d-sm-block">OVNI Panel</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block lh-1">
                <small class="d-block fw-bold text-dark"><?= h($nombreOficina) ?></small>
            </div>
            <img src="<?= $avatarUrl ?>" alt="User" class="nav-user-img">
            <a href="<?= $APP_ROOT ?>/logout.php" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                <i class="bi bi-box-arrow-right text-danger"></i>
            </a>
        </div>
    </nav>

    <header class="hero-pattern">
        <div class="container hero-content">
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
            <h2 class="h3 fw-bold text-white mb-1">Gestión de Usuarios</h2>
            <p class="text-white text-opacity-75 small">Administra el acceso y roles de tu equipo</p>
        </div>
    </header>

    <div class="content-overlap">
        
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> border-0 shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div><?= h($msg) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="app-card shadow-sm">
                    <div class="card-head">
                        <span><i class="bi bi-people me-2 text-muted"></i>Usuarios Registrados</span>
                        <span class="badge bg-light text-dark border"><?= count($listaUsuarios) ?> Total</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-clean mb-0">
                            <thead>
                                <tr>
                                    <th>Usuario / Oficina</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaUsuarios as $u): ?>
                                    <?php 
                                        $uAvatar = $APP_ROOT . '/assets/img/' . ($u['avatar_usuario'] ?? 'noavatar.png');
                                        $esAdmin = ($u['rol'] === 'admin');
                                        $esYo    = ((int)$u['id_usuario'] === (int)$user['id_usuario']);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $uAvatar ?>" class="user-avatar-sm">
                                                <span class="fw-bold"><?= h($u['nombre_oficina']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="text-muted small"><?= h($u['email_usuario']) ?></span></td>
                                        <td>
                                            <span class="badge-role <?= $esAdmin ? 'role-admin' : 'role-user' ?>">
                                                <?= $esAdmin ? 'ADMIN' : 'USUARIO' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($esYo): ?>
                                                <span class="text-muted small fst-italic">Tú</span>
                                            <?php else: ?>
                                                <a href="?borrar=<?= $u['id_usuario'] ?>" 
                                                   class="btn btn-sm btn-light text-danger border-0"
                                                   onclick="return confirm('¿Eliminar usuario definitivamente?');"
                                                   title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($listaUsuarios)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No hay usuarios registrados</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="app-card shadow-sm sticky-top" style="top: 90px; z-index: 1;">
                    <div class="card-head">
                        <span><i class="bi bi-person-plus me-2 text-muted"></i>Nuevo Usuario</span>
                    </div>
                    <div class="card-body-pad">
                        <form method="POST" action="">
                            <input type="hidden" name="accion" value="crear">
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre / Oficina</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej: Ventas" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="correo@empresa.com" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" placeholder="******" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Rol de Acceso</label>
                                <select name="rol" class="form-select">
                                    <option value="usuario">Usuario (Limitado)</option>
                                    <option value="admin">Administrador (Total)</option>
                                </select>
                                <div class="form-text text-muted small mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Usuario:</strong> Solo ve sus documentos.<br>
                                    <strong>Admin:</strong> Gestiona usuarios.
                                </div>
                            </div>

                            <button type="submit" class="btn-primary-theme">
                                Crear Usuario
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>