<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">

    <style>
        .perfil-box {
            width: 500px;
            background: white;
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
        }
        .perfil-box h3 {
            color: #0088aa;
        }
    </style>
</head>

<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
</header>

<div class="perfil-box">
    <h2>Mi Perfil</h2>

    <h3>Nombre:</h3>
    <p><?php echo $_SESSION['usuario_nombre']; ?></p>

    <p style="margin-top:20px;">
        <a href="dashboard_usuario.php">← Volver</a>
    </p>
</div>

</body>
</html>
