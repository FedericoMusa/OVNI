<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "funciones.php";

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = login_usuario($_POST['email'] ?? '', $_POST['clave'] ?? '');
    if ($user) {
        $_SESSION['usuario'] = $user;
        header("Location: consultas.php");
        exit;
    } else {
        $mensaje = "Usuario o contraseña incorrecto";
    }
}

// calcula la base del proyecto (ej: /OVNI/)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Oficina Virtual - Iniciar sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= htmlspecialchars($BASE) ?>">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/estilos.css">
  <link rel="stylesheet" href="assets/css/animate.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    <div class="text-center mb-3">
      <img src="assets/img/logo.png" alt="Logo OVNI" class="logo-ovni mb-2" style="width: 70px;">
      <h2 class="mb-2 fw-bold" style="color:#0d55fd;">Iniciar Sesión</h2>
      <p class="text-muted mb-3">Accedé a tu oficina virtual</p>
    </div>

    <form method="POST" action="">
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
      </div>
      <div class="mb-3">
        <input type="password" name="clave" class="form-control" placeholder="Contraseña" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>

    <?php if ($mensaje): ?>
      <div class="alert alert-danger mt-3 py-2 text-center" style="font-size:.95em;">
        <?= $mensaje ?>
      </div>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="index.php" class="text-decoration-none" style="color:#0d6efd;">&larr; Volver al inicio</a>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
