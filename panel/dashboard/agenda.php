<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors','1');
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== Rutas =====
   $APP_ROOT = prefijo de la app ("" o "/OVNI"), calculado a partir de /panel/... */
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (preg_match('#^(.*?)/panel(?:/|$)#', $script, $m)) {
    $APP_ROOT = rtrim($m[1], '/'); // "/OVNI" o "" si corre en raíz
} else {
    $APP_ROOT = '';
}

/* ===== Seguridad: requiere login ===== */
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: $APP_ROOT/login.php");
    exit;
}

/* ===== Util ===== */
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$user          = $_SESSION['usuario'];
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$pageTitle     = 'Agenda — ' . $nombreOficina;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= h($pageTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $APP_ROOT ?>/assets/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

  <!-- Encabezado simple -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Agenda</h1>
    <a class="btn btn-outline-secondary" href="<?= $APP_ROOT ?>panel/dashboard/dashboard.php">← Volver al dashboard</a>
  </div>

  <!-- Contenido principal -->
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <p class="text-muted mb-3">
        Aquí irá el listado/calendario de actividades de la oficina.
      </p>

      <!-- Placeholder de estructura inicial -->
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Próximos eventos</h5>
              <ul class="list-unstyled mb-0">
                <li class="text-muted">— (sin eventos por ahora)</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title">Acciones rápidas</h5>
              <div class="d-grid gap-2">
                <button class="btn btn-primary" type="button" disabled>+ Nuevo evento (próximamente)</button>
                <button class="btn btn-outline-secondary" type="button" disabled>Importar desde .ics (próximamente)</button>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /.row -->

    </div>
  </div>

</div><!-- /.container -->

<script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
