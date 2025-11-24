<?php
declare(strict_types=1);

// Agenda - Con validación de fechas pasadas

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 1. CONFIGURACIÓN */
$ruta_actual = dirname($_SERVER['SCRIPT_NAME']); 
$ruta_raiz   = dirname(dirname($ruta_actual)); 
$APP_ROOT    = rtrim(str_replace('\\', '/', $ruta_raiz), '/');

require_once __DIR__ . '/../../funciones.php'; 

/* 2. SEGURIDAD */
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: {$APP_ROOT}/login.php");
    exit;
}

$user = $_SESSION['usuario'];

if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/* 3. DATOS VISUALES */
$nombreOficina = $user['nombre_oficina'] ?? 'Mi Oficina';
$avatarUrl     = $APP_ROOT . '/assets/img/' . ($user['avatar_usuario'] ?? 'noavatar.png');
$colorAcento   = $user['color_tema'] ?? '#009640'; 

/* ===== LÓGICA CALENDARIO ===== */
$DATA_FILE = __DIR__ . '/agenda_data.json';
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

// Funciones auxiliares locales
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
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return false;
        $json = json_encode(array_values($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

/* ===== AJAX ===== */
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) { http_response_code(403); exit; }

    // --- VALIDACIÓN DE FECHA (BACKEND) ---
    // Se aplica para create y update
    if ($action === 'create' || $action === 'update') {
        $startStr = $_POST['start'] ?? '';
        if ($startStr) {
            try {
                $dtStart = new DateTime($startStr);
                $dtNow   = new DateTime('today'); // Medianoche de hoy
                
                if ($dtStart < $dtNow) {
                    http_response_code(400);
                    echo json_encode(['ok'=>false, 'error'=>'No se pueden agendar eventos en fechas pasadas.']);
                    exit;
                }
            } catch (Exception $e) {
                // Fecha inválida, dejamos pasar o bloqueamos según preferencia. 
                // FullCalendar suele mandar fechas válidas ISO8601.
            }
        }
    }

    // Create
    if ($action === 'create') {
        $ev = [
            'id' => next_id($events),
            'title' => trim($_POST['title'] ?? ''),
            'start' => $_POST['start'] ?? '',
            'end' => $_POST['end'] ?? null,
            'phone' => $_POST['phone'] ?? '',
            'references' => $_POST['references'] ?? '',
            'reminder' => $_POST['reminder'] ?? ''
        ];
        $events[] = $ev;
        echo save_events($DATA_FILE, $events) ? json_encode(['ok'=>true]) : json_encode(['ok'=>false]);
        exit;
    }
    // Update
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        foreach ($events as &$e) {
            if ((int)$e['id'] === $id) {
                $e['title'] = $_POST['title'];
                $e['start'] = $_POST['start'];
                $e['end'] = $_POST['end'];
                $e['phone'] = $_POST['phone'];
                $e['references'] = $_POST['references'];
                $e['reminder'] = $_POST['reminder'];
                echo save_events($DATA_FILE, $events) ? json_encode(['ok'=>true]) : json_encode(['ok'=>false]); exit;
            }
        }
    }
    // Delete
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        foreach ($events as $k => $e) {
            if ((int)$e['id'] === $id) {
                unset($events[$k]);
                echo save_events($DATA_FILE, $events) ? json_encode(['ok'=>true]) : json_encode(['ok'=>false]); exit;
            }
        }
    }
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Agenda | <?= h($nombreOficina) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link href="<?= $APP_ROOT ?>/assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

  <style>
    :root {
        --bg-body: #f4f6f8;
        --hero-bg: #1e293b;
        --primary: <?= $colorAcento ?>;
        --text-main: #334155;
        --card-bg: #ffffff;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* 1. NAVBAR */
    .navbar-pro {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.75rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .nav-logo { height: 32px; width: auto; }
    .nav-user-img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; }

    /* 2. HERO */
    .hero-pattern {
        background-color: var(--hero-bg);
        background-image: radial-gradient(#ffffff 1px, transparent 1px);
        background-size: 30px 30px;
        padding: 3rem 0 7rem 0;
        color: white;
        position: relative;
        text-align: center;
        overflow: hidden;
    }
    .hero-pattern::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.85) 0%, rgba(30, 41, 59, 1) 100%);
        pointer-events: none;
    }
    .hero-content { position: relative; z-index: 2; }

    .btn-back {
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 50px;
        padding: 6px 20px;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px;
        transition: all 0.2s;
        margin-bottom: 1.5rem;
    }
    .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }

    /* 3. CONTENEDOR SOLAPADO */
    .content-overlap {
        max-width: 1200px;
        margin: -4rem auto 3rem auto;
        padding: 0 20px;
        position: relative;
        z-index: 10;
        width: 100%;
    }

    /* 4. TARJETAS */
    .app-card {
        background: var(--card-bg);
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .card-head {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 600;
        color: #0f172a;
        background: white;
    }

    .card-body-pad { padding: 1.5rem; }

    .btn-primary-theme {
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.8rem;
        font-weight: 600;
        width: 100%;
        transition: opacity 0.2s;
    }
    .btn-primary-theme:hover { opacity: 0.9; color: white; }

    /* LISTA DE EVENTOS COMPACTA */
    .event-list { max-height: 400px; overflow-y: auto; }
    .event-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .event-item:last-child { border-bottom: none; }
    .event-date {
        background: #f1f5f9;
        color: var(--primary);
        font-weight: 700;
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 4px;
        text-align: center;
        line-height: 1.2;
        min-width: 50px;
    }
    .event-title { font-weight: 600; font-size: 0.9rem; color: #334155; }

    /* FULLCALENDAR TWEAKS */
    .fc-button-primary {
        background-color: var(--primary) !important;
        border-color: var(--primary) !important;
    }
    .fc-daygrid-event { border-radius: 4px; font-size: 0.85rem; }
    .fc .fc-toolbar-title { font-size: 1.25rem; }
    
    /* Estilo para días pasados */
    .fc-day-past {
        background-color: #f8f9fa; /* Gris muy suave */
        opacity: 0.7;
        cursor: not-allowed;
    }

  </style>
</head>
<body>

    <nav class="navbar-pro">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= $APP_ROOT ?>/assets/img/logo.png" alt="OVNI" class="nav-logo">
            <span class="fw-bold text-dark d-none d-sm-block fs-5">OVNI Panel</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block lh-1">
                <small class="d-block fw-bold text-dark"><?= h($nombreOficina) ?></small>
            </div>
            <img src="<?= $avatarUrl ?>" alt="User" class="nav-user-img">
            <a href="<?= $APP_ROOT ?>/logout.php" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                <i class="bi bi-box-arrow-right text-danger"></i>
            </a>
        </div>
    </nav>

    <header class="hero-pattern">
        <div class="container hero-content">
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
            <h2 class="h3 fw-bold text-white mb-1">Agenda Corporativa</h2>
            <p class="text-white text-opacity-75 small">Organiza tus reuniones y vencimientos</p>
        </div>
    </header>

    <div class="content-overlap">
        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="app-card shadow-sm">
                    <div class="card-body-pad">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 90px; z-index: 1;">
                    
                    <div class="app-card shadow-sm mb-4">
                        <div class="card-body-pad">
                            <button id="btnNew" class="btn-primary-theme">
                                <i class="bi bi-plus-lg me-2"></i> Crear Nuevo Evento
                            </button>
                        </div>
                    </div>

                    <div class="app-card shadow-sm">
                        <div class="card-head">
                            <i class="bi bi-clock-history me-2 text-muted"></i> Próximos Eventos
                        </div>
                        <div class="card-body-pad pt-2">
                            <div id="nextEvents" class="event-list">
                                <div class="text-center p-4 text-muted small">Cargando...</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="eventForm">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                    <input type="hidden" name="id" id="evt_id">
                    
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="modalTitle">Detalles del Evento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div id="jsError" class="alert alert-warning d-none small p-2"></div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Título</label>
                            <input name="title" id="evt_title" class="form-control" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Inicio</label>
                                <input type="datetime-local" name="start" id="evt_start" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Fin (Opcional)</label>
                                <input type="datetime-local" name="end" id="evt_end" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Teléfono / Contacto</label>
                            <input name="phone" id="evt_phone" class="form-control" placeholder="+54...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Notas</label>
                            <textarea name="references" id="evt_references" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" id="btnDelete" class="btn btn-light text-danger me-auto" style="display:none">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                        <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4" style="background: var(--primary); border:none;">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= $APP_ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/es.global.min.js"></script>
    
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
      const jsError = document.getElementById('jsError');

      // Calcular fecha de hoy sin hora (medianoche)
      const today = new Date();
      today.setHours(0,0,0,0);

      let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: { left: 'prev,next', center: 'title', right: 'dayGridMonth,timeGridWeek' },
        height: 'auto',
        selectable: true,
        
        // VALIDACIÓN 1: Selección de rango
        select: function(info) {
            // info.start es un objeto Date
            if (info.start < today) {
                alert('No puedes agendar eventos en el pasado.');
                calendar.unselect();
                return;
            }
            openModal({ start: info.startStr, end: info.endStr || '' }, 'create');
        },
        
        // VALIDACIÓN 2: Click en día
        dateClick: function(info) {
            // info.date es Date con hora 00:00 en dayGrid
            // En caso de duda, parsear info.dateStr
            const clickDate = new Date(info.dateStr + 'T00:00:00');
            if (clickDate < today) {
                alert('No puedes agendar en fechas pasadas.');
                return;
            }
            const dt = info.dateStr + 'T09:00';
            openModal({ start: dt, end: '' }, 'create');
        },
        
        eventClick: function(info) {
          const ev = info.event;
          openModal({ id: ev.id, title: ev.title, start: ev.startStr, end: ev.endStr || '', phone: ev.extendedProps.phone || '', references: ev.extendedProps.references || '' }, 'edit');
        },
        events: function(fetchInfo, successCallback, failureCallback) {
          fetch(ajaxUrl + '&action=list').then(r=>r.json()).then(successCallback).catch(failureCallback);
        }
      });
      calendar.render();

      function refreshList() {
        fetch(ajaxUrl + '&action=list').then(r=>r.json()).then(events=>{
          const future = events.filter(e => !e.start || new Date(e.start) >= new Date()).sort((a,b)=> new Date(a.start) - new Date(b.start)).slice(0,6);
          
          if(future.length) {
              nextList.innerHTML = future.map(ev => {
                  const date = new Date(ev.start);
                  const dateStr = date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
                  const timeStr = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                  return `
                    <div class="event-item d-flex align-items-center">
                        <div class="me-3 text-center">
                            <div class="event-date">
                                <span style="font-size:1.1em; display:block;">${date.getDate()}</span>
                                <small style="font-weight:400;">${date.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase()}</small>
                            </div>
                        </div>
                        <div style="flex-grow:1;">
                            <div class="event-title">${escapeHtml(ev.title)}</div>
                            <div class="small text-muted text-truncate">${ev.extendedProps.phone ? escapeHtml(ev.extendedProps.phone) : 'Evento agendado'}</div>
                        </div>
                    </div>
                  `;
              }).join('');
          } else {
              nextList.innerHTML = '<div class="text-center p-4 text-muted small">No hay eventos próximos</div>';
          }
        });
      }
      refreshList();

      btnNew.addEventListener('click', ()=> {
          // Al crear desde botón, sugerimos "ahora"
          const now = new Date();
          // Ajuste zona horaria simplificado
          const iso = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
          openModal({ start: iso }, 'create');
      });

      function openModal(data, mode) {
        jsError.classList.add('d-none');
        document.getElementById('modalTitle').textContent = mode==='create' ? 'Nueva Cita' : 'Editar Cita';
        document.getElementById('evt_id').value = data.id || '';
        document.getElementById('evt_title').value = data.title || '';
        document.getElementById('evt_start').value = (data.start ? toLocalInput(data.start) : '');
        document.getElementById('evt_end').value = (data.end ? toLocalInput(data.end) : '');
        document.getElementById('evt_phone').value = data.phone || '';
        document.getElementById('evt_references').value = data.references || '';
        btnDelete.style.display = mode === 'edit' ? 'inline-block' : 'none';
        bsModal.show();
      }

      function toLocalInput(dtStr) {
        if (!dtStr) return '';
        const d = new Date(dtStr);
        if (isNaN(d)) return '';
        const pad = n => n.toString().padStart(2,'0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
      }

      form.addEventListener('submit', function(e){
        e.preventDefault();
        
        // VALIDACIÓN EN EL SUBMIT DEL FORM
        const startVal = document.getElementById('evt_start').value;
        if (new Date(startVal) < today) {
            jsError.textContent = 'No puedes guardar una fecha pasada.';
            jsError.classList.remove('d-none');
            return;
        }

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
              jsError.textContent = res.error || 'Error desconocido';
              jsError.classList.remove('d-none');
            }
          }).catch(()=>{
              jsError.textContent = 'Error de comunicación con el servidor';
              jsError.classList.remove('d-none');
          });
      });

      btnDelete.addEventListener('click', function(){
        if (!confirm('¿Eliminar este evento?')) return;
        const fd = new FormData();
        fd.append('csrf', form.querySelector('[name=csrf]').value);
        fd.append('id', document.getElementById('evt_id').value);
        fetch(ajaxUrl + '&action=delete', { method:'POST', body: fd })
          .then(r=>r.json()).then(res=>{
            if (res.ok) {
              calendar.refetchEvents();
              refreshList();
              bsModal.hide();
            } else alert(res.error || 'Error');
          });
      });

      function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c=> ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    })();
    </script>
</body>
</html>