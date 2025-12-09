<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/funciones.php';

/* -------------------------------
   Rutas: raíz de la app (OVNI)
   ------------------------------- */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$BASE = rtrim(str_replace('\\','/', dirname($scriptName)), '/');      // /OVNI  ó /OVNI/panel
$APP_ROOT = rtrim(preg_replace('#/panel$#', '', $BASE), '/');         // /OVNI
if ($APP_ROOT === '/') $APP_ROOT = '';

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email  = isset($_POST['email'])  ? trim($_POST['email'])  : '';
  $clave  = isset($_POST['clave'])  ? (string)$_POST['clave']  : '';
  $clave2 = isset($_POST['clave2']) ? (string)$_POST['clave2'] : '';
  $acepto = !empty($_POST['acepto_terminos']);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = "Formato de email inválido.";
  } elseif (strlen($clave) < 8) {
    $mensaje = "La contraseña debe tener al menos 8 caracteres.";
  } elseif ($clave !== $clave2) {
    $mensaje = "Las contraseñas no coinciden.";
  } elseif (!$acepto) {
    $mensaje = "Debes aceptar el Descargo y Seguridad antes de registrarte.";
  } else {
    $mensaje = registrar_usuario($email, $clave); // registra versión/aceptación en funciones.php
  }
}

$pageTitle = 'Registro de usuario';
require_once __DIR__ . '/partials/header.php';
?>
<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="card shadow-lg p-4" style="max-width: 420px; width: 100%;">
    <div class="text-center mb-3">
      <img src="<?= $APP_ROOT ?>/assets/img/logo.png" alt="Logo OVNI" class="logo-ovni mb-2" style="width: 70px;">
      <h2 class="mb-2" style="font-weight: bold; color: #0d6efd;">Registro de Usuario</h2>
      <p class="text-muted mb-3" style="font-size: 1rem;">Creá tu cuenta para acceder a la oficina virtual</p>
    </div>

    <form method="POST" action="" autocomplete="off" autocapitalize="none" spellcheck="false">
      <input type="text" name="fakeusernameremembered" style="display:none">
      <input type="password" name="fakepasswordremembered" style="display:none">

      <div class="mb-3">
        <input
          type="email"
          name="email"
          class="form-control"
          placeholder="Email"
          required
          inputmode="email"
          autocomplete="email"
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>"
        >
      </div>

      <div class="mb-3 position-relative">
        <input type="password" id="clave" name="clave" class="form-control" placeholder="Contraseña" required minlength="8" autocomplete="new-password">
        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2"
                onclick="togglePassword('clave', this)">👁️</button>
      </div>

      <div class="mb-1 position-relative">
        <input type="password" id="clave2" name="clave2" class="form-control" placeholder="Reingresa la contraseña" required minlength="8" autocomplete="new-password">
        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2"
                onclick="togglePassword('clave2', this)">👁️</button>
      </div>

      <div class="form-check mt-2 mb-3">
        <input class="form-check-input" type="checkbox" id="acepto_terminos" name="acepto_terminos" required <?= !empty($_POST['acepto_terminos']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="acepto_terminos" style="font-size: .95rem;">
          Acepto el
          <a href="<?= $APP_ROOT ?>/descargo.php" target="_blank" rel="noopener">Descargo y Seguridad</a>
          (versión 1.0)
        </label>
        <!-- Botoncito para verlo en modal sin abrir nueva pestaña -->
        <button type="button" class="btn btn-link p-0 ms-1 align-baseline" data-bs-toggle="modal" data-bs-target="#modalDescargo">(ver aquí)</button>
      </div>

      <button type="submit" class="btn btn-primary w-100">Registrarse</button>
    </form>

    <?php if ($mensaje): ?>
      <div class="alert alert-info mt-3 py-2 text-center" style="font-size: 0.95em;">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="<?= $APP_ROOT ?>/index.php" class="text-decoration-none" style="color: #0d6efd;">&larr; Volver al inicio</a>
    </div>
  </div>
</div>

<!-- Modal con el descargo embebido -->
<div class="modal fade" id="modalDescargo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Descargo y Seguridad (v1.0)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0" style="height:70vh;">
        <iframe src="<?= $APP_ROOT ?>/descargo.php" style="border:0;width:100%;height:100%;"></iframe>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const form  = document.querySelector('form');
  const pass1 = document.getElementById('clave');
  const pass2 = document.getElementById('clave2');
  const chk   = document.getElementById('acepto_terminos');

  function validar() {
    pass1.setCustomValidity(pass1.value.length < 8 ? 'La contraseña debe tener al menos 8 caracteres.' : '');
    pass2.setCustomValidity(pass1.value !== pass2.value ? 'Las contraseñas no coinciden.' : '');
  }
  pass1.addEventListener('input', validar);
  pass2.addEventListener('input', validar);

  form.addEventListener('submit', (e) => {
    validar();
    chk.setCustomValidity(chk.checked ? '' : 'Debes aceptar el Descargo y Seguridad.');
    if (!form.checkValidity()) { e.preventDefault(); form.reportValidity(); }
  });
})();
function togglePassword(idCampo, boton) {
  const campo = document.getElementById(idCampo);
  const visible = campo.type === "text";
  campo.type = visible ? "password" : "text";
  boton.textContent = visible ? "👁️" : "🙈";
}
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
