<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['usuario'];

// Calcula base del proyecto (p.ej. /OVNI/)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

// Avatar seguro con fallback y verificación en disco
$avatar = 'noavatar.png'; // corregido el nombre por defecto
if (!empty($user['avatar_usuario'])) {
    // evita traversal: solo nombre de archivo
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
  <title>Mi Oficina Virtual</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= htmlspecialchars($BASE) ?>">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    <div class="text-center mb-3">
      <img src="assets/img/<?= htmlspecialchars($avatar) ?>" class="avatar mb-2" alt="Avatar" style="width: 90px;">
      <h2 class="mb-2 fw-bold" style="color:#0d6efd;">Mi Oficina Virtual</h2>
      <p class="text-muted mb-3" style="font-size:1rem;">
        ¡Bienvenido, <?= htmlspecialchars($user['email_usuario'] ?? '') ?>!
      </p>
    </div>

    <div class="mb-3 text-center">
      <strong>Email:</strong> <?= htmlspecialchars($user['email_usuario'] ?? '') ?>
    </div>

    <div class="text-center mt-3">
      <a href="logout.php" class="btn btn-outline-danger w-100">Cerrar sesión</a>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
