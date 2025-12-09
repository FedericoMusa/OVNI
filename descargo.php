<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$version = '1.0';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Descargo y Seguridad — OVNI (v<?= $version ?>)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <style>
    body { background: #f8f9fa; }
    .card { max-width: 800px; margin: 2rem auto; }
  </style>
</head>
<body>

<div class="card shadow">
  <div class="card-body">
    <h1 class="h4 mb-3 text-center">Descargo y Seguridad (v<?= $version ?>)</h1>
    <p class="text-muted text-center mb-4">
      Documento basado en las normas ISO 27001 (Seguridad de la Información) e ISO 29100 (Privacidad de datos personales).
    </p>

    <p>
      La plataforma <strong>OVNI (Oficina Virtual Naturalmente Integrada)</strong> proporciona un entorno digital para que sus usuarios
      gestionen y almacenen información relacionada con su actividad. Cada usuario es responsable del contenido que
      sube, almacena o comparte dentro del sistema.
    </p>

    <p>
      OVNI no se hace responsable por la veracidad, exactitud ni licitud del material ingresado por los usuarios. Cualquier
      uso indebido, publicación de información confidencial o violación de derechos de terceros será exclusiva
      responsabilidad del titular de la cuenta.
    </p>

    <p>
      Los datos personales son tratados conforme a las mejores prácticas internacionales en materia de seguridad de la información,
      adoptando medidas técnicas y organizativas que buscan proteger su integridad, disponibilidad y confidencialidad.
    </p>

    <p>
      Al registrarse, el usuario declara haber leído y aceptado este documento, comprometiéndose a cumplir con las
      políticas de seguridad y las leyes vigentes en la República Argentina, incluyendo la Ley N° 25.326 de Protección de Datos Personales.
    </p>

    <div class="text-center mt-4">
      <a href="registro.php" class="btn btn-primary">Volver al registro</a>
    </div>
  </div>
</div>

</body>
</html>
