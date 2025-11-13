<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/funciones.php';

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST['email'] ?? '';
  $clave = $_POST['clave'] ?? '';
  $user  = login_usuario($email, $clave);
  if ($user) {
    $_SESSION['usuario'] = $user;
    header("Location: dashboard.php");
    exit;
  } else {
    $mensaje = "Usuario o contraseña incorrecto";
  }
}

$pageTitle = 'Iniciar sesión';
require_once __DIR__ . '/partials/header.php';
?>
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
      <div class="mb-3 position-relative">
        <input type="password" name="clave" id="login_clave" class="form-control" placeholder="Contraseña" required>
        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2"
                onclick="togglePassword('login_clave', this)">👁️</button>
      </div>
      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>

    <?php if ($mensaje): ?>
      <div class="alert alert-danger mt-3 py-2 text-center" style="font-size:.95em;">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="index.php" class="text-decoration-none" style="color:#0d6efd;">&larr; Volver al inicio</a>
    </div>
  </div>
</div>

<script>
function togglePassword(idCampo, boton) {
  const campo = document.getElementById(idCampo);
  const visible = campo.type === "text";
  campo.type = visible ? "password" : "text";
  boton.textContent = visible ? "👁️" : "🙈";
}
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
