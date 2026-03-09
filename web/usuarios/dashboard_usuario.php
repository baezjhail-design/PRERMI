<?php
session_start();

// Si no hay sesión, redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

$usuarioId = (int)$_SESSION['usuario_id'];
$fotoFacialPath = __DIR__ . '/../../uploads/rostros/face_' . $usuarioId . '.jpg';
$fotoFacialWeb = '../../uploads/rostros/face_' . $usuarioId . '.jpg';
$fotoFacialExiste = file_exists($fotoFacialPath);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Usuario PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">

    <style>
        .dashboard-container {
            width: 90%;
            margin: 30px auto;
        }

        .welcome {
            text-align: center;
            font-size: 26px;
            margin-bottom: 20px;
            color: white;
        }

        .card-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
        }

        .card {
            background: white;
            color: #004466;
            width: 250px;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0px 5px 15px #00000040;
            transition: 0.3s;
        }

        .card:hover {
            transform: scale(1.08);
            background: #e1ffff;
        }

        .btn-facial {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 1000;
            background: #004466;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 12px #00000040;
        }

        .btn-facial:hover {
            background: #006b8a;
        }

        .facial-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 1100;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .facial-modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 420px;
            width: 100%;
            padding: 18px;
            text-align: center;
            box-shadow: 0 8px 24px #00000055;
        }

        .facial-modal-content h3 {
            margin: 0 0 12px;
            color: #004466;
        }

        .facial-modal-content img {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #d7eef6;
        }

        .facial-modal-close {
            margin-top: 14px;
            border: none;
            border-radius: 8px;
            background: #cc0000;
            color: #fff;
            padding: 8px 16px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<button class="btn-facial" onclick="abrirModalFacial()">Ver Foto Facial</button>

<header>
    <h1>PRERMI</h1>
    <p>Dashboard de Usuario</p>
</header>

<div class="dashboard-container">

    <div class="welcome">
        Bienvenido, <b><?php echo $_SESSION['usuario_nombre']; ?></b>
    </div>

    <div class="card-grid">

        <a href="tarjeta_usuario.php" class="card">
            Tarjeta Digital
        </a>

        <a href="depositos_usuario.php" class="card">
            Mis Depósitos
        </a>

        <a href="sanciones_usuario.php" class="card">
            Mis Sanciones
        </a>

        <a href="perfil_usuario.php" class="card">
            Mi Perfil
        </a>

        <a href="logout_usuario.php" class="card" style="background:#cc0000; color:white;">
            Cerrar Sesión
        </a>

    </div>

</div>

<div id="facialModal" class="facial-modal" onclick="cerrarModalFacial(event)">
    <div class="facial-modal-content">
        <h3>Foto Facial Vinculada</h3>
        <?php if ($fotoFacialExiste): ?>
            <img src="<?php echo htmlspecialchars($fotoFacialWeb); ?>" alt="Foto facial del usuario">
        <?php else: ?>
            <p>No se encontro una foto facial vinculada para esta cuenta.</p>
        <?php endif; ?>
        <button class="facial-modal-close" onclick="cerrarModalFacial()">Cerrar</button>
    </div>
</div>

<script>
function abrirModalFacial() {
    document.getElementById('facialModal').style.display = 'flex';
}

function cerrarModalFacial(event) {
    if (!event || event.target.id === 'facialModal') {
        document.getElementById('facialModal').style.display = 'none';
    }
}
</script>

</body>
</html>
