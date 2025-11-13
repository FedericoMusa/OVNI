<?php
require_once __DIR__ . '/funciones.php';

use App\Core\Session; ?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login</title>
<link rel="stylesheet" href="/OVNI-desarrollo/public/assets/css/bootstrap.min.css">
</head><body class="p-4">
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= Session::h($error) ?></div>
<?php endif; ?>
<?php if (!empty($expired)): ?>
  <div class="alert alert-warning">Tu sesión expiró por inactividad.</div>
<?php endif; ?>
<form method="post" action="/OVNI-desarrollo/public/?action=login" class="card p-3 mx-auto" style="max-width:420px">
  <h1 class="h4 mb-3">Iniciar sesión</h1>
  <input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
  <input class="form-control mb-3" type="password" name="clave" placeholder="Contraseña" required>
  <button class="btn btn-primary w-100">Entrar</button>
</form>
</body></html>
