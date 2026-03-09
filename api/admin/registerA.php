<?php
// api/admin/registerA.php - Registro de nuevos administradores

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../config/mailer.php';

// SET JSON HEADER FIRST - before any potential output
header("Content-Type: application/json; charset=UTF-8");

// Disable error display to avoid HTML mixed with JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Validar entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    jsonErr('JSON inválido', 400);
}

$required = ['usuario', 'email', 'clave'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonErr("Campo obligatorio: $field", 400);
    }
}

$usuario = trim($input['usuario']);
$email = trim($input['email']);
$nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
$apellido = isset($input['apellido']) ? trim($input['apellido']) : '';
$clave = $input['clave'];

// Validaciones
if (strlen($clave) < 8) {
    jsonErr('Contraseña debe tener al menos 8 caracteres', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonErr('Email inválido', 400);
}

if (strlen($usuario) < 3) {
    jsonErr('Usuario debe tener al menos 3 caracteres', 400);
}

try {
    $pdo = getPDO();
    
    // Verificar que el usuario no exista
    $stmt = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ? OR email = ?");
    $stmt->execute([$usuario, $email]);
    if ($stmt->rowCount() > 0) {
        jsonErr('Usuario o email ya registrado', 409);
    }
    
    // Generar token de verificación
    $verification_token = bin2hex(random_bytes(32));
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Insertar administrador NO VERIFICADO
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios_admin (usuario, email, clave, verification_token, verified, active, rol) 
         VALUES (?, ?, ?, ?, 0, 0, 'admin')"
    );
    
    $stmt->execute([
        $usuario,
        $email,
        $clave_hash,
        $verification_token
    ]);
    
    $admin_id = $pdo->lastInsertId();
    
    // Enviar email de confirmación
    try {
        $mail = getMailer();
        $fullName = trim((string)($nombre . ' ' . $apellido));
        if ($fullName === '') {
            $fullName = $usuario;
        }
        $mail->addAddress($email, $fullName);
        $mail->Subject = 'Verifica tu correo - Registro de Administrador PRERMI';
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $base_url = $protocol . '://' . $host . '/PRERMI';
        $verifyLink = $base_url . "/api/admin/verifyEmailTokenA.php?token=" . urlencode($verification_token);
        
        $mailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
                .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { color: #ff6b6b; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
                .button { background: #ff6b6b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .footer { color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
                .warning { background: #fff3cd; padding: 15px; border-radius: 5px; color: #856404; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='card'>
                    <div class='header'>¡Bienvenido, " . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . "!</div>
                    <p>Has solicitado una cuenta de administrador en PRERMI.</p>
                    
                    <div class='warning'>
                        <strong>Próximo paso:</strong> 
                        Haz clic en el botón de abajo para verificar tu correo electrónico.
                    </div>
                    
                    <a href='" . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . "' class='button'>Verificar Correo</a>
                    
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all; font-size: 12px; color: #666;'>" . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . "</p>
                    
                    <p style='margin-top: 20px;'>
                        <strong>Importante:</strong> Un superadministrador debe aprobar tu cuenta antes de que puedas acceder al sistema.
                    </p>
                    
                    <div class='footer'>
                        <p>PRERMI - Plataforma de Reciclaje y Recolección de Materiales Inteligente</p>
                        <p>&copy; 2025 Todos los derechos reservados.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->Body = $mailBody;
        $mail->send();
        
    } catch (Exception $e) {
        registrarLog("Error enviando email de verificación admin a $email: " . $e->getMessage(), "warning");
    }
    
    registrarLog("Nuevo administrador registrado: $usuario ($email)", "info");
    jsonOk(['message' => 'Registro exitoso. Revisa tu correo para verificar tu cuenta.', 'admin_id' => $admin_id]);
    
} catch (PDOException $e) {
    error_log('AdminRegister PDO error: ' . $e->getMessage());
    jsonErr('Error en la base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('AdminRegister general error: ' . $e->getMessage());
    jsonErr('Error en el registro: ' . $e->getMessage(), 500);
}

