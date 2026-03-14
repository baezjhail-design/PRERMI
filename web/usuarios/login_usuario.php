<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script></head>
<body>

<header>
    <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" class="header-logo">
    <p>Acceso de Usuarios</p>
    <button id="btnTheme" class="btn-theme-user" onclick="toggleTheme()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
</header>

<section class="form-container">

    <h2>Iniciar Sesión</h2>

    <form id="loginForm">

        <div class="input-group">
            <label>Usuario</label>
            <input type="text" id="usuario" required>
        </div>

        <div class="input-group">
            <label>Clave</label>
            <input type="password" id="clave" required>
        </div>

        <button type="submit" class="btn">Entrar</button>

        <p class="link">¿No tienes cuenta?
            <a href="index_usuario.php">Regístrate aquí</a>
        </p>

    </form>

    <p id="msg"></p>

</section>

<script>
document.getElementById("loginForm").addEventListener("submit", async function(e){
    e.preventDefault();

    let data = {
        usuario: document.getElementById("usuario").value,
        clave: document.getElementById("clave").value
    };

    let resp = await fetch("../../api/usuarios/login.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify(data)
    });

    let json = await resp.json();

    if (resp.status === 403) {
        document.getElementById("msg").innerHTML = '<div class="msg-verify">✉️ ' + json.msg + '</div>';
        return;
    }

    if (json.success) {
        // Guardar en sesión vía PHP
        fetch("sesion_usuario.php", {
            method: "POST",
            headers: {"Content-Type":"application/json"},
            body: JSON.stringify(json.user)
        }).then(() => {
            window.location = "dashboard_usuario.php";
        });

    } else {
        document.getElementById("msg").innerHTML = '<div class="msg-error">❌ ' + json.msg + '</div>';
    }
});
</script>

<script src="/PRERMI/web/assets/js/theme.js"></script>
</body>
</html>
