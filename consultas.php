<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['usuario'];

// Lógica para el avatar por defecto
$avatar = empty($user['avatar_usuario']) ? 'novatar.png' : $user['avatar_usuario'];
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-3">
            <img src="img/<?php echo $avatar; ?>" class="avatar mb-2" alt="Avatar" style="width: 90px;">
            <h2 class="mb-2" style="font-weight: bold; color: #0d6efd;">Mi Oficina Virtual</h2>
            <p class="text-muted mb-3" style="font-size: 1rem;">¡Bienvenido, <?php echo htmlspecialchars($user['email_usuario']); ?>!</p>
        </div>
        <div class="mb-3 text-center">
            <strong>Email:</strong> <?php echo htmlspecialchars($user['email_usuario']); ?>
        </div>
        <div class="text-center mt-3">
            <a href="logout.php" class="btn btn-outline-danger w-100">Cerrar sesión</a>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>