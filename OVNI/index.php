<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Oficina Virtual - Inicio</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="main-content">
        <h1>Bienvenido a OVNI</h1>
        <p>Tu oficina virtual simple y segura.</p>
        <div style="text-align:center;">
            <a href="registro.php">Registrarse</a> | <a href="login.php">Iniciar Sesión</a>
        </div>
    </div>
</body>
</html>