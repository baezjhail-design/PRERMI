<?php
// scripts/crear_adminA.php - ejecutar 1 vez y luego BORRAR
require_once __DIR__ . '/../config/db_config.php';
$usuario='superadmin'; $email='admin@local'; $pass_hash = password_hash('Admin1234', PASSWORD_DEFAULT); $rol='admin';
$stmt = $conn->prepare("INSERT INTO usuarios_admin (usuario,email,clave,rol,verified,active) VALUES (?,?,?,?,1,1)");
$stmt->bind_param("ssss",$usuario,$email,$pass_hash,$rol);
if($stmt->execute()) echo "Admin creado. BORRA este archivo.";
else echo "Error: ".$conn->error;
