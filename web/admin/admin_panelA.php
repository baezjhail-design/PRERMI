<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
if(!isset($_SESSION['admin_id'])) header("Location:/PRERMI/web/admin/loginA.php");
if($_SESSION['admin_rol'] !== 'admin'){ echo "No autorizado"; exit; }

if(isset($_GET['approve'])){ $id=intval($_GET['approve']); $conn->query("UPDATE usuarios_admin SET active=1 WHERE id=$id"); header("Location: admin_panelA.php"); exit; }
if(isset($_GET['deny'])){ $id=intval($_GET['deny']); $conn->query("DELETE FROM usuarios_admin WHERE id=$id"); header("Location: admin_panelA.php"); exit; }

$res = $conn->query("SELECT id,usuario,email,created_at FROM usuarios_admin WHERE verified=1 AND active=0 ORDER BY created_at ASC");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Panel Admin</title></head><body>
<h2>Panel — Aprobar usuarios</h2>
<p>Admin: <?=htmlspecialchars($_SESSION['admin_user'])?> | <a href="/PRERMI/API/admin/logoutA.php">Salir</a></p>
<table border="1" cellpadding="6"><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Acción</th></tr>
<?php while($r=$res->fetch_assoc()): ?>
<tr>
<td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['usuario']) ?></td><td><?= htmlspecialchars($r['email']) ?></td>
<td><a href="?approve=<?= $r['id'] ?>">Aprobar</a> | <a href="?deny=<?= $r['id'] ?>">Eliminar</a></td>
</tr>
<?php endwhile; ?>
</table>
</body></html>
