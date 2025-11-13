<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario'])) { header("Location: ../login.php"); exit; }
$user = $_SESSION['usuario'];

// helper HTML safe
if (!function_exists('h')) {
  function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

// base URL relativa (sirve para links/recursos)
function base_url(): string {
  return rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
}
