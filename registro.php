<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();                    // <-- necesario para flash
include "funciones.php";

$mensaje = "";

// PROCESO POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email  = isset($_POST['email'])  ? trim($_POST['email'])  : '';
    $clave  = isset($_POST['clave'])  ? (string)$_POST['clave']  : '';
    $clave2 = isset($_POST['clave2']) ? (string)$_POST['clave2'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_msg'] = "Formato de email inválido.";
    } elseif (strlen($clave) < 8) {
        $_SESSION['flash_msg'] = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($clave !== $clave2) {
        $_SESSION['flash_msg'] = "Las contraseñas no coinciden.";
    } else {
        $_SESSION['flash_msg'] = registrar_usuario($email, $clave);
    }

    // PRG: redirige para limpiar POST y evitar reenvío
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// MENSAJE FLASH EN GET
if (isset($_SESSION['flash_msg'])) {
    $mensaje = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Oficina Virtual - Inicio</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Opcional: evitar cache agresivo del formulario -->
    <meta http-equiv="Cache-Control" content="no-store">

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="assets/css/animate.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-3">
            <img src="assets/img/logo.png" alt="Logo OVNI" class="logo-ovni mb-2" style="width: 70px;">
            <h2 class="mb-2" style="font-weight: bold; color: #0d6efd;">Registro de Usuario</h2>
            <p class="text-muted mb-3" style="font-size: 1rem;">¡Crea tu cuenta para acceder a la oficina virtual!</p>
        </div>

        <!-- SUGERENCIA: dejar sin 'novalidate' para que el navegador ayude -->
        <form method="POST" action="" autocomplete="off" autocapitalize="none" spellcheck="false">
            <!-- Honeypot para frenar autocompletar terco -->
            <input type="text" name="fakeusernameremembered" style="display:none">
            <input type="password" name="fakepasswordremembered" style="display:none">

            <div class="mb-3">
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Email"
                    required
                    inputmode="email"
                    autocomplete="email"
                    value="">
            </div>

            <div class="mb-3">
                <input
                    type="password"
                    id="clave"
                    name="clave"
                    class="form-control"
                    placeholder="Contraseña"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    value="">
            </div>

            <div class="mb-3">
                <input
                    type="password"
                    id="clave2"
                    name="clave2"
                    class="form-control"
                    placeholder="Reingresa la contraseña"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    value="">
            </div>

            <button type="submit" class="btn btn-primary w-100">Registrarse</button>
        </form>

        <?php if ($mensaje): ?>
            <div class="alert alert-info mt-3 py-2 text-center" style="font-size: 0.95em;">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none" style="color: #0d6efd;">&larr; Volver al inicio</a>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Validación cliente adicional: longitud y coincidencia -->
<script>
  (function () {
    const form  = document.querySelector('form');
    const pass1 = document.getElementById('clave');
    const pass2 = document.getElementById('clave2');

    function validar() {
      pass1.setCustomValidity(pass1.value.length < 8 ? 'La contraseña debe tener al menos 8 caracteres.' : '');
      pass2.setCustomValidity(pass1.value !== pass2.value ? 'Las contraseñas no coinciden.' : '');
    }

    pass1.addEventListener('input', validar);
    pass2.addEventListener('input', validar);
    form.addEventListener('submit', (e) => {
      validar();
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
      }
    });
  })();
</script>

</body>
</html>
