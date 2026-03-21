<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Administrador PRERMI</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; padding: 30px; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        h2 { margin-top: 0; }
        label { display:block; margin: 14px 0 6px; font-weight: 600; }
        input { width: 100%; box-sizing: border-box; padding: 12px; border:1px solid #d6dbe7; border-radius: 8px; }
        button { margin-top: 18px; width: 100%; padding: 12px; border:0; border-radius: 8px; background:#6c63ff; color:#fff; font-weight:700; cursor:pointer; }
        .msg { margin-bottom: 14px; padding: 12px; border-radius: 8px; display:none; }
        .msg.ok { background:#e8f7ee; color:#17663a; display:block; }
        .msg.err { background:#fdecec; color:#8f1f1f; display:block; }
        a { display:inline-block; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Registro de Administradores</h2>
        <div id="msg" class="msg"></div>

        <form id="registerAdminForm">
            <label>Usuario:</label>
            <input type="text" name="usuario" id="usuario" minlength="4" maxlength="30" pattern="[A-Za-z0-9._-]{4,30}" required>

            <label>Email:</label>
            <input type="email" name="email" id="email" maxlength="120" required>

            <label>Contraseña:</label>
            <input type="password" name="clave" id="clave" minlength="8" maxlength="100" required>

            <button type="submit" id="btnSubmit">Registrar</button>
        </form>

        <a href="loginA.php">¿Ya tienes cuenta? Inicia sesión</a>
    </div>

    <script>
        const form = document.getElementById('registerAdminForm');
        const msg = document.getElementById('msg');
        const btn = document.getElementById('btnSubmit');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            msg.className = 'msg';
            msg.style.display = 'none';

            const payload = {
                usuario: document.getElementById('usuario').value.trim().toLowerCase(),
                email: document.getElementById('email').value.trim().toLowerCase(),
                clave: document.getElementById('clave').value
            };

            if (!/^[a-z0-9._-]{4,30}$/.test(payload.usuario)) {
                msg.textContent = 'El usuario debe tener 4-30 caracteres válidos.';
                msg.className = 'msg err';
                return;
            }

            if (payload.clave.length < 8) {
                msg.textContent = 'La contraseña debe tener al menos 8 caracteres.';
                msg.className = 'msg err';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Registrando...';

            try {
                const res = await fetch('/PRERMI/api/admin/registerA.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    form.style.display = 'none';
                    msg.innerHTML = (data.message || data.msg || 'Registro completado.') +
                        '<br><br><strong style="color:#17663a;">&#128231; Revisa tu correo electrónico para verificar tu cuenta.</strong>' +
                        '<br><span style="display:inline-block;margin-top:8px;background:#fff8e1;border:1px solid #f59e0b;border-radius:6px;padding:8px 12px;font-size:0.88rem;">&#9888;&#65039; <strong>Si no encuentras el correo, revisa tu carpeta de <span style="color:#d97706;">Spam / Correo no deseado</span>.</strong></span>';
                    msg.className = 'msg ok';
                } else {
                    msg.textContent = data.message || data.msg || 'Error en el proceso.';
                    msg.className = 'msg err';
                }
                if (data.success) form.reset();
            } catch (err) {
                msg.textContent = 'No se pudo conectar con el servidor.';
                msg.className = 'msg err';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Registrar';
            }
        });
    </script>
</body>
</html>
