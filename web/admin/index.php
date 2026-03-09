<?php
session_start();
if(!isset($_SESSION['admin_id'])) header("Location:/PRERMI/web/admin/loginA.php");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin - Inicio</title></head><body>
<h2>Bienvenido <?=htmlspecialchars($_SESSION['admin_user'])?></h2>
<ul>
  <li><a href="/PRERMI/web/admin/admin_panelA.php">Panel de aprobaciones</a></li>
  <li><a href="/PRERMI/web/monitoreo/vehiculos.php">Monitoreo</a></li>
  <li><a href="/PRERMI/API/admin/logoutA.php">Cerrar sesión</a></li>
</ul>
</body></html>
