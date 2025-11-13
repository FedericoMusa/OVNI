<?php
$pageTitle = 'Inicio';
require_once __DIR__ . '/partials/header.php';
?>
<header class="py-4">
  <h1 class="text-center titulo-ovni">Bienvenido a OVNI</h1>
  <p class="text-center">TU OFICINA VIRTUAL</p>
  <div class="text-center">
    <a href="registro.php" class="btn btn-primary me-2">Registrarse</a>
    <a href="login.php" class="btn btn-success">Iniciar Sesión</a>
  </div>
</header>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary"></nav>

<div class="logo-container text-center my-4">
  <img src="assets/img/logo.png" alt="Logo OVNI" class="logo-ovni">
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
