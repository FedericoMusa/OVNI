<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Autoloader simple para src\ (soporta namespaces "App\" y "Src\")
 */
spl_autoload_register(function($class) {
  $prefixes = ['App\\' => __DIR__ . '/../src/', 'Src\\' => __DIR__ . '/../src/'];
  foreach ($prefixes as $prefix => $base) {
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) continue;
    $rel = substr($class, strlen($prefix));
    $file = $base . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) { require $file; return; }
  }
});

require_once __DIR__ . '/../config.php';      // tu config (DB, etc.)
require_once __DIR__ . '/../funciones.php';   // <-- puente procedural a POO

// Router súper simple por query string (?action=...)
$action = $_GET['action'] ?? 'login';

switch ($action) {
  case 'login':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Procedural: usamos el puente
      if (session_status() === PHP_SESSION_NONE) session_start();

      $email = $_POST['email'] ?? '';
      $pass  = $_POST['clave'] ?? '';
      $user  = login_usuario($email, $pass);

      if ($user) {
        $_SESSION['usuario'] = $user;
        header("Location: ?action=dashboard");
        exit;
      } else {
        $error = "Usuario o contraseña incorrecto.";
      }
    }
    // Vista login (simple)
    include __DIR__ . '/../views/login.php';
    break;

  case 'logout':
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset(); session_destroy();
    header("Location: ?action=login"); exit;

  case 'dashboard':
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) { header("Location: ?action=login"); exit; }
    // refresco de usuario por ID (para ver cambios)
    $u = obtener_usuario_por_id((int)$_SESSION['usuario']['id_usuario']);
    if ($u) $_SESSION['usuario'] = $u; 
    $user = $_SESSION['usuario'];
    $flash = $_GET['flash'] ?? '';
    include __DIR__ . '/../views/dashboard.php';
    break;

  case 'renameOffice':
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) { header("Location: ?action=login"); exit; }
    $ok  = actualizar_nombre_oficina((int)$_SESSION['usuario']['id_usuario'], $_POST['nombre_oficina'] ?? '');
    $msg = $ok ? 'Nombre actualizado.' : 'Error al actualizar nombre.';
    header("Location: ?action=dashboard&flash=" . urlencode($msg)); exit;

  case 'changeAvatar':
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) { header("Location: ?action=login"); exit; }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
      header("Location: ?action=dashboard&flash=" . urlencode('Error al subir archivo.')); exit;
    }
    $tmp = $_FILES['avatar']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
      header("Location: ?action=dashboard&flash=" . urlencode('Formato no permitido.')); exit;
    }
    $new  = 'avatar_' . (int)$_SESSION['usuario']['id_usuario'] . '.' . $ext;
    $dest = __DIR__ . '/assets/img/' . $new;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
    if (!move_uploaded_file($tmp, $dest)) {
      header("Location: ?action=dashboard&flash=" . urlencode('No se pudo mover el archivo.')); exit;
    }
    $ok = actualizar_avatar_oficina((int)$_SESSION['usuario']['id_usuario'], $new);
    $msg = $ok ? 'Avatar actualizado.' : 'No se pudo guardar en BD.';
    header("Location: ?action=dashboard&flash=" . urlencode($msg)); exit;

  default:
    http_response_code(404);
    echo "Not Found";
}
