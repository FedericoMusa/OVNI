<?php
namespace App\Controller;
require_once __DIR__ . '/../../funciones.php';

use App\Core\Session;
use App\Service\OfficeService;
use App\Repository\UserRepository;

class DashboardController {
  public function __construct(
    private OfficeService $office,
    private UserRepository $users,
    private Session $session
  ) {}

  public function index(): void {
    $userArr = $this->session->user();
    if (!$userArr) { header("Location: /OVNI-desarrollo/public/?action=login"); exit; }
    $userId = (int)$userArr['id_usuario'];
    $user   = $this->users->findById($userId);
    $flash  = $_GET['flash'] ?? '';
    include __DIR__ . '/../../views/dashboard.php';
  }

  public function renameOffice(): void {
    $userArr = $this->session->user();
    if (!$userArr) { header("Location: /OVNI-desarrollo/public/?action=login"); exit; }
    $ok = $this->office->renameOffice((int)$userArr['id_usuario'], $_POST['nombre_oficina'] ?? '');
    $msg = $ok ? 'Nombre actualizado.' : 'Error al actualizar nombre.';
    header("Location: /OVNI-desarrollo/public/?action=dashboard&flash=" . urlencode($msg));
    exit;
  }

  public function changeAvatar(): void {
    $userArr = $this->session->user();
    if (!$userArr) { header("Location: /OVNI-desarrollo/public/?action=login"); exit; }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
      header("Location: /OVNI-desarrollo/public/?action=dashboard&flash=" . urlencode('Error al subir archivo.')); exit;
    }
    $tmp  = $_FILES['avatar']['tmp_name'];
    $ext  = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
      header("Location: /OVNI-desarrollo/public/?action=dashboard&flash=" . urlencode('Formato no permitido.')); exit;
    }
    $new  = 'avatar_' . (int)$userArr['id_usuario'] . '.' . $ext;
    $dest = __DIR__ . '/../../public/assets/img/' . $new;
    if (!is_dir(dirname($dest))) { mkdir(dirname($dest), 0777, true); }
    if (!move_uploaded_file($tmp, $dest)) {
      header("Location: /OVNI-desarrollo/public/?action=dashboard&flash=" . urlencode('No se pudo mover el archivo.')); exit;
    }
    $ok = $this->office->updateAvatar((int)$userArr['id_usuario'], $new);
    $msg = $ok ? 'Avatar actualizado.' : 'No se pudo guardar en BD.';
    header("Location: /OVNI-desarrollo/public/?action=dashboard&flash=" . urlencode($msg));
    exit;
  }
}
