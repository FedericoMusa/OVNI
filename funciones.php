<?php
//////////////////////////
// Conexión a la BD
//////////////////////////
const LEGAL_VERSION = '1.0';

function conectar(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "ovni_dev"; // Asegurate que este sea el nombre correcto de tu BD

    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 🟩 Helpers para versionado legal
function asegurar_version_legal(mysqli $conn, string $version): void {
    $sql = "INSERT IGNORE INTO terms_versions (version, title, published_at) VALUES (?, 'Descargo y Seguridad OVNI', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $version);
    $stmt->execute();
    $stmt->close();
}

function guardar_aceptacion_terminos(mysqli $conn, int $userId, string $version): void {
    $ip        = $_SERVER['REMOTE_ADDR']     ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $sql = "INSERT INTO user_terms_acceptances (user_id, version, accepted_at, ip, user_agent) VALUES (?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $userId, $version, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}

/* ==========================================================
   GESTIÓN DE USUARIOS Y PERMISOS (Corregido para usuarios.php)
   ========================================================== */

// 1. Obtener lista de todos los usuarios
function obtener_todos_usuarios(): array {
    $conn = conectar();
    // IMPORTANTE: Se agregó 'rol' y 'avatar_usuario' que faltaban
    $sql  = "SELECT id_usuario, email_usuario, nombre_oficina, estado_usuario, rol, avatar_usuario FROM usuarios ORDER BY id_usuario DESC";
    $res  = $conn->query($sql);
    
    // Si no existe la columna rol aún, devolvemos array vacío para no romper todo
    if (!$res) return [];

    $usuarios = $res->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $usuarios;
}

// 2. Crear un nuevo usuario (Con contraseña encriptada)
function crear_usuario_nuevo($nombre, $email, $password, $rol) {
    $conn = conectar();
    
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email_usuario = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return "El email ya está registrado.";
    }
    $stmt->close();

    // Encriptar contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar (Asumimos avatar por defecto)
    $sql = "INSERT INTO usuarios (nombre_oficina, email_usuario, password_usuario, rol, avatar_usuario, estado_usuario) VALUES (?, ?, ?, ?, 'noavatar.png', 'activo')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
    
    $exito = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $exito ? true : "Error al guardar en BD.";
}

// 3. Eliminar usuario
function eliminar_usuario($id) {
    $conn = conectar();
    $sql = "DELETE FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $exito = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $exito;
}

//////////////////////////
// Helpers generales
//////////////////////////
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

function normalizar_email(string $email): string { return trim(mb_strtolower($email)); }

function validar_credenciales(string $email, string $clave): ?string {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Formato de email inválido.";
    }
    if (strlen($clave) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    return null; // OK
}

//////////////////////////
// Helpers de sesión
//////////////////////////
function usuario_id_actual(): ?int {
    return isset($_SESSION['usuario']['id_usuario']) ? (int)$_SESSION['usuario']['id_usuario'] : null;
}
function oficina_id_actual(): ?int {
    return isset($_SESSION['usuario']['id_oficina']) ? (int)$_SESSION['usuario']['id_oficina'] : null;
}

//////////////////////////
// Login de usuario
//////////////////////////
function login_usuario(string $email, string $clave): ?array {
    $conn = conectar();
    // IMPORTANTE: Traemos el campo 'rol' en el login para la sesión
    $sql  = "SELECT * FROM usuarios WHERE email_usuario = ? AND estado_usuario = 'activo' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $emailNorm = normalizar_email($email);
    $stmt->bind_param("s", $emailNorm);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc() ?: null;
    $stmt->close();
    $conn->close();

    if (!$user) return null;

    // Detectar si usamos password_usuario (nuevo) o clave_usuario (viejo)
    // Tu código mezclaba ambos nombres. Aquí priorizamos password_usuario si existe, sino clave_usuario.
    $hash = $user['password_usuario'] ?? $user['clave_usuario'] ?? '';
    
    if (password_verify($clave, $hash)) {
        return $user;
    }
    
    return null;
}

//////////////////////////
// Obtener usuario por ID
//////////////////////////
function obtener_usuario_por_id(int $id_usuario): ?array {
    $conn = conectar();
    $sql  = "SELECT id_usuario, email_usuario, avatar_usuario, estado_usuario, nombre_oficina, id_oficina, rol
             FROM usuarios WHERE id_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc() ?: null;
    $stmt->close();
    $conn->close();
    return $user;
}

//////////////////////////
// Actualizaciones
//////////////////////////
function actualizar_nombre_oficina(int $id_usuario, string $nuevo_nombre): bool {
    $nuevo_nombre = trim($nuevo_nombre);
    if ($nuevo_nombre === '' || mb_strlen($nuevo_nombre) > 100) return false;
    
    $conn = conectar();
    $sql  = "UPDATE usuarios SET nombre_oficina = ? WHERE id_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_nombre, $id_usuario);
    $ok   = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

function actualizar_avatar_oficina(int $id_usuario, string $nombre_archivo): bool {
    $nombre_archivo = basename($nombre_archivo);
    if ($nombre_archivo === '') return false;

    $conn = conectar();
    $sql  = "UPDATE usuarios SET avatar_usuario = ? WHERE id_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nombre_archivo, $id_usuario);
    $ok   = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

//////////////////////////
// Registro (Público)
//////////////////////////
function registrar_usuario(string $email, string $clave): string {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = null;
    try {
        $conn = conectar();
        $conn->begin_transaction();

        $email = normalizar_email($email);
        if ($msg = validar_credenciales($email, $clave)) {
            $conn->rollback(); $conn->close(); return $msg;
        }

        // Unicidad
        $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email_usuario=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()) {
            $stmt->close(); $conn->rollback(); $conn->close();
            return "El email ya está registrado";
        }
        $stmt->close();

        // 1) Oficina
        $nombre_oficina = "Oficina de " . $email;
        $stmt1 = $conn->prepare("INSERT INTO oficinas (nombre) VALUES (?)");
        $stmt1->bind_param("s", $nombre_oficina);
        $stmt1->execute();
        $id_oficina = (int)$conn->insert_id;
        $stmt1->close();

        // 2) Usuario (Por defecto rol 'usuario')
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        // NOTA: Usamos 'password_usuario' para unificar con el panel admin
        $stmt2 = $conn->prepare(
            "INSERT INTO usuarios (email_usuario, password_usuario, avatar_usuario, estado_usuario, id_oficina, nombre_oficina, rol)
             VALUES (?, ?, 'noavatar.png', 'activo', ?, ?, 'usuario')"
        );
        $stmt2->bind_param("ssis", $email, $hash, $id_oficina, $nombre_oficina);
        $stmt2->execute();
        $id_usuario = (int)$conn->insert_id;
        $stmt2->close();

        asegurar_version_legal($conn, LEGAL_VERSION);
        guardar_aceptacion_terminos($conn, $id_usuario, LEGAL_VERSION); 
        
        $conn->commit(); $conn->close();
        return "Usuario registrado correctamente.";

    } catch (mysqli_sql_exception $e) {
        if ($conn) { $conn->rollback(); $conn->close(); }
        return "Error al registrar: " . $e->getMessage();
    }
}
// --- AGREGAR AL FINAL DE funciones.php ---

// Actualizar el color del tema de la oficina
function actualizar_color_tema(int $id_usuario, string $nuevo_color): bool {
    // Validar que sea un hex code válido (ej: #ff0000)
    if (!preg_match('/^#[a-f0-9]{6}$/i', $nuevo_color)) {
        return false;
    }
    $conn = conectar();
    $sql  = "UPDATE usuarios SET color_tema = ? WHERE id_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_color, $id_usuario);
    $ok   = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

/* ==========================================================
   GESTIÓN DE AGENDA (Para base de datos)
   ========================================================== */

// Obtener todos los eventos de un usuario
function obtener_eventos_usuario(int $id_usuario): array {
    $conn = conectar();
    // Asegúrate de que la tabla 'eventos' exista con estas columnas
    $sql = "SELECT id, title, start, end, phone, references FROM eventos WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $eventos = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $eventos;
}

// Guardar un nuevo evento para un usuario
function guardar_evento_usuario(int $id_usuario, string $title, string $start, ?string $end, string $phone, string $references): bool {
    $conn = conectar();
    $sql = "INSERT INTO eventos (id_usuario, title, start, end, phone, references) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $id_usuario, $title, $start, $end, $phone, $references);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

// NOTA: Faltaría implementar `actualizar_evento_por_id` y `eliminar_evento_por_id`
// que recibirían el ID del evento y el ID del usuario para seguridad.


error_log("OVNI funciones.php cargado OK.");
?>