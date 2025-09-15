<?php
// Calcula la base del proyecto p.ej. /OVNI/
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Oficina Virtual - Inicio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fija la base para TODAS las rutas relativas -->
  <base href="<?= htmlspecialchars($BASE) ?>">

  <!-- CSS locales (sin barra inicial) -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/estilos.css">
  <link rel="stylesheet" href="assets/css/animate.css">
</head>
<body>

  <header class="py-4">
    <h1 class="text-center titulo-ovni">Bienvenido a OVNI</h1>
    <p class="text-center">TU OFICINA VIRTUAL</p>
    <div class="text-center">
      <a href="registro.php" class="btn btn-primary me-2">Registrarse</a>
      <a href="login.php" class="btn btn-success">Iniciar Sesión</a>
    </div>
  </header>

  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <!-- ...navbar... -->
  </nav>

  <div class="logo-container text-center my-4">
    <img src="assets/img/logo.png" alt="Logo OVNI" class="logo-ovni">
  </div>

  <?php include "footer.php"; ?>

  <!-- JS (si usás componentes de Bootstrap que requieren JS) -->
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
