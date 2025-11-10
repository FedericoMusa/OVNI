<?php
require __DIR__ . "/funciones.php";
$conn = conectar();
$conn->begin_transaction();

asegurar_version_legal($conn, LEGAL_VERSION);

// usa el último usuario creado para probar la FK
$res = $conn->query("SELECT id_usuario FROM usuarios ORDER BY id_usuario DESC LIMIT 1");
$row = $res->fetch_assoc();
$userId = (int)$row['id_usuario'];

guardar_aceptacion_terminos($conn, $userId, LEGAL_VERSION);
$conn->commit();

echo "OK";
