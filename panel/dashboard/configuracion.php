<?php
declare(strict_types=1);
/**
 * panel/dashboard/oficina.php
 * Mejoras:
 * - rutas absolutas calculadas (APP_ROOT) para assets y redirects
 * - validación segura de CSRF
 * - validación y verificación MIME del avatar subido con finfo
 * - limpieza del avatar antiguo al reemplazar (si no es noavatar.png)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Calcular APP_ROOT (raíz de la app, p.ej. /OVNI o vacío si corre en raíz) */
$script = $_SERVER['SCRIPT_NAME'] ?? '/';
$baseDir = rtrim(str_replace('\\','/', dirname($script)), '/'); // e.g. /OVNI/panel/dashboard
$APP_ROOT = rtrim(preg_replace('#/panel(?:/.*)?$#', '', $baseDir), '/');
if ($APP_ROOT === '/') $APP_ROOT = '';
// Rutas de archivos en servidor
$docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$assetsWebPath = ($APP_ROOT ?: '') . '/panel/assets/img'; // ruta pública para IMG: /OVNI/panel/assets/img
$assetsFsPath  = $docRoot . $assetsWebPath;               // ruta absoluta en disco

require_once __DIR__ . '/../funciones.php'; // funciones centralizadas

/* helpers */
if (!function_exists('h')) {
    function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

/* PRG / Mensajes */
$flash = '';
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: " . ($APP_ROOT ?: '') . "/login.php");
    exit;
}
$user = $_SESSION['usuario'];

/* Manejo formulario: cambiar nombre */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_nombre') {
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], (string)$csrf)) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } else {
        $nuevo = trim((string)($_POST['nombre_oficina'] ?? ''));
        if (actualizar_nombre_oficina((int)$user['id_usuario'], $nuevo)) {
            if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                $_SESSION['usuario'] = $user = $u;
            }
            $_SESSION['flash_success'] = 'Nombre actualizado.';
            header("Location: " . ($APP_ROOT ?: '') . "/panel/dashboard/dashboard.php?updated=name");
            exit;
        } else {
            $flash = '<div class="alert alert-warning">El nombre debe tener entre 1 y 100 caracteres.</div>';
        }
    }
}

/* Manejo formulario: cambiar avatar */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_avatar') {
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], (string)$csrf)) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $flash = '<div class="alert alert-danger">Error al subir archivo.</div>';
    } else {
        $fileTmp  = $_FILES['avatar']['tmp_name'];
        $fileName = basename($_FILES['avatar']['name']);
        $fileSize = $_FILES['avatar']['size'] ?? 0;

        // Limitar tamaño razonable (ej. 4MB)
        $maxSize = 4 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            $flash = '<div class="alert alert-danger">El archivo es demasiado grande. Máx 4MB.</div>';
        } else {
            // Verificamos MIME real con finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($fileTmp);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];
            if (!isset($allowed[$mime])) {
                $flash = '<div class="alert alert-danger">Formato no permitido. Usá JPG, PNG, GIF o WEBP.</div>';
            } else {
                // Asegurar directorio de destino
                if (!is_dir($assetsFsPath)) {
                    @mkdir($assetsFsPath, 0775, true);
                }
                $ext = $allowed[$mime];
                $newName = 'avatar_' . (int)$user['id_usuario'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $assetsFsPath . '/' . $newName;

                if (!move_uploaded_file($fileTmp, $dest)) {
                    $flash = '<div class="alert alert-danger">No se pudo mover el archivo al destino.</div>';
                } else {
                    @chmod($dest, 0644);
                    // Actualizar DB
                    if (actualizar_avatar_oficina((int)$user['id_usuario'], $newName)) {
                        // Borrar avatar anterior si no es 'noavatar.png'
                        $old = $user['avatar_usuario'] ?? 'noavatar.png';
                        if ($old && $old !== 'noavatar.png') {
                            $oldPath = $assetsFsPath . '/' . basename($old);
                            if (is_file($oldPath)) { @unlink($oldPath); }
                        }
                        // Refrescar datos de sesión
                        if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                            $_SESSION['usuario'] = $user = $u;
                        }
                        $_SESSION['flash_success'] = 'Avatar actualizado.';
                        header("Location: " . ($APP_ROOT ?: '') . "/panel/dashboard/dashboard.php?updated=avatar");
                        exit;
                    } else {
                        // Borrar archivo subido si no se pudo guardar en BD
                        if (is_file($dest)) @unlink($dest);
                        $flash = '<div class="alert alert-danger">No se pudo guardar el avatar en la base de datos.</div>';
                    }
                }
            }
        }
    }
}

/* utilities para la vista */
$displayAvatar = $user['avatar_usuario'] ?? 'noavatar.png';
$avatarUrl = ($APP_ROOT ?: '') . '/panel/assets/img/' . rawurlencode($displayAvatar);
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= h($nombreOficina) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= h(($APP_ROOT ?: '') . '/assets/css/bootstrap.min.css') ?>">
  <style>
    .avatar-sm { width:80px; height:80px; object-fit:cover; border-radius:50%; }
  </style>
</head>
<body>
<div class="container py-4" style="max-width:720px">
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= h($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <?php if ($flash) { echo $flash; } ?>

  <div class="d-flex align-items-center gap-3 mb-3">
    <img src="<?= h($avatarUrl) ?>" alt="Avatar" class="avatar-sm">
    <div>
      <h1 class="h3 mb-1">Bienvenido a <?= h($nombreOficina) ?></h1>
      <div class="text-muted">Usuario: <?= h($user['email_usuario'] ?? '') ?></div>
    </div>
    <div class="ms-auto">
      <a class="btn btn-outline-dark" href="<?= h(($APP_ROOT ?: '') . '/logout.php') ?>" aria-label="Cerrar sesión">Cerrar sesión</a>
    </div>
  </div>

  <!-- Form cambiar nombre -->
  <div class="card mb-3">
    <div class="card-body">
      <h2 class="h5 mb-3">Cambiar nombre de la Oficina</h2>
      <form method="post" class="row g-2" action="">
        <input type="hidden" name="accion" value="cambiar_nombre">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="col-12 col-md-9">
          <input type="text" name="nombre_oficina" value="<?= h($user['nombre_oficina'] ?? '') ?>" maxlength="100" required class="form-control" aria-label="Nombre de la oficina">
        </div>
        <div class="col-12 col-md-3 d-grid">
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Form cambiar avatar -->
  <div class="card">
    <div class="card-body">
      <h2 class="h5 mb-3">Cambiar avatar</h2>
      <form method="post" enctype="multipart/form-data" class="row g-2" action="">
        <input type="hidden" name="accion" value="cambiar_avatar">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="col-12 col-md-9">
          <input type="file" name="avatar" accept="image/*" required class="form-control" aria-label="Seleccionar avatar">
          <div class="form-text">Formatos permitidos: jpg, jpeg, png, gif, webp. Máx 4MB.</div>
        </div>
        <div class="col-12 col-md-3 d-grid">
          <button class="btn btn-secondary">Subir avatar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-4 d-flex gap-2">
    <a class="btn btn-success" href="<?= h(($APP_ROOT ?: '') . '/panel/dashboard/dashboard.php') ?>">Ir al panel</a>
    <a class="btn btn-outline-dark ms-auto" href="<?= h(($APP_ROOT ?: '') . '/logout.php') ?>" aria-label="Cerrar sesión">Cerrar sesión</a>
  </div>
</div>

<script src="<?= h(($APP_ROOT ?: '') . '/assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>