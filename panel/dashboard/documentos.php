<?php
declare(strict_types=1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURACIÓN DE RUTAS (BLINDADO PARA WINDOWS)
define('DS', DIRECTORY_SEPARATOR);

// Raíz del proyecto: C:\xampp\htdocs\OVNI
$ROOT_PATH = dirname(__DIR__, 2); 

// Ruta Web
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']);
$ruta_raiz   = dirname(dirname($ruta_actual));
$APP_ROOT    = rtrim(str_replace('\\', '/', $ruta_raiz), '/');

require_once $ROOT_PATH . DS . 'funciones.php';

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
$colorAcento   = $user['color_tema'] ?? '#009640';

// 3. PROCESAMIENTO
$msg = '';
$msgType = 'success';

// Función auxiliar para verificar carpetas
function prepararCarpeta($rutaCompleta) {
    if (is_dir($rutaCompleta)) return true;
    if (file_exists($rutaCompleta)) return false;
    return mkdir($rutaCompleta, 0777, true);
}

// A) SUBIDA DE ARCHIVOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc'])) {
    $tipoDoc = $_POST['tipo_doc'] ?? ''; 
    $archivo = $_FILES['archivo_doc'];

    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];

        if (!in_array($ext, $permitidos)) {
            $msg = "Formato no permitido."; $msgType = 'warning';
        } else {
            $subcarpeta = ($tipoDoc === 'contrato') ? 'contratos' : 'presupuestos';
            $dirDestino = $ROOT_PATH . DS . 'assets' . DS . 'docs' . DS . $subcarpeta;

            if (prepararCarpeta($dirDestino)) {
                $nombreFinal = $user['id_usuario'] . '_' . time() . '_' . basename($archivo['name']);
                $rutaFinal = $dirDestino . DS . $nombreFinal;
                
                if (move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
                    $msg = "Archivo guardado en: assets/docs/$subcarpeta"; $msgType = 'success';
                } else {
                    $msg = "Error al mover archivo."; $msgType = 'danger';
                }
            } else {
                $msg = "Error: No se pudo acceder a la carpeta: $dirDestino"; $msgType = 'danger';
            }
        }
    } else {
        $msg = "Error carga: " . $archivo['error']; $msgType = 'danger';
    }
}

// B) CREACIÓN DE DOCUMENTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_doc') {
    $tituloDoc = trim($_POST['titulo_doc'] ?? 'Sin Titulo');
    $contenidoHtml = $_POST['contenido_doc'] ?? '';
    $tipoDoc = $_POST['tipo_doc_crear'] ?? 'contrato';

    if (empty($tituloDoc) || empty($contenidoHtml)) {
        $msg = "Faltan datos obligatorios."; $msgType = 'warning';
    } else {
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', $tituloDoc));
        $nombreFinal = $user['id_usuario'] . '_' . time() . '_' . $nombreLimpio . '.html';
        
        $subcarpeta = ($tipoDoc === 'contrato') ? 'contratos' : 'presupuestos';
        $dirDestino = $ROOT_PATH . DS . 'assets' . DS . 'docs' . DS . $subcarpeta;
        
        if (prepararCarpeta($dirDestino)) {
            $rutaArchivo = $dirDestino . DS . $nombreFinal;
            $plantilla = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>{$tituloDoc}</title>" .
                         "<style>body{font-family: sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; line-height: 1.6;}</style>" .
                         "</head><body><h1>{$tituloDoc}</h1>{$contenidoHtml}</body></html>";

            if (file_put_contents($rutaArchivo, $plantilla)) {
                $msg = "Documento creado correctamente."; $msgType = 'success';
            } else {
                $msg = "Error al escribir en disco."; $msgType = 'danger';
            }
        } else {
            $msg = "Fallo al crear carpeta: " . $dirDestino; $msgType = 'danger';
        }
    }
}

// C) ELIMINACIÓN DE DOCUMENTOS (NUEVO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_doc') {
    // basename() es vital para evitar que alguien envíe "../../../config.php"
    $archivoEliminar = basename($_POST['nombre_archivo'] ?? ''); 
    $carpetaEliminar = $_POST['carpeta_tipo'] ?? '';

    if (in_array($carpetaEliminar, ['contratos', 'presupuestos']) && !empty($archivoEliminar)) {
        // SEGURIDAD: Solo borrar si el archivo empieza con TU id de usuario
        $prefijoUsuario = $user['id_usuario'] . '_';
        
        if (str_starts_with($archivoEliminar, $prefijoUsuario)) {
            $rutaCompleta = $ROOT_PATH . DS . 'assets' . DS . 'docs' . DS . $carpetaEliminar . DS . $archivoEliminar;

            if (file_exists($rutaCompleta)) {
                // unlink es la función de PHP para borrar archivos
                if (unlink($rutaCompleta)) {
                    $msg = "Documento eliminado correctamente.";
                    $msgType = 'success';
                } else {
                    $msg = "Error al intentar borrar el archivo (permisos o bloqueo).";
                    $msgType = 'danger';
                }
            } else {
                $msg = "El archivo no existe.";
                $msgType = 'warning';
            }
        } else {
            $msg = "No tienes permiso para borrar este archivo.";
            $msgType = 'danger';
        }
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
    :root {
        --bg-body: #f4f6f8; --hero-bg: #1e293b; --primary: <?= $colorAcento ?>; --text-main: #334155;
    }
    body {
        background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main);
        min-height: 100vh; display: flex; flex-direction: column;
    }
    .navbar-pro {
        background: white; border-bottom: 1px solid #e2e8f0; padding: 0.75rem 2rem;
        display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;
    }
    .brand-group { display: flex; align-items: center; gap: 12px; }
    .nav-logo { height: 32px; width: auto; }
    .brand-text { font-weight: 700; color: #0f172a; font-size: 1.1rem; }
    .hero-pattern {
        background-color: var(--hero-bg); background-image: radial-gradient(#ffffff 1px, transparent 1px);
        background-size: 30px 30px; padding: 3rem 0 7rem 0; color: white; text-align: center; position: relative;
    }
    .btn-back {
        background: rgba(255,255,255,0.1); color: white; border-radius: 50px; padding: 6px 20px; text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px; margin-bottom: 1.5rem;
    }
    .content-overlap {
        max-width: 1100px; margin: -4rem auto 3rem auto; padding: 0 20px; position: relative; z-index: 10;
    }
    .doc-card {
        background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 2rem; height: 100%; transition: transform 0.2s;
    }
    .doc-card:hover { transform: translateY(-4px); }
    .card-contrato { border-top: 4px solid #3b82f6; } 
    .card-presupuesto { border-top: 4px solid #10b981; } 
    .icon-circle {
        width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; margin-bottom: 1.5rem;
    }
    .bg-icon-blue { background: #eff6ff; color: #3b82f6; }
    .bg-icon-green { background: #ecfdf5; color: #10b981; }
    .card-title { font-size: 1.2rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem; }
    .download-link {
        display: inline-block; font-size: 0.85rem; color: #64748b; text-decoration: none; border: 1px dashed #cbd5e1;
        padding: 8px 12px; border-radius: 8px; margin-bottom: 1.5rem;
    }
    .btn-action {
        width: 100%; padding: 0.7rem; border-radius: 8px; font-weight: 600; border: none; color: white; margin-top: 1rem;
    }
    .btn-blue { background-color: #3b82f6; }
    .btn-green { background-color: #10b981; }

    .modal-xl-custom { max-width: 95vw !important; margin: 1rem auto; }
    .modal-content { height: 92vh; display: flex; flex-direction: column; }
    .modal-body { flex: 1; background-color: #f8fafc; padding: 0 !important; overflow: hidden; }
    .tox-tinymce { border: none !important; height: 100% !important; }
    .tox-notifications-container, .tox-statusbar__branding { display: none !important; }
    .modal-backdrop { z-index: 1040 !important; }
    .modal { z-index: 1050 !important; }
  </style>
</head>
<body>

    <nav class="navbar-pro">
        <div class="brand-group">
            <img src="<?= $APP_ROOT ?>/assets/img/logo.png" alt="OVNI" class="nav-logo">
            <span class="brand-text d-none d-sm-block">OVNI Panel</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="fw-bold fs-7 text-dark d-none d-md-block"><?= h($nombreOficina) ?></div>
            <a href="<?= $APP_ROOT ?>/logout.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Salir</a>
        </div>
    </nav>

    <header class="hero-pattern">
        <div class="container hero-content">
            <a href="dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Volver al Dashboard</a>
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

        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalCrearDoc">
                <i class="bi bi-pencil-square me-2"></i> Redactar Nuevo
            </button>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="doc-card card-contrato">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle bg-icon-blue mb-0"><i class="bi bi-file-earmark-text"></i></div>
                        <div><div class="card-title mb-0">Contratos</div><div class="text-muted small">Acuerdos legales firmados</div></div>
                    </div>
                    <a href="<?= $APP_ROOT ?>/assets/docs/modelos/modelo_contrato.pdf" class="download-link w-100 text-center"><i class="bi bi-download me-1"></i> Descargar Modelo PDF</a>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipo_doc" value="contrato">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Subir Archivo</label>
                            <input class="form-control" type="file" name="archivo_doc" required>
                        </div>
                        <button type="submit" class="btn-action btn-blue">Subir Contrato</button>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="doc-card card-presupuesto">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle bg-icon-green mb-0"><i class="bi bi-calculator"></i></div>
                        <div><div class="card-title mb-0">Presupuestos</div><div class="text-muted small">Cotizaciones y números</div></div>
                    </div>
                    <a href="<?= $APP_ROOT ?>/assets/docs/modelos/modelo_presupuesto.xlsx" class="download-link w-100 text-center"><i class="bi bi-download me-1"></i> Descargar Modelo Excel</a>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipo_doc" value="presupuesto">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Subir Archivo</label>
                            <input class="form-control" type="file" name="archivo_doc" required>
                        </div>
                        <button type="submit" class="btn-action btn-green">Subir Presupuesto</button>
                    </form>
                </div>
            </div>
        </div>

        <h4 class="fw-bold text-dark mb-3">🗂️ Archivos Disponibles</h4>
        <div class="doc-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase">Nombre</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Tipo</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Fecha</th>
                            <th class="pe-4 py-3 text-end text-muted small fw-bold text-uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $carpetas = ['contratos' => 'Contrato', 'presupuestos' => 'Presupuesto'];
                        $hayArchivos = false;
                        foreach ($carpetas as $carpeta => $etiqueta) {
                            $rutaDir = $ROOT_PATH . DS . 'assets' . DS . 'docs' . DS . $carpeta;
                            if (is_dir($rutaDir)) {
                                $archivos = array_diff(scandir($rutaDir), ['.', '..']);
                                foreach ($archivos as $archivo) {
                                    $hayArchivos = true;
                                    $rutaWeb = "{$APP_ROOT}/assets/docs/{$carpeta}/{$archivo}";
                                    $esHtml = str_ends_with($archivo, '.html');
                                    $partes = explode('_', $archivo);
                                    $fecha = (count($partes) > 1 && is_numeric($partes[1])) ? date('d/m/Y H:i', (int)$partes[1]) : '-';
                                    $nombreVisual = (count($partes) > 2) ? implode(' ', array_slice($partes, 2)) : $archivo;
                                    $nombreVisual = str_replace(['.html', '.pdf', '.docx', '.xlsx'], '', $nombreVisual);
                        ?>
                                    <tr>
                                        <td class="ps-4">
                                            <i class="bi <?= $esHtml ? 'bi-filetype-html text-warning' : 'bi-file-earmark-text text-secondary' ?> fs-5 me-2"></i>
                                            <span class="fw-bold text-dark"><?= h($nombreVisual) ?></span>
                                        </td>
                                        <td><span class="badge <?= $carpeta === 'contratos' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' ?> rounded-pill"><?= $etiqueta ?></span></td>
                                        <td class="text-muted small"><?= $fecha ?></td>
                                        <td class="pe-4 text-end">
                                            <a href="<?= $rutaWeb ?>" target="_blank" class="btn btn-sm btn-light border" title="Ver"><i class="bi bi-eye"></i></a>
                                            <a href="<?= $rutaWeb ?>" download class="btn btn-sm btn-light border ms-1" title="Descargar"><i class="bi bi-download"></i></a>
                                            
                                            <?php if (str_starts_with($archivo, $user['id_usuario'] . '_')): ?>
                                                <form action="" method="POST" class="d-inline ms-1" onsubmit="return confirm('¿Estás seguro de eliminar este documento permanentemente?');">
                                                    <input type="hidden" name="accion" value="eliminar_doc">
                                                    <input type="hidden" name="nombre_archivo" value="<?= h($archivo) ?>">
                                                    <input type="hidden" name="carpeta_tipo" value="<?= h($carpeta) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                        <?php 
                                }
                            }
                        }
                        if (!$hayArchivos): 
                        ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No hay documentos aún.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCrearDoc" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl-custom modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <form method="POST" action="" class="d-flex flex-column h-100">
            <div class="modal-header bg-white border-bottom py-2 align-items-center">
              <div class="d-flex align-items-center gap-3 flex-grow-1">
                 <h5 class="modal-title fw-bold text-primary m-0"><i class="bi bi-file-richtext"></i> Editor</h5>
                 <input type="text" name="titulo_doc" class="form-control form-control-sm w-50" placeholder="Título (Ej: Contrato 2024)" required>
                 <select name="tipo_doc_crear" class="form-select form-select-sm w-auto">
                    <option value="contrato">Contrato</option>
                    <option value="presupuesto">Presupuesto</option>
                 </select>
              </div>
              <div class="d-flex gap-2 ms-3">
                 <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCerrarModal">Cancelar</button>
                 <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold"><i class="bi bi-save"></i> Guardar</button>
              </div>
            </div>
            <div class="modal-body">
              <input type="hidden" name="accion" value="crear_doc">
              <textarea id="editorContenido" name="contenido_doc"></textarea>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/g5gjli5kf8wmt416sficvhb14nwrirjh7q07sov6p8y65xyy/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
          const modalEl = document.getElementById('modalCrearDoc');
          const myModal = new bootstrap.Modal(modalEl);
          let editorIniciado = false;

          modalEl.addEventListener('shown.bs.modal', function () {
              if (!editorIniciado) {
                  tinymce.init({
                      selector: '#editorContenido',
                      height: '100%', menubar: true, resize: false,
                      plugins: ['advlist', 'autolink', 'lists', 'link', 'charmap', 'preview', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'table', 'wordcount', 'help'],
                      toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
                      content_style: 'body { font-family:Inter,sans-serif; font-size:16px; margin: 2rem; color: #333; }',
                      setup: function(editor) {
                          editor.on('init', function() {
                              const borrador = localStorage.getItem('ovni_doc_draft');
                              if (borrador) editor.setContent(borrador);
                          });
                          editor.on('keyup change', function() {
                              localStorage.setItem('ovni_doc_draft', editor.getContent());
                          });
                      }
                  });
                  editorIniciado = true;
              }
          });

          document.getElementById('btnCerrarModal').addEventListener('click', function() {
              if(confirm('¿Cerrar? El borrador se guarda automáticamente.')) myModal.hide();
          });

          modalEl.querySelector('form').addEventListener('submit', function() {
              localStorage.removeItem('ovni_doc_draft');
          });
      });
    </script>
</body>
</html>