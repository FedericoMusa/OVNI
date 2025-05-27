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
    <title>Registro - OVNI</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="main-content">
        <h2>Registro de Usuario</h2>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="clave" placeholder="Contraseña" required>
            <button type="submit">Registrarse</button>
        </form>
        <p style="color:#c00;"><?php echo $mensaje; ?></p>
        <div style="text-align:center;">
            <a href="index.php">Volver</a>
        </div>
    </div>
</body>
</html>