<?php
namespace App\Repository;

use App\Core\Database;
use App\Domain\User;
use mysqli;

class UserRepository {
  public function __construct(private Database $db) {}

  public function findActiveByEmail(string $email): ?array {
    $sql  = "SELECT * FROM usuarios WHERE email_usuario = ? AND estado_usuario='activo' LIMIT 1";
    $stmt = $this->db->conn()->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
  }

  public function findById(int $id): ?User {
    $sql = "SELECT id_usuario, email_usuario, avatar_usuario, estado_usuario, nombre_oficina, id_oficina
            FROM usuarios WHERE id_usuario=? LIMIT 1";
    $stmt = $this->db->conn()->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) return null;
    return new User((int)$r['id_usuario'], $r['email_usuario'], $r['avatar_usuario'],
                    $r['estado_usuario'], $r['nombre_oficina'], $r['id_oficina'] !== null ? (int)$r['id_oficina'] : null);
  }

  public function updateOfficeName(int $userId, string $name): bool {
    $name = trim($name);
    if ($name === '' || mb_strlen($name) > 100) return false;
    $sql  = "UPDATE usuarios SET nombre_oficina=? WHERE id_usuario=? LIMIT 1";
    $stmt = $this->db->conn()->prepare($sql);
    $stmt->bind_param("si", $name, $userId);
    return $stmt->execute();
  }

  public function updateAvatar(int $userId, string $fileName): bool {
    $fileName = basename($fileName);
    if ($fileName === '') return false;
    $sql  = "UPDATE usuarios SET avatar_usuario=? WHERE id_usuario=? LIMIT 1";
    $stmt = $this->db->conn()->prepare($sql);
    $stmt->bind_param("si", $fileName, $userId);
    return $stmt->execute();
  }
}
