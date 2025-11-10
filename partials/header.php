<?php
// header común. Usa $pageTitle si viene definido.
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$title = isset($pageTitle) ? $pageTitle . ' – OVNI' : 'OVNI';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= htmlspecialchars($BASE) ?>">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/estilos.css">
  <link rel="stylesheet" href="assets/css/animate.css">
</head>
<body>
