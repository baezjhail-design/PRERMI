<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PRERMI - Login QR</title>
</head>
<body>
    <h1>Iniciar Sesión</h1>

    <?php
    $contenedor = $_GET['contenedor'] ?? '';
    $token      = $_GET['token'] ?? '';
    ?>

    <form method="POST" action="../backend/auth_CQR.php">
        <input type="hidden" name="contenedor" value="<?php echo htmlspecialchars($contenedor); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <input type="email" name="email" placeholder="Correo electrónico" required><br>
        <input type="password" name="clave" placeholder="Clave" required><br>
        <button type="submit">Ingresar</button>
    </form>
</body>
</html>
