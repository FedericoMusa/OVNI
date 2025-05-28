<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Oficina Virtual - Inicio</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="container main-content mt-5">
        <h1 class="text-center">Bienvenido a OVNI</h1>
        <p class="text-center">Tu oficina virtual simple y segura.</p>
        <div class="text-center">
            <a href="registro.php" class="btn btn-primary me-2">Registrarse</a>
            <a href="login.php" class="btn btn-success">Iniciar Sesión</a>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>