<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/style.css">
    <link rel="stylesheet" href="/PRERMI/web/assets/css/theme.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        .login-header {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header i {
            font-size: 2.5rem;
        }

        .login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .login-body {
            padding: 3rem 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .login-footer {
            text-align: center;
            padding: 2rem;
            border-top: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .login-footer p {
            margin: 0;
            color: #666;
        }

        .login-footer a {
            color: #2563eb;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .login-footer a:hover {
            color: #7c3aed;
        }

        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .back-link:hover {
            gap: 12px;
        }

        .alert {
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            border-left: 4px solid #2563eb;
        }

        .alert-success {
            background: rgba(81, 207, 102, 0.1);
            color: #51cf66;
            border-left: 4px solid #51cf66;
        }

        .admin-badge {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
    </style>
    <script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <a href="/PRERMI/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>

            <!-- Theme toggle button -->
            <div style="text-align:right;margin-bottom:.5rem;">
              <button onclick="toggleTheme()" id="btnTheme" title="Cambiar tema" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);padding:.4rem .85rem;border-radius:8px;cursor:pointer;font-size:.88rem;font-weight:600;transition:all .2s;">
                <i class="fas fa-moon"></i> Tema
              </button>
            </div>

            <div class="login-card">
                <div class="login-header">
                    <div class="mb-3">
                        <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="PRERMI" style="height:80px;width:auto;background:#fff;border-radius:12px;padding:6px 10px;box-shadow:0 4px 15px rgba(0,0,0,.25);">
                    </div>
                    <h1>Administrador</h1>
                    <p>Panel de control exclusivo</p>
                    <span class="admin-badge">ACCESO RESTRINGIDO</span>
                </div>

                <div class="login-body">
                    <div id="msg"></div>

                    <form id="loginForm">
                        <div class="form-group">
                            <label class="form-label" for="usuario">
                                <i class="fas fa-user"></i> Usuario Administrativo
                            </label>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                   placeholder="admin" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="clave">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave" name="clave" 
                                   placeholder="********" required>
                        </div>

                        <button type="submit" class="btn-login" id="btnLogin">
                            <i class="fas fa-sign-in-alt"></i> Acceder al Panel
                        </button>
                    </form>

                    <div class="alert alert-info" style="margin-top: 1.5rem;">
                        <i class="fas fa-lock"></i>
                        <span>Este panel es solo para administradores. <a href="/PRERMI/index.php" style="color: inherit; font-weight: 600;">Usuario común</a></span>
                    </div>
                </div>

                <div class="login-footer">
                    <p><i class="fas fa-shield-alt"></i> ¿Deseas registrarte como administrador? 
                        <a href="register.php">
                            Solicitar acceso <i class="fas fa-arrow-right"></i>
                        </a>
                    </p>
                    <hr style="opacity: 0.3;">
                    <p><i class="fas fa-info-circle"></i> ¿No eres administrador? 
                        <a href="/PRERMI/index.php">
                            Volver al inicio <i class="fas fa-arrow-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PRERMI/web/assets/js/theme.js"></script>
    <script>
        document.getElementById("loginForm").addEventListener("submit", async function(e){
            e.preventDefault();
            
            const btnLogin = document.getElementById("btnLogin");
            const msgDiv = document.getElementById("msg");
            
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Autenticando...';

            let form = new FormData(this);

            try {
                let res = await fetch("../../api/admin/loginA_submit.php", {
                    method: "POST",
                    body: form
                });

                let data = await res.json();

                if (data.success) {
                    msgDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Login exitoso, redirigiendo...</div>';
                    setTimeout(() => {
                        window.location.href = "dashboard.php";
                    }, 1000);
                } else {
                    msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (data.msg || 'Error en la autenticación') + '</div>';
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = '<i class="fas fa-sign-in-alt"></i> Acceder al Panel';
                }
            } catch (error) {
                msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión</div>';
                btnLogin.disabled = false;
                btnLogin.innerHTML = '<i class="fas fa-sign-in-alt"></i> Acceder al Panel';
            }
        });
    </script>
</body>
</html>
 



