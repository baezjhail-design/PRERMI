<?php
// index_usuario.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - PRERMI</title>

    <!-- Asegúrate de que este archivo existe -->
    <link rel="stylesheet" href="estilos_usuario.css">
</head>

<body>

<header>
    <h1>PRERMI</h1>
    <p>Programa de Reducción de Residuos y Manejo Inteligente</p>
</header>

<section class="form-container">

    <h2>Registro de Usuario</h2>

    <form id="registerForm">

        <div class="input-group">
            <label>Nombre</label>
            <input type="text" id="nombre" required>
        </div>

        <div class="input-group">
            <label>Apellido</label>
            <input type="text" id="apellido" required>
        </div>

        <div class="input-group">
            <label>Usuario</label>
            <input type="text" id="usuario" required>
        </div>

        <div class="input-group">
            <label>Cédula</label>
            <input type="text" id="cedula" required>
        </div>

        <div class="input-group">
            <label>Email <span style="color: #ff4444;">*</span></label>
            <input type="email" id="email" required>
            <small style="color: #666; font-size: 0.8rem;">Se enviar\u00e1 un correo de verificaci\u00f3n</small>
        </div>

        <div class="input-group">
            <label>Teléfono</label>
            <input type="text" id="telefono">
        </div>

        <div class="input-group">
            <label>Clave</label>
            <input type="password" id="clave" required>
        </div>

        <button type="submit" class="btn">Registrarme</button>

        <p class="link">
            ¿Ya tienes cuenta? <a href="login_usuario.php">Inicia sesión aquí</a>
        </p>

    </form>

    <p id="msg"></p>

</section>


<script>
// ----------------------------
//  REGISTRO USUARIO PRERMI
// ----------------------------
document.getElementById("registerForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    console.log("🔥 EVENTO DETECTADO: El formulario sí ejecuta JS");

    let data = {
        nombre:   document.getElementById("nombre").value.trim(),
        apellido: document.getElementById("apellido").value.trim(),
        usuario:  document.getElementById("usuario").value.trim(),
        cedula:   document.getElementById("cedula").value.trim(),
        email:    document.getElementById("email").value.trim(),
        telefono: document.getElementById("telefono").value.trim(),
        clave:    document.getElementById("clave").value.trim()
    };

    console.log("📤 ENVIANDO JSON AL SERVIDOR:", data);

    // ✔ NGROK – RUTA ABSOLUTA
    let resp = await fetch("/PRERMI/api/usuarios/register.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    });

    let texto = await resp.text();
    console.log("📥 RESPUESTA CRUD:", texto);

    let json = {};
    try {
        json = JSON.parse(texto);
    } catch(err) {
        document.getElementById("msg").innerHTML =
            "❌ Error inesperado. El servidor devolvió HTML, no JSON.";
        console.error("ERROR JSON:", texto);
        return;
    }

    if (json.success) {
        document.getElementById("registerForm").style.display = "none";
        document.getElementById("msg").innerHTML =
            '<div class="msg-success">✅ Registro exitoso.<br>Se envió un correo de verificación a <b>' + data.email + '</b>.<br>Debes verificar tu email antes de iniciar sesión.</div>' +
            '<p class="link" style="margin-top:15px;"><a href="login_usuario.php">Ir a Iniciar Sesión</a></p>';
    } else {
        document.getElementById("msg").innerHTML =
            '<div class="msg-error">❌ Error: ' + json.msg + '</div>';
    }
});
</script>

</body>
</html>
