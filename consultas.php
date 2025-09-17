<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/funciones.php';
// Parche temporal: 
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
//PARCHE TEMPORAL 
if (!function_exists("actualizar_nombre_oficina")) {
    function actualizar_nombre_oficina(int $id_usuario, string $nuevo_nombre): bool {
        $nuevo_nombre = trim($nuevo_nombre);
        if ($nuevo_nombre === '' || mb_strlen($nuevo_nombre) > 100) {
            return false;
        }

        $conn = conectar();
        $sql  = "UPDATE usuarios SET nombre_oficina = ? WHERE id_usuario = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nuevo_nombre, $id_usuario);
        $ok   = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $ok;
    }
}

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$user  = $_SESSION['usuario'];
$flash = "";

/* ---------------- CSRF simple ---------------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------------- Helpers ---------------- */
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------------- MANEJO DEL FORMULARIO ---------------- */

/* Cambiar nombre */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_nombre') {
    // opcional: validar CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } else {
        $nuevo = trim($_POST['nombre_oficina'] ?? '');
        if (actualizar_nombre_oficina((int)$user['id_usuario'], $nuevo)) {
            if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                $_SESSION['usuario'] = $user = $u;
            }
            $flash = '<div class="alert alert-success">Nombre actualizado.</div>';
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

        // Validaciones básicas
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $permitidas, true)) {
            $flash = '<div class="alert alert-danger">Formato no permitido. Usa JPG, PNG, GIF o WEBP.</div>';
        } else {
            // Nombre único por usuario
            $nuevoNombre = 'avatar_' . (int)$user['id_usuario'] . '.' . $ext;
            $destino     = __DIR__ . '/assets/img/' . $nuevoNombre;

            if (move_uploaded_file($tmp, $destino)) {
                if (actualizar_avatar_oficina((int)$user['id_usuario'], $nuevoNombre)) {
                    if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                        $_SESSION['usuario'] = $user = $u;
                    }
                    $flash = '<div class="alert alert-success">Avatar actualizado.</div>';
                } else {
                    $flash = '<div class="alert alert-danger">No se pudo guardar el avatar en la base de datos.</div>';
                }
            } else {
                $flash = '<div class="alert alert-danger">No se pudo mover el archivo al destino.</div>';
            }
        }
    }
}

/* ---------------- FIN LÓGICA PHP ---------------- */

// Base para rutas relativas (p.ej. /OVNI/)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= h($BASE) ?>">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>

<div class="container py-4" style="max-width:720px">
  <?php if ($flash) { echo $flash; } ?>

  <div class="d-flex align-items-center gap-3 mb-3">
    <img src="assets/img/<?= h($user['avatar_usuario'] ?? 'noavatar.png') ?>" alt="Avatar" style="width:80px; height:80px; object-fit:cover; border-radius:50%;">
    <div>
      <h1 class="h3 mb-1">Bienvenido a <?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></h1>
      <div class="text-muted">Usuario: <?= h($user['email_usuario'] ?? '') ?></div>
    </div>
  </div>

  <!-- Form cambiar nombre -->
  <div class="card mb-3">
    <div class="card-body">
      <h2 class="h5 mb-3">Cambiar nombre de la Oficina</h2>
      <form method="post" class="row g-2">
        <input type="hidden" name="accion" value="cambiar_nombre">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="col-12 col-md-9">
          <input type="text" name="nombre_oficina" value="<?= h($user['nombre_oficina'] ?? '') ?>" maxlength="100" required class="form-control">
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
      <form method="post" enctype="multipart/form-data" class="row g-2">
        <input type="hidden" name="accion" value="cambiar_avatar">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="col-12 col-md-9">
          <input type="file" name="avatar" accept="image/*" required class="form-control">
          <div class="form-text">Formatos permitidos: jpg, jpeg, png, gif, webp.</div>
        </div>
        <div class="col-12 col-md-3 d-grid">
          <button class="btn btn-secondary">Subir avatar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
