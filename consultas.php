<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1) Incluir SIEMPRE con ruta absoluta
require_once __DIR__ . '/funciones.php';

// 2) Diagnóstico inmediato: ¿se cargó la función?
if (!function_exists('actualizar_nombre_oficina')) {
    // Mostrar qué archivos se incluyeron realmente:
    echo "<pre>FATAL: actualizar_nombre_oficina() no existe.\nArchivos incluidos:\n";
    foreach (get_included_files() as $f) echo $f . "\n";
    echo "</pre>";
    exit; // detener aquí para ver el listado
}

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['usuario'];

// Incluí con ruta absoluta para evitar problemas
require_once __DIR__ . '/funciones.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['usuario'];

// Ejemplo: manejar el POST para cambiar el nombre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_nombre') {
    $nuevo = $_POST['nombre_oficina'] ?? '';
    if (actualizar_nombre_oficina((int)$user['id_usuario'], $nuevo)) {
        // refrescar sesión
        if ($u = obtener_usuario_por_id((int)$user['id_usuario'])) {
            $_SESSION['usuario'] = $user = $u;
        }
        $flash = 'Nombre actualizado.';
    } else {
        $flash = 'No se pudo actualizar (1-100 caracteres).';
    }
}

require_once "funciones.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Seguridad: token CSRF simple
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$user = $_SESSION['usuario'];
$flash = "";

// Manejo del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_nombre') {
    $token_ok = hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '');
    $nuevo = $_POST['nombre_oficina'] ?? '';

    if (!$token_ok) {
        $flash = '<div class="alert alert-danger">Sesión expirada. Recargá la página.</div>';
    } elseif (actualizar_nombre_oficina((int)$user['id_usuario'], $nuevo)) {
        // Refrescar usuario en sesión para ver el cambio al instante
        if ($actualizado = obtener_usuario_por_id((int)$user['id_usuario'])) {
            $_SESSION['usuario'] = $user = $actualizado;
        }
        $flash = '<div class="alert alert-success">Nombre de la oficina actualizado.</div>';
    } else {
        $flash = '<div class="alert alert-warning">No se pudo actualizar. Verificá el nombre (1-100 caracteres).</div>';
    }
}

// Base para rutas relativas (p.ej. /OVNI/)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

// Avatar con fallback
$avatar = 'noavatar.png';
if (!empty($user['avatar_usuario'])) {
    $cand = basename($user['avatar_usuario']);
    if (is_file(__DIR__ . "/assets/img/$cand")) {
        $avatar = $cand;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($user['nombre_oficina'] ?? 'Mi Oficina') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= htmlspecialchars($BASE) ?>">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="card shadow-lg p-4" style="max-width: 480px; width: 100%;">
    <div class="text-center mb-3">
      <img src="assets/img/<?= htmlspecialchars($avatar) ?>" class="avatar mb-2" alt="Avatar" style="width: 90px;">
      <h2 class="mb-2 fw-bold" style="color:#0d6efd;">
        <?= htmlspecialchars($user['nombre_oficina'] ?? 'Mi Oficina') ?>
      </h2>
      <p class="text-muted mb-3">¡Bienvenido, <?= htmlspecialchars($user['email_usuario'] ?? '') ?>!</p>
    </div>

    <?= $flash ?>

    <form method="post" class="mb-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
      <input type="hidden" name="accion" value="cambiar_nombre">

      <label class="form-label">Nombre de la oficina</label>
      <input
        type="text"
        name="nombre_oficina"
        class="form-control"
        maxlength="100"
        value="<?= htmlspecialchars($user['nombre_oficina'] ?? 'Mi Oficina') ?>"
        required
      >
      <div class="form-text">Hasta 100 caracteres. Ej: “OVNI — Estudio Musa”.</div>

      <button type="submit" class="btn btn-primary w-100 mt-3">Guardar</button>
    </form>

    <a href="logout.php" class="btn btn-outline-danger w-100">Cerrar sesión</a>
  </div>
</div>

<?php include "footer.php"; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
