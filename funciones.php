<?php
/**
 * funciones.php
 * Utilidades de conexión y funciones base para esquema multi-oficina (ovni_dev).
 *
 * Requiere las tablas:
 * - oficinas(id_oficina PK, nombre, creado_en)
 * - usuarios(id_usuario PK, email_usuario UNIQUE, clave_usuario, avatar_usuario, estado_usuario, id_oficina FK NULLABLE, creado_en)
 * - clientes(id_cliente PK, ..., id_oficina FK)
 * - proyectos(id_proyecto PK, ..., id_cliente FK, id_oficina FK, creado_por FK usuarios)
 * - documentos(...) (opcional aquí)
 */

//////////////////////////
// Conexión a la BD
//////////////////////////
function conectar(): mysqli {
    // Lanza excepciones ante errores (mejor manejo)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "ovni_dev"; // usar minúsculas para evitar problemas en Linux

    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    return $conn;
}

//////////////////////////
// Helpers generales
//////////////////////////
function normalizar_email(string $email): string {
    return trim(mb_strtolower($email));
}

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
// Helpers de sesión / tenant
// (Asegúrate de hacer session_start() en tus controladores)
//////////////////////////
function oficina_id_actual(): ?int {
    return $_SESSION['user']['id_oficina'] ?? null; // null => super_admin
}

function usuario_id_actual(): ?int {
    return $_SESSION['user']['id_usuario'] ?? null;
}

function es_super_admin(): bool {
    return is_null(oficina_id_actual());
}

//////////////////////////
// Alta de usuario (crea OFICINA + USUARIO en una transacción)
//////////////////////////
function registrar_usuario(string $email, string $clave): string {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = null;
    try {
        $conn = conectar();
        $conn->begin_transaction();

        $email = normalizar_email($email);

        if ($msg = validar_credenciales($email, $clave)) {
            $conn->rollback();
            $conn->close();
            return $msg;
        }

        // Unicidad de email (además del UNIQUE en BD)
        $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email_usuario=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()) {
            $stmt->close();
            $conn->rollback();
            $conn->close();
            return "El email ya está registrado";
        }
        $stmt->close();

        // 1) Crear oficina
        $nombre_oficina = "Oficina de " . $email;
        $stmt1 = $conn->prepare("INSERT INTO oficinas (nombre) VALUES (?)");
        $stmt1->bind_param("s", $nombre_oficina);
        $stmt1->execute();
        $id_oficina = (int)$conn->insert_id;
        $stmt1->close();

        // 2) Crear usuario asociado
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare(
            "INSERT INTO usuarios (email_usuario, clave_usuario, avatar_usuario, estado_usuario, id_oficina)
             VALUES (?, ?, 'noavatar.png', 'activo', ?)"
        );
        $stmt2->bind_param("ssi", $email, $hash, $id_oficina);
        $stmt2->execute();
        $id_usuario = (int)$conn->insert_id;
        $stmt2->close();

        $conn->commit();
        $conn->close();
        return "Oficina #$id_oficina creada y usuario #$id_usuario registrado correctamente";

    } catch (mysqli_sql_exception $e) {
        if ($conn) {
            $conn->rollback();
            $conn->close();
        }
        if ($e->getCode() === 1062) {
            return "El email ya está registrado";
        }
        return "Error al registrar: " . $e->getMessage();
    }
}

//////////////////////////
// Alta de SUPER ADMIN (sin oficina)
//////////////////////////
function registrar_super_admin(string $email, string $clave): string {
    try {
        $conn = conectar();
        $email = normalizar_email($email);
        if ($msg = validar_credenciales($email, $clave)) {
            $conn->close(); return $msg;
        }
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO usuarios (email_usuario, clave_usuario, avatar_usuario, estado_usuario, id_oficina)
             VALUES (?, ?, 'noavatar.png', 'activo', NULL)"
        );
        $stmt->bind_param("ss", $email, $hash);
        $stmt->execute();
        $stmt->close(); $conn->close();
        return "Super admin creado.";
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) return "El email ya está registrado";
        return "Error al registrar super admin: ".$e->getMessage();
    }
}

//////////////////////////
// Login (devuelve datos del usuario SIN el hash)
//////////////////////////
function login_usuario(string $email, string $clave) {
    try {
        $conn  = conectar();
        $email = normalizar_email($email);

        $sql = "SELECT id_usuario, email_usuario, clave_usuario, avatar_usuario, estado_usuario, id_oficina
                FROM usuarios
                WHERE email_usuario = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();

        $stmt->close();
        $conn->close();

        if (!$user) return false;                       // no existe
        if ($user['estado_usuario'] !== 'activo') return false; // no activo
        if (!password_verify($clave, $user['clave_usuario'])) return false; // clave mal

        // Rehash transparente si cambió el algoritmo por defecto
        if (password_needs_rehash($user['clave_usuario'], PASSWORD_DEFAULT)) {
            try {
                $nuevoHash = password_hash($clave, PASSWORD_DEFAULT);
                $conn = conectar();
                $upd  = $conn->prepare("UPDATE usuarios SET clave_usuario=? WHERE id_usuario=?");
                $upd->bind_param("si", $nuevoHash, $user['id_usuario']);
                $upd->execute();
                $upd->close();
                $conn->close();
            } catch (mysqli_sql_exception $e) { /* no romper login */ }
        }

        unset($user['clave_usuario']); // nunca exponer el hash
        return $user; // array asociativo con id_oficina incluido

    } catch (mysqli_sql_exception $e) {
        return false; // en login devolvemos false ante error
    }
}

// IMPORTANTE: NO seteamos $_SESSION aquí.
// Hacelo en tu controlador después de login exitoso:
//   session_start();
//   $user = login_usuario($email, $clave);
//   if ($user) {
//       $_SESSION['user'] = [
//           'id_usuario' => (int)$user['id_usuario'],
//           'email'      => $user['email_usuario'],
//           'id_oficina' => isset($user['id_oficina']) ? (int)$user['id_oficina'] : null,
//           'estado'     => $user['estado_usuario'],
//           'avatar'     => $user['avatar_usuario'],
//       ];
//   }

//////////////////////////
// Utilidades de usuarios
//////////////////////////

/** TRUE si el email ya existe */
function usuario_existe(string $email): bool {
    try {
        $conn  = conectar();
        $email = normalizar_email($email);

        $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email_usuario=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        $existe = $stmt->num_rows > 0;

        $stmt->close();
        $conn->close();
        return $existe;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

/** Cambia estado del usuario: 'activo' | 'inactivo' | 'bloqueado' */
function cambiar_estado_usuario(int $id_usuario, string $nuevo_estado): string {
    $permitidos = ['activo','inactivo','bloqueado'];
    if (!in_array($nuevo_estado, $permitidos, true)) {
        return "Estado inválido.";
    }
    try {
        $conn = conectar();
        $stmt = $conn->prepare("UPDATE usuarios SET estado_usuario=? WHERE id_usuario=?");
        $stmt->bind_param("si", $nuevo_estado, $id_usuario);
        $stmt->execute();
        $filas = $stmt->affected_rows;

        $stmt->close();
        $conn->close();

        return $filas > 0 ? "Estado actualizado." : "No se encontró el usuario.";
    } catch (mysqli_sql_exception $e) {
        return "Error al actualizar estado: " . $e->getMessage();
    }
}

/** Actualiza la contraseña (requiere clave actual correcta). */
function actualizar_password(int $id_usuario, string $clave_actual, string $clave_nueva): string {
    if (strlen($clave_nueva) < 8) {
        return "La nueva contraseña debe tener al menos 8 caracteres.";
    }
    try {
        $conn = conectar();

        // Traer hash actual
        $stmt = $conn->prepare("SELECT clave_usuario FROM usuarios WHERE id_usuario=? LIMIT 1");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($clave_actual, $row['clave_usuario'])) {
            $conn->close();
            return "La contraseña actual es incorrecta.";
        }

        // Guardar nuevo hash
        $nuevoHash = password_hash($clave_nueva, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE usuarios SET clave_usuario=? WHERE id_usuario=?");
        $upd->bind_param("si", $nuevoHash, $id_usuario);
        $upd->execute();
        $ok = $upd->affected_rows > 0;
        $upd->close();
        $conn->close();

        return $ok ? "Contraseña actualizada." : "No se pudo actualizar la contraseña.";

    } catch (mysqli_sql_exception $e) {
        return "Error al actualizar contraseña: " . $e->getMessage();
    }
}

/** Obtiene un usuario por ID (sin devolver el hash). */
function obtener_usuario_por_id(int $id_usuario) {
    try {
        $conn = conectar();
        $stmt = $conn->prepare(
            "SELECT id_usuario, email_usuario, avatar_usuario, estado_usuario, id_oficina, creado_en
             FROM usuarios WHERE id_usuario=? LIMIT 1"
        );
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();

        $stmt->close();
        $conn->close();
        return $user ?: null;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}

///////////////////////////////////////////////////////////
// EJEMPLOS OPCIONALES (CRUD scopeado a la oficina actual)
///////////////////////////////////////////////////////////

/** Crea un cliente para la oficina del usuario logueado */
function crear_cliente(string $nombre, ?string $email, ?string $telefono, ?string $direccion): string {
    $id_oficina = oficina_id_actual();
    if (!$id_oficina) {
        return "No hay oficina en sesión (o es super admin).";
    }
    try {
        $conn = conectar();
        $sql = "INSERT INTO clientes (nombre, email, telefono, direccion, id_oficina)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nombre, $email, $telefono, $direccion, $id_oficina);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close(); $conn->close();
        return "Cliente #$id creado.";
    } catch (mysqli_sql_exception $e) {
        return "Error al crear cliente: ".$e->getMessage();
    }
}

/** Lista clientes de mi oficina */
function listar_clientes(): array {
    $id_oficina = oficina_id_actual();
    if (!$id_oficina) return []; // o manejar super_admin aparte

    try {
        $conn = conectar();
        $stmt = $conn->prepare("SELECT id_cliente, nombre, email, telefono, direccion, creado_en
                                FROM clientes
                                WHERE id_oficina = ?
                                ORDER BY id_cliente DESC");
        $stmt->bind_param("i", $id_oficina);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); $conn->close();
        return $rows;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

/** Crea un proyecto validando pertenencia del cliente a mi oficina */
function crear_proyecto(int $id_cliente, string $nombre, string $estado='planificado',
                        ?string $fecha_inicio=null, ?string $fecha_fin_estimada=null): string {
    $id_oficina = oficina_id_actual();
    $id_usuario = usuario_id_actual();
    if (!$id_oficina || !$id_usuario) return "Sesión inválida.";

    try {
        $conn = conectar();
        $conn->begin_transaction();

        // Verificar que el cliente pertenece a mi oficina
        $chk = $conn->prepare("SELECT 1 FROM clientes WHERE id_cliente=? AND id_oficina=?");
        $chk->bind_param("ii", $id_cliente, $id_oficina);
        $chk->execute();
        if (!$chk->get_result()->fetch_row()) {
            $chk->close(); $conn->rollback(); $conn->close();
            return "El cliente no pertenece a tu oficina.";
        }
        $chk->close();

        $ins = $conn->prepare(
           "INSERT INTO proyectos (nombre, id_cliente, id_oficina, estado, fecha_inicio, fecha_fin_estimada, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param("siisssi", $nombre, $id_cliente, $id_oficina, $estado, $fecha_inicio, $fecha_fin_estimada, $id_usuario);
        $ins->execute();
        $id = $conn->insert_id;
        $ins->close();

        $conn->commit(); $conn->close();
        return "Proyecto #$id creado.";
    } catch (mysqli_sql_exception $e) {
        if (isset($conn)) { $conn->rollback(); $conn->close(); }
        return "Error al crear proyecto: ".$e->getMessage();
    }
}
