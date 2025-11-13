<?php
namespace App\Service;

use App\Repository\UserRepository;

class OfficeService {
  public function __construct(private UserRepository $users) {}

  public function renameOffice(int $userId, string $name): bool {
    return $this->users->updateOfficeName($userId, $name);
  }

  public function updateAvatar(int $userId, string $fileName): bool {
    return $this->users->updateAvatar($userId, $fileName);
  }
}
