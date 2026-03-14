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
    <title>Tarjeta Digital - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">

    <style>
        .qr-box {
            width: 400px;
            background: white;
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .qr-box h2 {
            color: #0077aa;
            margin-bottom: 20px;
        }
        .qr-box p {
            color: #666;
            margin-bottom: 25px;
        }
        .qr-display {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
</header>

<div class="qr-box">
    <h2><i class="fas fa-qrcode"></i> Tarjeta de Escaneo QR</h2>

    <p>Escanea este código QR en los contenedores de reciclaje para registrar tus depósitos</p>
    
    <div class="qr-display">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo $_SESSION['usuario_id']; ?>" alt="QR Code" />
    </div>

    <p style="margin-top: 25px; font-size: 0.9em; color: #999;">
        Este código QR te identifica en el sistema
    </p>

    <p>
        <a href="dashboard_usuario.php">← Volver al Dashboard</a>
    </p>
</div>

</body>
</html>
