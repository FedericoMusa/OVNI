<?php
declare(strict_types=1);

// Agenda simple con FullCalendar - archivo corregido y limpio

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== Rutas =====
   $APP_ROOT = prefijo de la app ("" o "/OVNI"), calculado a partir de /panel/... */
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (preg_match('#^(.*?)/panel(?:/|$)#', $script, $m)) {
    $APP_ROOT = rtrim($m[1], '/');
} else {
    $APP_ROOT = '';
}

/* ===== Seguridad: requiere login ===== */
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: {$APP_ROOT}/login.php");
    exit;
}

/* ===== Util ===== */
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/* ===== Agenda: almacenamiento simple en JSON ===== */
$DATA_FILE = __DIR__ . '/agenda_data.json';
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

/* ===== Funciones de almacenamiento ===== */
if (!function_exists('load_events')) {
    function load_events(string $file): array {
        if (!is_file($file)) return [];
        $json = @file_get_contents($file);
        $arr  = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }
}
if (!function_exists('save_events')) {
    function save_events(string $file, array $events): bool {
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }
        $json = json_encode(array_values($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) return false;
        // escribir de forma atómica con LOCK_EX
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }
}
if (!function_exists('next_id')) {
    function next_id(array $events): int {
        $max = 0;
        foreach ($events as $e) {
            $id = (int)($e['id'] ?? 0);
            if ($id > $max) $max = $id;
        }
        return $max + 1;
    }
}

/* ===== AJAX handler para FullCalendar ===== */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = (string)($_GET['action'] ?? 'list');
    $events = load_events($DATA_FILE);

    if ($action === 'list') {
        $payload = array_map(function($e){
            return [
                'id' => (string)($e['id'] ?? ''),
                'title' => $e['title'] ?? '',
                'start' => $e['start'] ?? null,
                'end' => $e['end'] ?? null,
                'extendedProps' => [
                    'phone' => $e['phone'] ?? '',
                    'references' => $e['references'] ?? '',
                    'reminder' => $e['reminder'] ?? ''
                ]
            ];
        }, $events);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Acciones que modifican el estado deben venir por POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CSRF
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $start = (string)($_POST['start'] ?? '');
        $end   = (string)($_POST['end'] ?? '');
        $phone = trim((string)($_POST['phone'] ?? ''));
        $refs  = trim((string)($_POST['references'] ?? ''));
        $rem   = trim((string)($_POST['reminder'] ?? ''));

        if ($title === '' || $start === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Título y fecha de inicio requeridos'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = next_id($events);
        $ev = [
            'id' => $id,
            'title' => $title,
            'start' => $start,
            'end' => $end ?: null,
            'phone' => $phone,
            'references' => $refs,
            'reminder' => $rem
        ];
        $events[] = $ev;

        if (save_events($DATA_FILE, $events)) {
            echo json_encode(['ok' => true, 'event' => $ev], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $found = false;
        foreach ($events as &$e) {
            if ((int)($e['id'] ?? 0) === $id) {
                $found = true;
                $e['title'] = trim((string)($_POST['title'] ?? $e['title']));
                $e['start'] = (string)($_POST['start'] ?? $e['start']);
                $e['end']   = (string)($_POST['end'] ?? $e['end']);
                $e['phone'] = trim((string)($_POST['phone'] ?? $e['phone']));
                $e['references'] = trim((string)($_POST['references'] ?? $e['references']));
                $e['reminder'] = trim((string)($_POST['reminder'] ?? $e['reminder']));

                if (save_events($DATA_FILE, $events)) {
                    echo json_encode(['ok' => true, 'event' => $e], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(500);
                    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar'], JSON_UNESCAPED_UNICODE);
                }
                break;
            }
        }
        if (!$found) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Evento no encontrado'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $found = false;
        foreach ($events as $k => $e) {
            if ((int)($e['id'] ?? 0) === $id) {
                unset($events[$k]);
                $found = true;
                break;
            }
        }
        if (!$found) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Evento no encontrado'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (save_events($DATA_FILE, $events)) {
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===== Página HTML (interfaz) ===== */
$user = $_SESSION['usuario'];
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$pageTitle = 'Agenda — ' . $nombreOficina;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= h($pageTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
  <style>
    #calendar { max-width: 900px; margin: 0 auto; }
    .fc .fc-event { cursor: pointer; }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Agenda</h1>
    <div>
      <a class="btn btn-outline-secondary me-2" href="<?= $APP_ROOT ?>/panel/dashboard/dashboard.php">← Volver al dashboard</a>
      <a class="btn btn-outline-dark" href="<?= $APP_ROOT ?>/logout.php">Cerrar sesión</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm"><div class="card-body"><div id="calendar"></div></div></div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h5 class="mb-2">Próximos eventos</h5>
          <ul id="nextEvents" class="list-unstyled mb-0"></ul>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="mb-2">Acciones</h5>
          <div class="d-grid">
            <button id="btnNew" class="btn btn-primary">+ Nuevo evento</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para crear/editar cita -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="eventForm">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
      <input type="hidden" name="id" id="evt_id">
      <div class="modal-header"><h5 class="modal-title" id="modalTitle">Nueva cita</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Título</label><input name="title" id="evt_title" class="form-control" required></div>
        <div class="row g-2">
          <div class="col-6"><label class="form-label">Inicio</label><input type="datetime-local" name="start" id="evt_start" class="form-control" required></div>
          <div class="col-6"><label class="form-label">Fin (opcional)</label><input type="datetime-local" name="end" id="evt_end" class="form-control"></div>
        </div>
        <div class="mb-2 mt-2"><label class="form-label">Teléfono</label><input name="phone" id="evt_phone" class="form-control" placeholder="+54..."></div>
        <div class="mb-2"><label class="form-label">Referencias / notas</label><textarea name="references" id="evt_references" rows="3" class="form-control"></textarea></div>
        <div class="mb-0"><label class="form-label">Recordatorio (datetime opcional)</label><input type="datetime-local" name="reminder" id="evt_reminder" class="form-control"><div class="form-text">El sistema guardará el campo; luego podés exportarlo o consultar para enviar recordatorios externos.</div></div>
      </div>
      <div class="modal-footer"><button type="button" id="btnDelete" class="btn btn-outline-danger me-auto" style="display:none">Eliminar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
    </form>
  </div></div>
</div>

<script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
(function(){
  const ajaxUrl = location.pathname + '?ajax=1';
  const calendarEl = document.getElementById('calendar');
  const modalEl = document.getElementById('eventModal');
  const bsModal = new bootstrap.Modal(modalEl, {});
  const form = document.getElementById('eventForm');
  const btnNew = document.getElementById('btnNew');
  const btnDelete = document.getElementById('btnDelete');
  const nextList = document.getElementById('nextEvents');

  let calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
    selectable: true,
    select: function(info) { openModal({ start: info.startStr, end: info.endStr || '' }, 'create'); },
    dateClick: function(info) { const dt = info.dateStr + 'T09:00'; openModal({ start: dt, end: '' }, 'create'); },
    eventClick: function(info) {
      const ev = info.event;
      openModal({ id: ev.id, title: ev.title, start: ev.startStr, end: ev.endStr || '', phone: ev.extendedProps.phone || '', references: ev.extendedProps.references || '', reminder: ev.extendedProps.reminder || '' }, 'edit');
    },
    events: function(fetchInfo, successCallback, failureCallback) {
      fetch(ajaxUrl + '&action=list').then(r=>r.json()).then(successCallback).catch(failureCallback);
    }
  });
  calendar.render();

  function refreshList() {
    fetch(ajaxUrl + '&action=list').then(r=>r.json()).then(events=>{
      const future = events.filter(e => !e.start || new Date(e.start) >= new Date()).sort((a,b)=> new Date(a.start) - new Date(b.start)).slice(0,6);
      nextList.innerHTML = future.length ? future.map(ev=>`<li class="mb-2"><strong>${escapeHtml(ev.title)}</strong><div class="text-muted small">${ev.start? new Date(ev.start).toLocaleString() : ''}${ev.extendedProps && ev.extendedProps.phone ? ' · ' + escapeHtml(ev.extendedProps.phone) : ''}</div></li>`).join('') : '<li class="text-muted">— (sin eventos)</li>';
    }).catch(()=>{ nextList.innerHTML = '<li class="text-muted">— (error cargando)</li>'; });
  }
  refreshList();

  btnNew.addEventListener('click', ()=> openModal({ start: new Date().toISOString().slice(0,16) }, 'create'));

  function openModal(data, mode) {
    document.getElementById('modalTitle').textContent = mode==='create' ? 'Nueva cita' : 'Editar cita';
    document.getElementById('evt_id').value = data.id || '';
    document.getElementById('evt_title').value = data.title || '';
    document.getElementById('evt_start').value = (data.start ? toLocalInput(data.start) : '');
    document.getElementById('evt_end').value = (data.end ? toLocalInput(data.end) : '');
    document.getElementById('evt_phone').value = data.phone || '';
    document.getElementById('evt_references').value = data.references || '';
    document.getElementById('evt_reminder').value = (data.reminder ? toLocalInput(data.reminder) : '');
    btnDelete.style.display = mode === 'edit' ? 'inline-block' : 'none';
    bsModal.show();
  }

  function toLocalInput(dtStr) {
    if (!dtStr) return '';
    const d = new Date(dtStr);
    if (isNaN(d)) return '';
    const pad = n => n.toString().padStart(2,'0');
    const yyyy = d.getFullYear();
    const mm = pad(d.getMonth()+1);
    const dd = pad(d.getDate());
    const hh = pad(d.getHours());
    const mi = pad(d.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(form);
    const id = fd.get('id') || '';
    const action = id ? 'update' : 'create';
    fetch(ajaxUrl + '&action=' + action, { method:'POST', body: fd })
      .then(r=>r.json()).then(res=>{
        if (res.ok) {
          calendar.refetchEvents();
          refreshList();
          bsModal.hide();
        } else {
          alert(res.error || 'Error');
        }
      }).catch(()=>alert('Error de comunicación'));
  });

  btnDelete.addEventListener('click', function(){
    if (!confirm('Eliminar esta cita?')) return;
    const fd = new FormData();
    fd.append('csrf', form.querySelector('[name=csrf]').value);
    fd.append('id', document.getElementById('evt_id').value);
    fetch(ajaxUrl + '&action=delete', { method:'POST', body: fd })
      .then(r=>r.json()).then(res=>{
        if (res.ok) {
          calendar.refetchEvents();
          refreshList();
          bsModal.hide();
        } else alert(res.error || 'No se pudo eliminar');
      }).catch(()=>alert('Error de comunicación'));
  });

  function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c=> ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
})();
</script>
</body>
</html>