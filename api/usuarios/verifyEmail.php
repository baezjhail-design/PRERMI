<?php
// api/usuarios/verifyEmail.php - Verificar email de usuario

require_once __DIR__ . '/../utils.php';

header("Content-Type: text/html; charset=UTF-8");

$token = isset($_GET['token']) ? trim($_GET['token']) : null;

if (!$token) {
    showVerificationPage(false, 'Token invalido o no proporcionado');
}

try {
    $pdo = getPDO();

    // Buscar usuario con este token y token activo
    $stmt = $pdo->prepare("SELECT id, usuario, email FROM usuarios WHERE token = ? AND token_activo = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        showVerificationPage(false, 'Token no encontrado o ya utilizado');
    }

    // Marcar como verificado y desactivar token
    $stmt = $pdo->prepare("UPDATE usuarios SET verified = 1, token_activo = 0, token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    registrarLog("Email verificado para usuario: {$user['usuario']}", "info");
    showVerificationPage(true, 'Tu correo ha sido verificado exitosamente. Ya puedes iniciar sesion.');

} catch (PDOException $e) {
    error_log('User email verification error: ' . $e->getMessage());
    showVerificationPage(false, 'Error en la verificacion. Intenta de nuevo.');
}

function showVerificationPage($success, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $success ? 'Verificacion Exitosa' : 'Error de Verificacion'; ?> - PRERMI</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 20px;
            }

            .verification-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                overflow: hidden;
                max-width: 500px;
                text-align: center;
                padding: 50px 30px;
            }

            .success-icon {
                font-size: 80px;
                color: #51cf66;
                margin-bottom: 20px;
            }

            .error-icon {
                font-size: 80px;
                color: #ff6b6b;
                margin-bottom: 20px;
            }

            .verification-card h1 {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
                color: #333;
            }

            .verification-card p {
                color: #666;
                margin-bottom: 30px;
                font-size: 16px;
                line-height: 1.6;
            }

            .btn-back {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 12px 40px;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s ease;
                display: inline-block;
            }

            .btn-back:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="verification-card">
            <?php if ($success): ?>
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Verificacion Exitosa</h1>
            <?php else: ?>
                <div class="error-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h1>Error en la Verificacion</h1>
            <?php endif; ?>

            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="../../index.php" class="btn-back">Volver al Sitio</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

