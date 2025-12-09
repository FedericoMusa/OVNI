<?php
namespace App\Service;

use App\Repository\UserRepository;

class AuthService {
  public function __construct(private UserRepository $users) {}

  public function login(string $email, string $password): ?array {
    $email = trim(mb_strtolower($email));
    $row = $this->users->findActiveByEmail($email);
    if (!$row) return null;

    $hash = (string)$row['clave_usuario'];
    $info = password_get_info($hash);
    $ok = !empty($info['algo']) ? password_verify($password, $hash) : hash_equals($hash, $password);
    return $ok ? $row : null;
  }
}
