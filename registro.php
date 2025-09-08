<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "funciones.php";
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mensaje = registrar_usuario($_POST['email'], $_POST['clave']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Oficina Virtual - Inicio</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/animate.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
</head>
<body>


<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-3">
            <img src="img/logo.png" alt="Logo OVNI" class="logo-ovni mb-2" style="width: 70px;">
            <h2 class="mb-2" style="font-weight: bold; color: #0d6efd;">Registro de Usuario</h2>
            <p class="text-muted mb-3" style="font-size: 1rem;">¡Crea tu cuenta para acceder a la oficina virtual!</p>
        </div>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
            </div>
            <div class="mb-3">
                <input type="password" name="clave" class="form-control" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Registrarse</button>
        </form>
        <?php if ($mensaje): ?>
            <div class="alert alert-info mt-3 py-2 text-center" style="font-size: 0.95em;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none" style="color: #0d6efd;">&larr; Volver al inicio</a>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>


</body>
</html>