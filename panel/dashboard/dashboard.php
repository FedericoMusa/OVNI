<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ----------------------------------------------------------------
 * CÁLCULO DE RUTAS (UBICACIÓN: /panel/dashboard/dashboard.php)
 * ----------------------------------------------------------------
 */
// 1. Directorio actual: /OVNI/panel/dashboard
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']); 

// 2. Raíz del proyecto: Subimos 2 niveles (/OVNI)
//    De 'dashboard' -> 'panel' -> 'OVNI'
$ruta_raiz = dirname(dirname($ruta_actual)); 

// 3. Definimos la constante de raíz web
$APP_ROOT = rtrim(str_replace('\\', '/', $ruta_raiz), '/');
// $APP_ROOT ahora vale "/OVNI"

/**
 * ----------------------------------------------------------------
 * SEGURIDAD: REQUIERE LOGIN
 * ----------------------------------------------------------------
 */
if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    empty($_SESSION['usuario']['id_usuario'])
) {
    $loginUrl = ($APP_ROOT !== '' ? $APP_ROOT : '') . '/login.php';
    header("Location: $loginUrl", true, 302);
    exit;
}

$user = $_SESSION['usuario'];

/**
 * Helper para escapar HTML
 */
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * MENSAJERÍA
 * Corrección del error Deprecated: FILTER_SANITIZE_STRING -> FILTER_SANITIZE_FULL_SPECIAL_CHARS
 */
$msg = '';
if (isset($_SESSION['flash_success'])) {
    $msg = (string)$_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
} else {
    // AQUÍ ESTÁ EL ARREGLO DEL ERROR:
    $updated = filter_input(INPUT_GET, 'updated', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
    
    if ($updated === 'name')   { $msg = 'Nombre de la oficina actualizado correctamente.'; }
    if ($updated === 'avatar') { $msg = 'Avatar actualizado correctamente.'; }
}

/**
 * AVATAR
 */
$avatarName = trim((string)($user['avatar_usuario'] ?? ''));
if ($avatarName === '') {
    $avatarName = 'noavatar.png';
}

// Comprobación física del archivo (Ruta de disco)
$docRoot  = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$avatarFs = $docRoot . $APP_ROOT . '/assets/img/' . $avatarName;

if (!is_file($avatarFs)) {
    $avatarName = 'noavatar.png';
}

$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$emailUsuario  = $user['email_usuario'] ?? '';
$title         = 'Dashboard — ' . $nombreOficina;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= h($title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="IE=edge">
  
  <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $APP_ROOT ?>/assets/css/estilos.css">
</head>
<body class="body-dashboard bg-light">

<div class="container py-4">

  <?php if ($msg): ?>
    <div class="alert alert-success mb-4" role="status"><?= h($msg) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm mb-4 ovni-header">
    <div class="card-body d-flex flex-column align-items-center text-center py-4">
      <img
        src="<?= $APP_ROOT ?>/assets/img/<?= h($avatarName) ?>"
        alt="Avatar de la oficina"
        class="avatar"
        loading="lazy"
        width="160" height="160"
        style="width:160px;height:160px;object-fit:cover;border-radius:50%;border:4px solid #fff;
               box-shadow:0 0 0 6px rgba(0,0,0,.03), 0 10px 25px rgba(0,0,0,.12);">
      <h1 class="display-6 fw-semibold mt-3 mb-1"><?= h($nombreOficina) ?></h1>
      <?php if ($emailUsuario): ?>
        <div class="text-muted" aria-label="Correo electrónico"><?= h($emailUsuario) ?></div>
      <?php endif; ?>
    </div>
    <div style="height:4px;background:#1e66ff;border-bottom-left-radius:16px;border-bottom-right-radius:16px;"></div>
  </div>

  <div class="row row-cols-1 row-cols-md-2 g-4">

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">Configuración</h5>
          <p class="card-text flex-grow-1">Editá nombre de oficina, avatar y preferencias.</p>
          <a class="btn btn-outline-primary mt-auto" href="<?= $APP_ROOT ?>/panel/dashboard/oficina.php">Editar perfil</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">Documentos / Incidentes</h5>
          <p class="card-text flex-grow-1">Gestión de documentos y reportes.</p>
          <a class="btn btn-outline-secondary mt-auto" href="<?= $APP_ROOT ?>/panel/dashboard/documentos.php">Entrar</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">Agenda</h5>
          <p class="card-text flex-grow-1">Revisá y organizá tu agenda.</p>
          <a class="btn btn-outline-secondary mt-auto" href="<?= $APP_ROOT ?>/panel/dashboard/agenda.php">Entrar</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">Usuarios y Permisos</h5>
          <p class="card-text flex-grow-1">Administrá roles y accesos.</p>
          <a class="btn btn-outline-secondary mt-auto" href="<?= $APP_ROOT ?>/panel/dashboard/usuarios.php">Entrar</a>
        </div>
      </div>
    </div>

  </div>

  <div class="d-flex gap-2 justify-content-end mt-4">
    <a class="btn btn-outline-dark" href="<?= $APP_ROOT ?>/logout.php" aria-label="Cerrar sesión">Cerrar sesión</a>
  </div>

</div>

<script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 