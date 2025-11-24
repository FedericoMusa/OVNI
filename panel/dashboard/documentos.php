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

// Datos Visuales
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$avatarUrl     = $APP_ROOT . '/assets/img/' . ($user['avatar_usuario'] ?? 'noavatar.png');
$colorAcento   = $user['color_tema'] ?? '#009640';

// 3. PROCESAMIENTO DE SUBIDA
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc'])) {
    $tipoDoc = $_POST['tipo_doc'] ?? ''; 
    $archivo = $_FILES['archivo_doc'];

    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];

        if (!in_array($ext, $permitidos)) {
            $msg = "Formato no permitido. Solo PDF, Office o Imágenes.";
            $msgType = 'warning';
        } else {
            $subcarpeta = ($tipoDoc === 'contrato') ? 'contratos' : 'presupuestos';
            $dirDestino = __DIR__ . "/../../assets/docs/{$subcarpeta}";
            
            if (!is_dir($dirDestino)) mkdir($dirDestino, 0777, true);

            $nombreFinal = $user['id_usuario'] . '_' . time() . '_' . basename($archivo['name']);
            
            if (move_uploaded_file($archivo['tmp_name'], $dirDestino . '/' . $nombreFinal)) {
                $msg = "Archivo subido correctamente a " . ucfirst($subcarpeta);
                $msgType = 'success';
            } else {
                $msg = "Error al mover el archivo.";
                $msgType = 'danger';
            }
        }
    } else {
        $msg = "Error en la carga. Código: " . $archivo['error'];
        $msgType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Documentos | <?= h($nombreOficina) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    /* --- MISMO SISTEMA DE DISEÑO QUE DASHBOARD --- */
    :root {
        --bg-body: #f4f6f8;
        --hero-bg: #1e293b;
        --primary: <?= $colorAcento ?>;
        --text-main: #334155;
        --text-muted: #64748b;
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

    /* 2. HERO TEXTURIZADO */
    .hero-pattern {
        background-color: var(--hero-bg);
        background-image: radial-gradient(#ffffff 1px, transparent 1px);
        background-size: 30px 30px;
        padding: 3rem 0 7rem 0;
        color: white;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    
    .hero-pattern::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
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
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 1.5rem;
    }
    .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }

    /* 3. CONTENEDOR SOLAPADO */
    .content-overlap {
        max-width: 1100px;
        margin: -4rem auto 3rem auto;
        padding: 0 20px;
        position: relative;
        z-index: 10;
    }

    /* 4. TARJETAS ESTILO DASHBOARD */
    .doc-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 2rem;
        height: 100%;
        transition: transform 0.2s;
        position: relative;
        overflow: hidden;
    }
    
    .doc-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    /* Borde superior de color por tipo */
    .card-contrato { border-top: 4px solid #3b82f6; } /* Azul */
    .card-presupuesto { border-top: 4px solid #10b981; } /* Verde */

    .icon-circle {
        width: 56px; height: 56px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
    }

    .bg-icon-blue { background: #eff6ff; color: #3b82f6; }
    .bg-icon-green { background: #ecfdf5; color: #10b981; }

    .card-title { font-size: 1.2rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem; }
    
    .download-link {
        display: inline-block;
        font-size: 0.85rem;
        color: #64748b;
        text-decoration: none;
        border: 1px dashed #cbd5e1;
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        transition: all 0.2s;
    }
    .download-link:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #334155;
    }

    /* Formulario integrado */
    .file-drop-zone {
        border: 2px dashed #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .file-drop-zone:hover { border-color: #cbd5e1; }

    .btn-action {
        width: 100%;
        padding: 0.7rem;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        color: white;
        margin-top: 1rem;
        transition: opacity 0.2s;
    }
    .btn-action:hover { opacity: 0.9; color: white; }
    
    .btn-blue { background-color: #3b82f6; }
    .btn-green { background-color: #10b981; }

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
            <a href="<?= $APP_ROOT ?>/logout.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                Salir
            </a>
        </div>
    </nav>

    <header class="hero-pattern">
        <div class="container hero-content">
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
            <h2 class="h3 fw-bold text-white mb-1">Gestión Documental</h2>
            <p class="text-white text-opacity-75 small">Centraliza tus archivos y reportes importantes</p>
        </div>
    </header>

    <div class="content-overlap">
        
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> shadow-sm border-0 mb-4 d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div><?= h($msg) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-md-6">
                <div class="doc-card card-contrato">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle bg-icon-blue mb-0">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <div class="card-title mb-0">Contratos</div>
                            <div class="text-muted small">Acuerdos legales firmados</div>
                        </div>
                    </div>

                    <a href="<?= $APP_ROOT ?>/assets/docs/modelos/modelo_contrato.pdf" class="download-link w-100 text-center">
                        <i class="bi bi-download me-1"></i> Descargar Modelo PDF
                    </a>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipo_doc" value="contrato">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nuevo Documento</label>
                            <input class="form-control" type="file" name="archivo_doc" required>
                        </div>
                        
                        <button type="submit" class="btn-action btn-blue">
                            Subir Contrato
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="doc-card card-presupuesto">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle bg-icon-green mb-0">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div>
                            <div class="card-title mb-0">Presupuestos</div>
                            <div class="text-muted small">Cotizaciones y números</div>
                        </div>
                    </div>

                    <a href="<?= $APP_ROOT ?>/assets/docs/modelos/modelo_presupuesto.xlsx" class="download-link w-100 text-center">
                        <i class="bi bi-download me-1"></i> Descargar Modelo Excel
                    </a>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipo_doc" value="presupuesto">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nuevo Documento</label>
                            <input class="form-control" type="file" name="archivo_doc" required>
                        </div>
                        
                        <button type="submit" class="btn-action btn-green">
                            Subir Presupuesto
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>