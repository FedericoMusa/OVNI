<?php
declare(strict_types=1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURACIÓN Y RUTAS
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']); 
$ruta_raiz   = dirname(dirname($ruta_actual)); 
$APP_ROOT    = rtrim(str_replace('\\', '/', $ruta_raiz), '/');

// 2. SEGURIDAD
if (empty($_SESSION['usuario']['id_usuario'])) {
    header("Location: {$APP_ROOT}/login.php", true, 302);
    exit;
}

$user = $_SESSION['usuario'];

if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Datos
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$emailUsuario  = $user['email_usuario'] ?? '';
$descripcion   = $user['descripcion_oficina'] ?? ''; 
$rol           = $user['rol'] ?? 'usuario';
$colorAcento   = $user['color_tema'] ?? '#009640'; 

$avatarName = $user['avatar_usuario'] ?? 'noavatar.png';
if (empty($avatarName)) $avatarName = 'noavatar.png';
$avatarUrl = "$APP_ROOT/assets/img/" . h($avatarName);

// Fecha actual para darle "vida" al dashboard
$fechaHoy = date("d/m/Y");

// Mensajes
$msg = '';
if (isset($_SESSION['flash_success'])) {
    $msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | <?= h($nombreOficina) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
        --bg-body: #f4f6f8;
        --hero-bg: #1e293b; /* Slate Dark */
        --primary: <?= $colorAcento ?>;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', sans-serif;
        color: #334155;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* 1. NAVBAR - Sólida y limpia */
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

    .brand-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .nav-logo { height: 32px; width: auto; }
    .brand-text { font-weight: 700; color: #0f172a; letter-spacing: -0.5px; font-size: 1.1rem; }

    /* 2. HERO CON TEXTURA (El secreto para que no se vea vacío) */
    .hero-pattern {
        background-color: var(--hero-bg);
        /* Patrón de puntos sutil */
        background-image: radial-gradient(#ffffff 1px, transparent 1px);
        background-size: 30px 30px;
        opacity: 1;
        padding: 4rem 0 7rem 0; /* Mucho padding abajo para el solapamiento */
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    /* Overlay degradado para suavizar el patrón */
    .hero-pattern::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.8) 0%, rgba(30, 41, 59, 1) 100%);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
    }

    .avatar-ring {
        width: 100px; height: 100px;
        border-radius: 50%;
        border: 4px solid rgba(255,255,255,0.15);
        padding: 3px; /* Espacio entre borde e imagen */
    }
    .avatar-img {
        width: 100%; height: 100%;
        border-radius: 50%;
        object-fit: cover;
        background: white;
    }

    /* 3. BARRA DE INFORMACIÓN (INFO STRIP) */
    .info-strip {
        max-width: 900px;
        margin: -3rem auto 3rem auto; /* Sube sobre el hero */
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
        position: relative;
        z-index: 10;
        display: flex;
        justify-content: space-around;
        align-items: center;
        border: 1px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .info-item {
        text-align: center;
        flex: 1;
        min-width: 150px;
    }
    .info-item:not(:last-child) { border-right: 1px solid #f1f5f9; }
    
    .info-label { font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; }
    .info-value { font-size: 1rem; font-weight: 600; color: #0f172a; margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 6px; }

    /* 4. GRID DE TARJETAS ROBUSTAS */
    .cards-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 20px;
        flex-grow: 1; /* Empuja el footer abajo */
    }

    .feature-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 2rem;
        height: 100%;
        transition: all 0.2s ease;
        text-decoration: none !important;
        display: block;
        position: relative;
        overflow: hidden;
    }

    .feature-card:hover {
        transform: translateY(-4px);
        border-color: var(--primary); /* Borde de color al hover */
        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.1);
    }

    .icon-spot {
        width: 56px; height: 56px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
    }

    /* Temas de tarjetas */
    .theme-config .icon-spot { background: #f0fdf4; color: #16a34a; }
    .theme-docs .icon-spot   { background: #eff6ff; color: #2563eb; }
    .theme-agenda .icon-spot { background: #fff7ed; color: #ea580c; }
    .theme-users .icon-spot  { background: #fef2f2; color: #dc2626; }

    .card-head { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; }
    .card-body-text { font-size: 0.9rem; color: #64748b; line-height: 1.5; margin-bottom: 1.5rem; }

    .btn-action {
        display: inline-flex;
        align-items: center;
        font-weight: 600;
        font-size: 0.85rem;
        color: #475569;
        transition: color 0.2s;
    }
    .feature-card:hover .btn-action { color: var(--primary); }

    /* 5. FOOTER */
    .dashboard-footer {
        text-align: center;
        padding: 2rem 0;
        color: #94a3b8;
        font-size: 0.85rem;
        margin-top: 3rem;
        border-top: 1px solid #e2e8f0;
    }

    /* Utility */
    .status-dot { height: 8px; width: 8px; background-color: #22c55e; border-radius: 50%; display: inline-block; }

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
                <div class="fw-bold fs-7 text-dark"><?= h($nombreOficina) ?></div>
            </div>
            <a href="<?= $APP_ROOT ?>/logout.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3" style="font-size: 0.8rem;">
                Salir
            </a>
        </div>
    </nav>

    <header class="hero-pattern">
        <div class="container hero-content">
            <div class="d-inline-block avatar-ring mb-3">
                <img src="<?= $avatarUrl ?>" alt="Avatar" class="avatar-img">
            </div>
            
            <h2 class="h3 fw-bold text-white mb-1">Hola, <?= h(explode(' ', trim($nombreOficina))[0]) ?></h2>
            
            <?php if (!empty($descripcion)): ?>
                <p class="text-white text-opacity-75 small mx-auto mt-2 mb-0" style="max-width: 500px;">
                    "<?= h($descripcion) ?>"
                </p>
            <?php endif; ?>

            <?php if ($msg): ?>
                <div class="alert alert-success d-inline-flex align-items-center py-1 px-3 mt-3 mb-0 rounded-pill small border-0 shadow text-dark bg-white">
                    <i class="bi bi-check-circle-fill me-2 text-success"></i> <?= h($msg) ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="container px-3">
        <div class="info-strip">
            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value"><i class="bi bi-calendar-event text-muted me-1 small"></i> <?= $fechaHoy ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado</div>
                <div class="info-value"><span class="status-dot me-1"></span> En línea</div>
            </div>
            <div class="info-item">
                <div class="info-label">Rol</div>
                <div class="info-value text-uppercase"><?= h($rol) ?></div>
            </div>
            <div class="info-item d-none d-md-block">
                <div class="info-label">Acceso</div>
                <div class="info-value">Seguro SSL</div>
            </div>
        </div>
    </div>

    <div class="cards-container">
        <div class="row g-4 justify-content-center">
            
            <div class="col-md-6 col-lg-3">
                <a href="oficina.php" class="feature-card theme-config">
                    <div class="icon-spot">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <div class="card-head">Configuración</div>
                    <div class="card-body-text">
                        Ajusta tu perfil, cambia el logo y personaliza la apariencia.
                    </div>
                    <span class="btn-action">Editar perfil <i class="bi bi-arrow-right ms-2"></i></span>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="documentos.php" class="feature-card theme-docs">
                    <div class="icon-spot">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div class="card-head">Documentos</div>
                    <div class="card-body-text">
                        Accede a tus contratos, presupuestos y archivos seguros.
                    </div>
                    <span class="btn-action">Gestionar archivos <i class="bi bi-arrow-right ms-2"></i></span>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="agenda.php" class="feature-card theme-agenda">
                    <div class="icon-spot">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div class="card-head">Agenda</div>
                    <div class="card-body-text">
                        Visualiza tus próximas reuniones, eventos y vencimientos.
                    </div>
                    <span class="btn-action">Ver calendario <i class="bi bi-arrow-right ms-2"></i></span>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <?php if ($rol === 'admin'): ?>
                    <a href="usuarios.php" class="feature-card theme-users">
                        <div class="icon-spot">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="card-head">Usuarios</div>
                        <div class="card-body-text">
                            Controla quién tiene acceso a la plataforma y sus roles.
                        </div>
                        <span class="btn-action">Administrar <i class="bi bi-arrow-right ms-2"></i></span>
                    </a>
                <?php else: ?>
                    <div class="feature-card bg-light" style="cursor: not-allowed; opacity: 0.7;">
                        <div class="icon-spot bg-secondary text-white">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <div class="card-head text-muted">Usuarios</div>
                        <div class="card-body-text text-muted">
                            Esta sección está reservada para administradores.
                        </div>
                        <span class="btn-action text-muted">Bloqueado <i class="bi bi-lock ms-2"></i></span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <footer class="dashboard-footer">
        <div class="container">
            <p class="mb-0">© <?= date('Y') ?> OVNI Panel. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>