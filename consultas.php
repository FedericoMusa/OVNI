<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Base URL (con barra final), ej: /OVNI/ */
$BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';

define('INACTIVITY_LIMIT', 300); // 5 minutos
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > INACTIVITY_LIMIT)) {
    session_unset();
    session_destroy();
    header("Location: {$BASE}login.php?expired=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once __DIR__ . '/funciones.php';

/* ------- Helpers opcionales ------- */
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/* ------- Parches si faltan en funciones.php ------- */
if (!function_exists('actualizar_avatar_oficina')) {
    function actualizar_avatar_oficina(int $id_usuario, string $nombre_archivo): bool {
        $nombre_archivo = basename($nombre_archivo);
        if ($nombre_archivo === '') return false;
        $conn = conectar();
        $sql  = "UPDATE usuarios SET avatar_usuario = ? WHERE id_usuario = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nombre_archivo, $id_usuario);
        $ok   = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }
}
if (!function_exists('actualizar_nombre_oficina')) {
    function actualizar_nombre_oficina(int $id_usuario, string $nuevo): bool {
        $nuevo = trim($nuevo);
        if ($nuevo === '' || mb_strlen($nuevo) > 100) return false;
        $conn = conectar();
        $sql  = "UPDATE usuarios SET nombre_oficina = ? WHERE id_usuario = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nuevo, $id_usuario);
        $ok   = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }
}
if (!function_exists('obtener_usuario_por_id')) {
    function obtener_usuario_por_id(int $id): ?array {
        $conn = conectar();
        $sql  = "SELECT id_usuario, email_usuario, nombre_oficina, avatar_usuario
                 FROM usuarios WHERE id_usuario = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();
        $conn->close();
        return $row;
    }
}
/* -------- FIN parches -------- */

if (!isset($_SESSION['usuario'])) {
    header("Location: {$BASE}login.php");
    exit;
}

$user  = $_SESSION['usuario'];
$flash = "";

/* ---------------- CSRF simple ---------------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------------- MANEJO DEL FORMULARIO ---------------- */

/* Cambiar nombre */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_nombre') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } else {
        $nuevo = trim($_POST['nombre_oficina'] ?? '');
        if (actualizar_nombre_oficina((int)$user['id_usuario'], $nuevo)) {
            if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                $_SESSION['usuario'] = $user = $u;
            }
            /* PRG → Dashboard */
            $_SESSION['flash_success'] = 'Nombre actualizado.';
            header("Location: {$BASE}panel/dashboard/dashboard.php?updated=name");
            exit;
        } else {
            $flash = '<div class="alert alert-warning">El nombre debe tener entre 1 y 100 caracteres.</div>';
        }
    }
}

/* Cambiar avatar */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_avatar') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $flash = '<div class="alert alert-danger">Error al subir archivo.</div>';
    } else {
        $tmp  = $_FILES['avatar']['tmp_name'];
        $name = $_FILES['avatar']['name'];

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $permitidas, true)) {
            $flash = '<div class="alert alert-danger">Formato no permitido. Usá JPG, JPEG, PNG, GIF o WEBP.</div>';
        } else {
            $dir = __DIR__ . '/assets/img';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

            $nuevoNombre = 'avatar_' . (int)$user['id_usuario'] . '.' . $ext;
            $destino     = $dir . '/' . $nuevoNombre;

            if (!move_uploaded_file($tmp, $destino)) {
                $flash = '<div class="alert alert-danger">No se pudo mover el archivo al destino.</div>';
            } else {
                // Opcional: normalizar permisos
                @chmod($destino, 0644);

                if (actualizar_avatar_oficina((int)$user['id_usuario'], $nuevoNombre)) {
                    if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                        $_SESSION['usuario'] = $user = $u;
                    }
                    /* PRG → Dashboard */
                    $_SESSION['flash_success'] = 'Avatar actualizado.';
                    header("Location: {$BASE}panel/dashboard/dashboard.php?updated=avatar");
                    exit;
                } else {
                    $flash = '<div class="alert alert-danger">No se pudo guardar el avatar en la base de datos.</div>';
                }
            }
        }
    }
}

/* ---------------- FIN LÓGICA PHP ---------------- */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= h($BASE) ?>">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <style>
    /* estilos pequeños locales */
    .avatar-sm { width:80px; height:80px; object-fit:cover; border-radius:50%; }
  </style>
</head>
<body>

<div class="container py-4" style="max-width:720px">
  <?php if ($flash) { echo $flash; } ?>

  <div class="d-flex align-items-center gap-3 mb-3">
    <img src="assets/img/<?= h($user['avatar_usuario'] ?? 'noavatar.png') ?>" alt="Avatar" class="avatar-sm">
    <div>
      <h1 class="h3 mb-1">Bienvenido a <?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></h1>
      <div class="text-muted">Usuario: <?= h($user['email_usuario'] ?? '') ?></div>
    </div>
    <div class="ms-auto">
      <a class="btn btn-outline-dark" href="<?= h($BASE) ?>logout.php" aria-label="Cerrar sesión">Cerrar sesión</a>
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
          <div class="form-text">Formatos permitidos: jpg, jpeg, png, gif, webp.</div>
        </div>
        <div class="col-12 col-md-3 d-grid">
          <button class="btn btn-secondary">Subir avatar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Salida (útil durante pruebas) -->
  <div class="mt-4 d-flex gap-2">
    <a class="btn btn-success" href="<?= h($BASE) ?>panel/dashboard/dashboard.php">Ir al panel</a>
    <a class="btn btn-outline-dark ms-auto" href="<?= h($BASE) ?>logout.php" aria-label="Cerrar sesión">Cerrar sesión</a>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
