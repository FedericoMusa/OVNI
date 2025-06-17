<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Oficina Virtual</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="main-content">
        <h2>Mi Oficina Virtual</h2>
        <img src="img/<?php echo $user['avatar_usuario']; ?>" class="avatar" alt="Avatar"><br>
        <strong>Email:</strong> <?php echo $user['email_usuario']; ?><br>
        <div style="text-align:center; margin-top:20px;">
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </div>
</body>
</html>