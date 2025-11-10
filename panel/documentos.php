<?php
session_start();
require_once __DIR__ . '/funciones.php';
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }
$pageTitle = 'Documentos / Incidentes';
require_once __DIR__ . '/partials/header.php';
?>
<div class="container my-4">
  <div class="card">
    <div class="card-body">
      <h2 class="h5 mb-3">Documentos / Incidentes</h2>
      <p class="text-muted mb-0">Vista de documentos e incidentes. (Placeholder)</p>
    </div>
  </div>
  <div class="mt-3">
    <a class="btn btn-outline-secondary" href="dashboard.php">Volver al dashboard</a>
  </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
