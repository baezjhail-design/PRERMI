<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Administrador PRERMI</title>
</head>
<body>
    <h2>Registro de Administradores</h2>

    <form action="/PRERMI/api/admin/registerA_submit.php" method="POST">
        
        <label>Usuario:</label><br>
        <input type="text" name="usuario" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="clave" required><br><br>

        <button type="submit">Registrar</button>
    </form>

    <br>
    <a href="loginA.php">¿Ya tienes cuenta? Inicia sesión</a>
</body>
</html>
