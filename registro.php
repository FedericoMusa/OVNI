<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "funciones.php";

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- Validaciones del lado servidor ---
    $email  = isset($_POST['email'])  ? trim($_POST['email'])  : '';
    $clave  = isset($_POST['clave'])  ? (string)$_POST['clave']  : '';
    $clave2 = isset($_POST['clave2']) ? (string)$_POST['clave2'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Formato de email inválido.";
    } elseif (strlen($clave) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($clave !== $clave2) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        // Si pasa las validaciones, registramos
        $mensaje = registrar_usuario($email, $clave);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Oficina Virtual - Inicio</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-3">
            <img src="assets/img/logo.png" alt="Logo OVNI" class="logo-ovni mb-2" style="width: 70px;">
            <h2 class="mb-2" style="font-weight: bold; color: #0d6efd;">Registro de Usuario</h2>
            <p class="text-muted mb-3" style="font-size: 1rem;">¡Crea tu cuenta para acceder a la oficina virtual!</p>
        </div>

        <!-- Autocomplete desactivado para evitar que “pegue” valores -->
        <form method="POST" action="" autocomplete="off" autocapitalize="none" spellcheck="false" novalidate>
            <!-- Honeypot para desalentar autocompletar de algunos navegadores -->
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

<!-- Validación cliente: longitud y coincidencia -->
<script>
  (function () {
    const form  = document.querySelector('form');
    const pass1 = document.getElementById('clave');
    const pass2 = document.getElementById('clave2');

    function validar() {
      // longitud mínima
      if (pass1.value.length < 8) {
        pass1.setCustomValidity('La contraseña debe tener al menos 8 caracteres.');
      } else {
        pass1.setCustomValidity('');
      }
      // coincidencia
      if (pass1.value !== pass2.value) {
        pass2.setCustomValidity('Las contraseñas no coinciden.');
      } else {
        pass2.setCustomValidity('');
      }
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
