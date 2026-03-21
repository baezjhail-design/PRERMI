<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
if(!isset($_SESSION['admin_id'])) { header("Location:/PRERMI/web/admin/loginA.php"); exit; }
if(($_SESSION['admin_rol'] ?? '') !== 'admin'){ echo "No autorizado"; exit; }

if (empty($_SESSION['csrf_admin_panelA'])) {
	$_SESSION['csrf_admin_panelA'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$csrf = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_admin_panelA'], $csrf)) {
		http_response_code(403);
		echo "Solicitud inválida";
		exit;
	}

	$action = $_POST['action'] ?? '';
	$id = intval($_POST['admin_id'] ?? 0);

	if ($id > 0 && $action === 'approve') {
		$stmt = $conn->prepare("UPDATE usuarios_admin SET active=1 WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
	}

	if ($id > 0 && $action === 'deny') {
		$stmt = $conn->prepare("DELETE FROM usuarios_admin WHERE id=?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
	}

	header("Location: admin_panelA.php");
	exit;
}

$res = $conn->query("SELECT id,usuario,email,created_at FROM usuarios_admin WHERE verified=1 AND active=0 ORDER BY created_at ASC");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Panel Admin</title></head><body>
<h2>Panel — Aprobar usuarios</h2>
<p>Admin: <?=htmlspecialchars($_SESSION['admin_user'])?> | <a href="/PRERMI/API/admin/logoutA.php">Salir</a></p>
<table border="1" cellpadding="6"><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Acción</th></tr>
<?php while($r=$res->fetch_assoc()): ?>
<tr>
<td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['usuario']) ?></td><td><?= htmlspecialchars($r['email']) ?></td>
<td>
	<form method="post" style="display:inline; margin:0;">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_admin_panelA']) ?>">
		<input type="hidden" name="admin_id" value="<?= intval($r['id']) ?>">
		<input type="hidden" name="action" value="approve">
		<button type="submit">Aprobar</button>
	</form>
	|
	<form method="post" style="display:inline; margin:0;" onsubmit="return confirm('¿Eliminar este administrador pendiente?');">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_admin_panelA']) ?>">
		<input type="hidden" name="admin_id" value="<?= intval($r['id']) ?>">
		<input type="hidden" name="action" value="deny">
		<button type="submit">Eliminar</button>
	</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</body></html>
