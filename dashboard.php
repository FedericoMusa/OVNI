<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();

$BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if (!isset($_SESSION['usuario'])) {
    header("Location: $BASE/login.php");
    exit;
}

$user = $_SESSION['usuario'];

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Mensajes de éxito (por GET o por flash de sesión)
$msg = '';
if (isset($_SESSION['flash_success'])) {
    $msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
} elseif (($_GET['updated'] ?? '') === 'name') {
    $msg = 'Nombre de la oficina actualizado correctamente.';
} elseif (($_GET['updated'] ?? '') === 'avatar') {
    $msg = 'Avatar actualizado correctamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard — <?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/estilos.css">
</head>
<body class="body-dashboard">

<div class="container py-4">

  <?php if ($msg): ?>
    <div class="alert alert-success mb-4"><?= h($msg) ?></div>
  <?php endif; ?>

  <!-- Header -->
  <div class="card border-0 shadow-sm mb-4 ovni-header">
    <div class="card-body d-flex flex-column align-items-center text-center py-4">
      <img
        src="<?= $BASE ?>/assets/img/<?= h($user['avatar_usuario'] ?? 'noavatar.png') ?>"
        alt="Avatar"
        class="avatar"
        style="width:160px;height:160px;object-fit:cover;border-radius:50%;border:4px solid #fff;
               box-shadow:0 0 0 6px rgba(0,0,0,.03), 0 10px 25px rgba(0,0,0,.12);">
      <h1 class="display-6 fw-semibold mt-3 mb-1"><?= h($user['nombre_oficina'] ?? 'Mi Oficina') ?></h1>
      <div class="text-muted"><?= h($user['email_usuario'] ?? '') ?></div>
    </div>
    <div style="height:4px;background:#1e66ff;border-bottom-left-radius:16px;border-bottom-right-radius:16px;"></div>
  </div>

  <!-- Grid de módulos -->
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Configuración</h5>
          <p class="card-text">Editá nombre de oficina, avatar y preferencias.</p>
          <a class="btn btn-outline-primary" href="<?= $BASE ?>/oficina.php">Editar perfil de la oficina</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Documentos / Incidentes</h5>
          <p class="card-text">Acceso rápido a gestión de documentos, incidentes y reportes.</p>
          <a class="btn btn-outline-secondary" href="<?= $BASE ?>/panel/documentos.php">Entrar</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Agenda</h5>
          <p class="card-text">Revisá y organizá tu agenda.</p>
          <a class="btn btn-outline-secondary" href="<?= $BASE ?>/panel/agenda.php">Entrar</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Usuarios y Permisos</h5>
          <p class="card-text">Administra roles y accesos.</p>
          <a class="btn btn-outline-secondary" href="<?= $BASE ?>/panel/usuarios.php">Entrar</a>
        </div>
      </div>
    </div>
  </div>

</div><!-- /.container -->

<script src="<?= $BASE ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
