<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ---------------------------------------------------------
 * CONFIGURACIÓN DE RUTAS (Simplificada)
 * Archivo: /OVNI/login.php
 * ---------------------------------------------------------
 */

// 1. Incluimos funciones.php que está en la misma carpeta (Raíz)
if (file_exists(__DIR__ . '/funciones.php')) {
    require_once __DIR__ . '/funciones.php';
} else {
    die("<h1>Error Crítico</h1><p>No se encuentra el archivo <code>funciones.php</code> en la carpeta raíz (OVNI).</p>");
}

// 2. Definimos la raíz de la APP para los enlaces
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$APP_ROOT   = rtrim(dirname($scriptName), '/'); // /OVNI


/* ---------------- LÓGICA DE LOGIN ---------------- */

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['clave'] ?? '';

    // Asumimos que la función login_usuario existe en funciones.php
    $user = login_usuario($email, $clave);

    if ($user) {
        $_SESSION['usuario'] = $user;
        
        // REDIRECCIÓN CORRECTA:
        // Vamos hacia /OVNI/panel/dashboard/dashboard.php
        $redirectUrl = ($APP_ROOT !== '' ? $APP_ROOT : '') . '/panel/dashboard/dashboard.php';
        
        header("Location: " . $redirectUrl, true, 302);
        exit;
    } else {
        $mensaje = "Correo o contraseña incorrectos.";
    }
}

// Opcional: Si usas partials, asegúrate de que existan. 
// Si no, puedes comentar estas líneas.
$pageTitle = 'Iniciar sesión';
if (file_exists(__DIR__ . '/partials/header.php')) {
    require_once __DIR__ . '/partials/header.php';
} else {
    // Header mínimo por si no existe el partial
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="assets/css/bootstrap.min.css"><title>Login</title></head><body class="bg-light">';
}
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    
    <div class="text-center mb-3">
      <!-- Asegúrate de tener logo.png en /OVNI/assets/img/ -->
      <img src="assets/img/logo.png" alt="Logo OVNI" class="logo-ovni mb-2" style="width: 70px; object-fit:contain;" onerror="this.style.display='none'">
      <h2 class="mb-2 fw-bold text-primary">Iniciar Sesión</h2>
      <p class="text-muted mb-3">Accedé a tu oficina virtual</p>
    </div>

    <form method="POST" action="">
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required autofocus>
      </div>
      
      <div class="mb-3 position-relative">
        <input type="password" name="clave" id="login_clave" class="form-control" placeholder="Contraseña" required>
        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2 border-0"
                onclick="togglePassword('login_clave', this)" style="background:transparent;">👁️</button>
      </div>
      
      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>

    <?php if ($mensaje): ?>
      <div class="alert alert-danger mt-3 py-2 text-center" role="alert">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="index.php" class="text-decoration-none">← Volver al inicio</a>
    </div>
  </div>
</div>

<script>
function togglePassword(idCampo, boton) {
  const campo = document.getElementById(idCampo);
  const visible = campo.type === "text";
  campo.type = visible ? "password" : "text";
  // Cambia el ícono si quieres
  boton.textContent = visible ? "👁️" : "🙈";
}
</script>

<?php 
if (file_exists(__DIR__ . '/partials/footer.php')) {
    require_once __DIR__ . '/partials/footer.php';
} else {
    echo '<script src="assets/js/bootstrap.bundle.min.js"></script></body></html>';
}
?>