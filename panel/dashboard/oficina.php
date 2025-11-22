<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------------------------------------------------
   CONFIGURACIÓN DE RUTAS (CRÍTICO PARA ESTA UBICACIÓN)
   Archivo: /panel/dashboard/oficina.php
   ---------------------------------------------------------
*/

// 1. Calculamos la ruta web base (URL) para que el CSS/imágenes carguen bien.
// Partimos de: /OVNI/panel/dashboard
// Subimos 1 nivel: /OVNI/panel
// Subimos 2 niveles: /OVNI
$ruta_web_actual = dirname($_SERVER['SCRIPT_NAME']); 
$ruta_web_raiz   = dirname(dirname($ruta_web_actual)); 
$BASE = rtrim(str_replace('\\', '/', $ruta_web_raiz), '/') . '/';

// 2. Incluimos funciones.php subiendo 2 carpetas físicamente en el disco.
require_once __DIR__ . '/../../funciones.php';


define('INACTIVITY_LIMIT', 300); // 5 minutos
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > INACTIVITY_LIMIT)) {
    session_unset();
    session_destroy();
    header("Location: {$BASE}login.php?expired=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();


/* ------- Parches por si faltan funciones (Backup) ------- */
if (!function_exists('actualizar_avatar_oficina')) {
    function actualizar_avatar_oficina(int $id_usuario, string $nombre_archivo): bool {
        $nombre_archivo = basename($nombre_archivo);
        if ($nombre_archivo === '') return false;
        $conn = conectar(); // Usa la conexión de funciones.php
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
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/* ---------------- VERIFICAR SESIÓN ---------------- */
if (!isset($_SESSION['usuario'])) {
    header("Location: {$BASE}login.php");
    exit;
}

$user  = $_SESSION['usuario'];
$flash = "";

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------------- PROCESAR FORMULARIOS ---------------- */

// CAMBIAR NOMBRE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_nombre') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } else {
        $nuevo = trim($_POST['nombre_oficina'] ?? '');
        if (actualizar_nombre_oficina((int)$user['id_usuario'], $nuevo)) {
            // Refrescamos datos de sesión
            if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                $_SESSION['usuario'] = $user = $u;
            }
            $_SESSION['flash_success'] = 'Nombre actualizado correctamente.';
            header("Location: {$BASE}panel/dashboard/dashboard.php?updated=name");
            exit;
        } else {
            $flash = '<div class="alert alert-warning">El nombre debe tener entre 1 y 100 caracteres.</div>';
        }
    }
}

// CAMBIAR AVATAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_avatar') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $flash = '<div class="alert alert-danger">Error al subir archivo. Código: ' . $_FILES['avatar']['error'] . '</div>';
    } else {
        $tmp  = $_FILES['avatar']['tmp_name'];
        $name = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','gif','webp'];

        if (!in_array($ext, $permitidas, true)) {
            $flash = '<div class="alert alert-danger">Solo se permiten imágenes (JPG, PNG, GIF, WEBP).</div>';
        } else {
            // RUTA DE DESTINO: Subimos 2 niveles para llegar a OVNI/assets/img
            $dir = __DIR__ . '/../../assets/img';
            
            if (!is_dir($dir)) { 
                @mkdir($dir, 0775, true); 
            }

            $nuevoNombre = 'avatar_' . (int)$user['id_usuario'] . '.' . $ext;
            $destino     = $dir . '/' . $nuevoNombre;

            if (!move_uploaded_file($tmp, $destino)) {
                $flash = '<div class="alert alert-danger">Error de permisos: No se pudo mover el archivo a assets/img.</div>';
            } else {
                @chmod($destino, 0644);
                if (actualizar_avatar_oficina((int)$user['id_usuario'], $nuevoNombre)) {
                    if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
                        $_SESSION['usuario'] = $user = $u;
                    }
                    $_SESSION['flash_success'] = 'Avatar actualizado.';
                    header("Location: {$BASE}panel/dashboard/dashboard.php?updated=avatar");
                    exit;
                } else {
                    $flash = '<div class="alert alert-danger">Error SQL al guardar avatar.</div>';
                }
            }
        }
    }
}
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
    .avatar-sm { width:80px; height:80px; object-fit:cover; border-radius:50%; border: 2px solid #ddd; }
  </style>
</head>
<body>

<div class="container py-4" style="max-width:720px">
  <?php if ($flash) { echo $flash; } ?>

  <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded shadow-sm">
    <img src="assets/img/<?= h($user['avatar_usuario'] ?? 'noavatar.png') ?>" alt="Avatar" class="avatar-sm">
    <div>
      <h1 class="h3 mb-0">Bienvenido a <?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></h1>
      <small class="text-muted"><?= h($user['email_usuario'] ?? '') ?></small>
    </div>
    <div class="ms-auto">
      <a class="btn btn-sm btn-outline-danger" href="logout.php">Salir</a>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">Nombre de la Oficina</div>
    <div class="card-body">
      <form method="post" class="row g-2" action="">
        <input type="hidden" name="accion" value="cambiar_nombre">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="col-12 col-md-9">
          <input type="text" name="nombre_oficina" value="<?= h($user['nombre_oficina'] ?? '') ?>" maxlength="100" required class="form-control" placeholder="Ej: Oficina Central">
        </div>
        <div class="col-12 col-md-3 d-grid">
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Imagen de Perfil</div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" class="row g-2" action="">
        <input type="hidden" name="accion" value="cambiar_avatar">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="col-12 col-md-9">
          <input type="file" name="avatar" accept="image/*" required class="form-control">
        </div>
        <div class="col-12 col-md-3 d-grid">
          <button class="btn btn-secondary">Subir</button>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-4">
    <a class="btn btn-success w-100" href="panel/dashboard/dashboard.php">Volver al Dashboard</a>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>