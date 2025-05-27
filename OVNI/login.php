<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "funciones.php";
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = login_usuario($_POST['email'], $_POST['clave']);
    if ($user) {
        $_SESSION['usuario'] = $user;
        header("Location: consultas.php");
        exit();
    } else {
        $mensaje = "Usuario o contraseña incorrecto";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - OVNI</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="main-content">
        <h2>Iniciar Sesión</h2>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="clave" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>
        <p style="color:#c00;"><?php echo $mensaje; ?></p>
        <div style="text-align:center;">
            <a href="index.php">Volver</a>
        </div>
    </div>
</body>
</html>