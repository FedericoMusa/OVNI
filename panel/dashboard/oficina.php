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

// Helpers
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$flash = "";

// 3. PROCESAMIENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = '<div class="alert alert-danger shadow-sm border-0">Error de token. Recarga.</div>';
    } else {
        $accion = $_POST['accion'] ?? '';

        // GUARDAR DATOS
        if ($accion === 'guardar_datos') {
            $nuevoNombre = trim($_POST['nombre_oficina'] ?? '');
            $nuevaDesc   = trim($_POST['descripcion_oficina'] ?? '');
            $nuevoColor  = $_POST['color_tema'] ?? '#009640';

            if ($nuevoNombre === '') {
                $flash = '<div class="alert alert-warning shadow-sm border-0">El nombre es obligatorio.</div>';
            } else {
                $conn = conectar();
                $sql = "UPDATE usuarios SET nombre_oficina = ?, descripcion_oficina = ?, color_tema = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nuevoNombre, $nuevaDesc, $nuevoColor, $user['id_usuario']);
                $stmt->execute(); $stmt->close();
                $conn->close();

                $_SESSION['usuario'] = obtener_usuario_por_id((int)$user['id_usuario']);
                $user = $_SESSION['usuario'];
                $flash = '<div class="alert alert-success shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i>Cambios guardados.</div>';
            }
        }

        // SUBIR AVATAR
        if ($accion === 'subir_avatar') {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dir = __DIR__ . '/../../assets/img';
                    if (!is_dir($dir)) mkdir($dir, 0777, true);
                    
                    $newName = 'avatar_' . $user['id_usuario'] . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . '/' . $newName)) {
                        actualizar_avatar_oficina((int)$user['id_usuario'], $newName);
                        $_SESSION['usuario'] = obtener_usuario_por_id((int)$user['id_usuario']);
                        $user = $_SESSION['usuario'];
                        $flash = '<div class="alert alert-success shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i>Foto actualizada.</div>';
                    }
                } else {
                    $flash = '<div class="alert alert-warning shadow-sm border-0">Formato no válido.</div>';
                }
            }
        }
    }
}

// Variables
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$descActual    = $user['descripcion_oficina'] ?? '';
$colorActual   = $user['color_tema'] ?? '#009640';
$avatarUrl     = $APP_ROOT . '/assets/img/' . ($user['avatar_usuario'] ?? 'noavatar.png');

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Configuración | <?= h($nombreOficina) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    :root {
        --bg-body: #eaeff3;       /* Mismo gris que el dashboard */
        --bg-hero: #2c3e50;       /* Mismo azul oscuro */
        --card-bg: #ffffff;
        --primary-accent: <?= $colorActual ?>;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Roboto', sans-serif;
        color: #495057;
    }

    /* 1. NAVBAR EXACTA */
    .navbar-clean {
        background-color: #ffffff;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        padding: 0.8rem 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nav-logo { height: 32px; width: auto; }
    .nav-user-img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; }

    /* 2. HERO OSCURO (HEADER DE PÁGINA) */
    /* Esto es lo que faltaba: un fondo oscuro que conecte visualmente */
    .page-header {
        background: linear-gradient(135deg, #344767 0%, #1a2035 100%);
        padding: 3rem 0 6rem 0; /* Padding inferior grande para solapamiento */
        color: white;
        margin-bottom: -4rem; /* Solapamiento negativo */
        text-align: center;
    }

    /* 3. CONTENEDOR PRINCIPAL */
    .main-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 20px 3rem 20px;
        position: relative; /* Para estar encima del hero */
        z-index: 10;
    }

    /* 4. TARJETAS IDÉNTICAS AL DASHBOARD */
    .settings-card {
        background: var(--card-bg);
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); /* Misma sombra */
        height: 100%;
        overflow: hidden;
        border-top: 4px solid var(--primary-accent); /* Borde de color superior */
    }

    .card-header-clean {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #f0f2f5;
        font-weight: 600;
        font-size: 1.1rem;
        color: #344767;
    }

    .card-body-clean {
        padding: 2rem;
    }

    /* 5. INPUTS MATERIAL */
    .form-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #7b809a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .form-control {
        background-color: #ffffff;
        border: 1px solid #d2d6da;
        border-radius: 6px;
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
        color: #495057;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: var(--primary-accent);
        box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
    }

    /* Botón Principal */
    .btn-save {
        background-color: var(--primary-accent);
        border: none;
        color: white;
        padding: 0.7rem 2rem;
        border-radius: 6px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: opacity 0.2s;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    .btn-save:hover { opacity: 0.9; color: white; }

    /* Avatar Preview */
    .avatar-preview-box {
        width: 140px; height: 140px;
        position: relative;
        margin: 0 auto 1.5rem auto;
    }
    .avatar-img-preview {
        width: 100%; height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f0f2f5;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .btn-cam {
        position: absolute;
        bottom: 5px; right: 5px;
        background: var(--primary-accent);
        color: white;
        width: 38px; height: 38px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        border: 3px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

  </style>
</head>
<body>

    <nav class="navbar-clean">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= $APP_ROOT ?>/assets/img/logo.png" alt="OVNI" class="nav-logo">
            <span class="fw-bold text-dark d-none d-sm-block fs-5">OVNI Panel</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <img src="<?= $avatarUrl ?>" alt="User" class="nav-user-img">
            <a href="<?= $APP_ROOT ?>/logout.php" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                <i class="bi bi-box-arrow-right text-danger"></i>
            </a>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h2 class="fw-bold mb-2">Configuración de Cuenta</h2>
            <p class="opacity-75 small mb-0">Personaliza la identidad visual de tu oficina</p>
            
            <div class="mt-3">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-4 border-opacity-25">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="main-container">
        
        <?php if ($flash) echo $flash; ?>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-header-clean">
                        <i class="bi bi-pencil-square me-2 text-muted"></i> Información
                    </div>
                    <div class="card-body-clean">
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="accion" value="guardar_datos">
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre Oficina</label>
                                    <input type="text" name="nombre_oficina" class="form-control" value="<?= h($nombreOficina) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Color de Tema</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_tema" value="<?= h($colorActual) ?>" title="Elige color">
                                        <input type="text" class="form-control bg-light" value="Color de botones" disabled>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Lema / Descripción</label>
                                    <textarea name="descripcion_oficina" class="form-control" rows="3" placeholder="Ej: Especialistas en..."><?= h($descActual) ?></textarea>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn-save">
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="settings-card">
                    <div class="card-header-clean">
                        <i class="bi bi-camera me-2 text-muted"></i> Imagen
                    </div>
                    <div class="card-body-clean text-center">
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="accion" value="subir_avatar">

                            <div class="avatar-preview-box">
                                <img src="<?= $avatarUrl ?>" id="previewImg" class="avatar-img-preview">
                                <label for="fileAvatar" class="btn-cam" title="Subir foto">
                                    <i class="bi bi-cloud-upload"></i>
                                </label>
                            </div>
                            
                            <input type="file" name="avatar" id="fileAvatar" class="d-none" accept="image/*" onchange="previewFile()">

                            <p class="text-muted small mb-3">JPG o PNG.<br>Se recomienda cuadrada.</p>

                            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                                Confirmar Foto
                            </button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewFile() {
            const preview = document.querySelector('#previewImg');
            const file    = document.querySelector('#fileAvatar').files[0];
            const reader  = new FileReader();
            reader.onload = function(e) { preview.src = e.target.result; }
            if (file) reader.readAsDataURL(file);
        }
    </script>
</body>
</html>