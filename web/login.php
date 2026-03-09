<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
              box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
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
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .login-footer a:hover {
              color: #764ba2;
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

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .remember-me input {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <a href="/PRERMI/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>

            <div class="login-card">
                <div class="login-header">
                    <h1>
                        <i class="fas fa-user-circle"></i> Usuario
                    </h1>
                    <p>Acceso a tu cuenta</p>
                </div>

                <div class="login-body">
                    <div id="msg"></div>

                    <form id="loginForm">
                        <div class="form-group">
                            <label class="form-label" for="usuario">
                                <i class="fas fa-user"></i> Nombre de Usuario
                            </label>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                   placeholder="usuario" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="clave">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave" name="clave" 
                                   placeholder="********" required>
                        </div>

                        <button type="submit" class="btn-login" id="btnLogin">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </form>

                    <div class="alert alert-info" style="margin-top: 1.5rem;">
                        <i class="fas fa-info-circle"></i>
                        <span>¿Olvidaste tu contraseña? <a href="#" style="color: inherit; font-weight: 600;">Recupérala aquí</a></span>
                    </div>
                </div>

                <div class="login-footer">
                    <p>¿No tienes cuenta? 
                        <a href="register.php">
                            Regístrate ahora <i class="fas fa-arrow-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').then(function(reg){
                console.log('SW registered', reg.scope);
            }).catch(function(err){
                console.warn('SW registration failed', err);
            });
        }
    </script>
    <script>
        document.getElementById("loginForm").addEventListener("submit", async function(e){
            e.preventDefault();
            
            const btnLogin = document.getElementById("btnLogin");
            const msgDiv = document.getElementById("msg");
            const originalText = btnLogin.innerHTML;
            
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Autenticando...';

            const formData = {
                usuario: document.getElementById('usuario').value,
                clave: document.getElementById('clave').value
            };

            try {
                const res = await fetch("../api/usuarios/login.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(formData)
                });

                const data = await res.json();

                if (data.success) {
                    msgDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Login exitoso, redirigiendo...</div>';
                    setTimeout(() => {
                        window.location.href = "user-dashboard.php";
                    }, 1500);
                } else {
                    // Check if it's an email verification issue (HTTP 403)
                    if (res.status === 403) {
                        msgDiv.innerHTML = '<div class="alert alert-warning" style="flex-direction: column; text-align: center;">' +
                            '<div style="font-size: 2.5rem; margin-bottom: 10px;"><i class="fas fa-envelope-open-text"></i></div>' +
                            '<strong>Cuenta no verificada</strong><br>' +
                            '<span style="font-size: 0.9rem;">' + (data.msg || 'Debes verificar tu correo antes de iniciar sesión.') + '</span>' +
                            '</div>';
                    } else {
                        msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (data.msg || 'Error en la autenticación') + '</div>';
                    }
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al servidor</div>';
                btnLogin.disabled = false;
                btnLogin.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>


