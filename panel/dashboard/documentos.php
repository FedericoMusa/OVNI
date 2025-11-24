<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

/**
 * 1. CONFIGURACIÓN DE RUTAS
 */
// Calculamos la raíz del proyecto web (/OVNI)
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']); 
$ruta_raiz   = dirname(dirname($ruta_actual));   
$APP_ROOT    = rtrim(str_replace('\\', '/', $ruta_raiz), '/'); 
// $APP_ROOT ahora vale "/OVNI"

require_once __DIR__ . '/../../funciones.php';

if (!isset($_SESSION['usuario'])) { 
    header("Location: {$APP_ROOT}/login.php"); 
    exit; 
}

$user = $_SESSION['usuario'];
$msg  = '';
$msgType = 'success'; 

/**
 * 2. LÓGICA DE SUBIDA DE ARCHIVOS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc'])) {
    
    $tipoDoc = $_POST['tipo_doc'] ?? ''; 
    $archivo = $_FILES['archivo_doc'];

    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];

        if (!in_array($ext, $permitidos)) {
            $msg = "Formato no permitido. Solo PDF, Word, Excel o Imágenes.";
            $msgType = 'danger';
        } else {
            // Define carpeta: contratos o presupuestos
            $subcarpeta = ($tipoDoc === 'contrato') ? 'contratos' : 'presupuestos';
            
            // Ruta física en el disco
            $dirDestino = __DIR__ . "/../../assets/docs/{$subcarpeta}";
            
            if (!is_dir($dirDestino)) {
                mkdir($dirDestino, 0777, true);
            }

            $nombreFinal = $user['id_usuario'] . '_' . time() . '_' . basename($archivo['name']);
            $rutaFinal   = $dirDestino . '/' . $nombreFinal;

            if (move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
                $msg = "El documento se subió correctamente a la carpeta de " . strtoupper($subcarpeta);
            } else {
                $msg = "Error al mover el archivo. Verificá permisos.";
                $msgType = 'danger';
            }
        }
    } else {
        $msg = "Error al subir archivo. Código: " . $archivo['error'];
        $msgType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentos e Incidentes</title>
    
    <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
    
    <style>
        .icon-box {
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">OVNI Panel</a>
    <div class="ms-auto text-white small">
        Usuario: <?= htmlspecialchars($user['email_usuario'] ?? '') ?>
    </div>
  </div>
</nav>

<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">Gestión de Documentos</h1>
            <p class="text-muted mb-0">Subí contratos y presupuestos de forma organizada.</p>
        </div>
        <a href="<?= $APP_ROOT ?>/panel/dashboard/dashboard.php" class="btn btn-outline-dark">
            &larr; Volver al Dashboard
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm" role="alert">
            <strong>Estado:</strong> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <h4 class="card-title h5 mb-0 fw-bold">Contratos</h4>
                    </div>
                    
                    <p class="text-muted small">
                        Subí aquí los contratos firmados. <br>
                        <a href="<?= $APP_ROOT ?>/assets/docs/modelos/modelo_contrato.pdf" class="text-decoration-none fw-bold">Descargar modelo PDF &darr;</a>
                    </p>

                    <hr class="opacity-25 my-4">

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipo_doc" value="contrato">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Subir archivo</label>
                            <input class="form-control" type="file" name="archivo_doc" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Guardar Contrato</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <h4 class="card-title h5 mb-0 fw-bold">Presupuestos</h4>
                    </div>

                    <p class="text-muted small">
                        Cargá las cotizaciones y números. <br>
                        <a href="<?= $APP_ROOT ?>/assets/docs/modelos/modelo_presupuesto.xlsx" class="text-success text-decoration-none fw-bold">Descargar modelo Excel &darr;</a>
                    </p>

                    <hr class="opacity-25 my-4">

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipo_doc" value="presupuesto">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Subir archivo</label>
                            <input class="form-control" type="file" name="archivo_doc" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Guardar Presupuesto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>