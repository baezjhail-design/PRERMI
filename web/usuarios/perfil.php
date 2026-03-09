<?php
// web/usuarios/perfil.php - ejemplo que muestra datos del usuario (requiere cedula GET)
$cedula = $_GET['cedula'] ?? '';
if (!$cedula) { echo "Falta cédula"; exit; }
$data = json_decode(file_get_contents("http://SERVER_HOST/PRERMI/API/usuarios/obtener_usuario.php?cedula=".$cedula), true);
if (!$data['success']) { echo "No encontrado"; exit; }
$user = $data['data'];
?>
<!doctype html><html><head><meta charset="utf-8"><title>Perfil</title></head><body>
<h2>Perfil: <?=htmlspecialchars($user['nombre'])?></h2>
<p>Cédula: <?=htmlspecialchars($user['cedula'])?></p>
<p>Email: <?=htmlspecialchars($user['email'])?></p>
</body></html>
