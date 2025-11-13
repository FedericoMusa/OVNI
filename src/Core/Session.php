<?php
namespace App\Core;

class Session {
  private int $limit;

  public function __construct(int $inactivityLimitSeconds) {
    $this->limit = $inactivityLimitSeconds;
    if (session_status() === PHP_SESSION_NONE) session_start();
    $this->applyInactivity();
  }

  private function applyInactivity(): void {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $this->limit)) {
      session_unset();
      session_destroy();
      header("Location: /OVNI-desarrollo/public/?expired=1");
      exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
  }

  public function setUser(array $user): void { $_SESSION['usuario'] = $user; }
  public function user(): ?array { return $_SESSION['usuario'] ?? null; }
  public function logout(): void { session_unset(); session_destroy(); }
  public static function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}
