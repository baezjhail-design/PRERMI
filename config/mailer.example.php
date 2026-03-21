<?php
/**
 * mailer.example.php — Plantilla de configuración del mailer SMTP
 *
 * INSTRUCCIONES PARA COLABORADORES:
 *   1. Copia este archivo: cp config/mailer.example.php config/mailer.php
 *   2. Rellena con tus propias credenciales SMTP.
 *   3. El archivo config/mailer.php está en .gitignore por seguridad.
 *   4. Para Gmail, usa una App Password (no la contraseña normal):
 *      https://myaccount.google.com/apppasswords
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';

$MAILER_CONFIG = [
    'mode' => 'smtp',
    'smtp' => [
        'host'     => 'smtp.gmail.com',
        'username' => 'tu_correo@gmail.com',   // Correo remitente
        'password' => 'xxxx xxxx xxxx xxxx',   // App Password de 16 chars
        'port'     => 465,
        'secure'   => 'ssl',
        'timeout'  => 30,
        'ehlo'     => 'tu-dominio.com',
    ],
];
