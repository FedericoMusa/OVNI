<?php
function conectar() {
    $conn = new mysqli("localhost", "root", "", "OVNI");
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    return $conn;
}

function registrar_usuario($email, $clave) {
    $conn = conectar();
    $email = $conn->real_escape_string($email);
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

    // Verificar si el email ya existe
    $sql = "SELECT * FROM usuarios WHERE email_usuario='$email'";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        return "El email ya está registrado";
    }

    $sql = "INSERT INTO usuarios (email_usuario, clave_usuario, avatar_usuario, estado_usuario)
            VALUES ('$email', '$clave_hash', 'noavatar.png', 'activo')";
    if ($conn->query($sql) === TRUE) {
        return "Usuario registrado correctamente";
    } else {
        return "Error: " . $conn->error;
    }
}

function login_usuario($email, $clave) {
    $conn = conectar();
    $email = $conn->real_escape_string($email);
    $sql = "SELECT * FROM usuarios WHERE email_usuario='$email'";
    $res = $conn->query($sql);
    if ($res->num_rows == 1) {
        $user = $res->fetch_assoc();
        if (password_verify($clave, $user['clave_usuario'])) {
            return $user;
        }
    }
    return false;
}
?>