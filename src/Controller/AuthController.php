<?php
namespace App\Controller;
require_once __DIR__ . '/../funciones.php';

use App\Core\Session;
use App\Service\AuthService;
use App\Repository\UserRepository;

class AuthController {
  public function __construct(
    private AuthService $auth,
    private UserRepository $users,
    private Session $session
  ) {}

  public function showLogin(): void {
    $expired = isset($_GET['expired']);
    include __DIR__ . '/../../views/login.php';
  }

  public function doLogin(): void {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['clave'] ?? '';
    $row   = $this->auth->login($email, $pass);
    if (!$row) {
      $error = "Usuario o contraseña incorrecto.";
      include __DIR__ . '/../../views/login.php';
      return;
    }
    $this->session->setUser($row);
    header("Location: /OVNI-desarrollo/public/?action=dashboard");
    exit;
  }

  public function logout(): void {
    $this->session->logout();
    header("Location: /OVNI-desarrollo/public/?action=login");
    exit;
  }
}
