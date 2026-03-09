<?php
// scripts/test_mailer_cli.php
require_once __DIR__ . '/../config/mailer.php';

// Cambia este email si quieres verlo en tu buzón real (Mailtrap capturará igualmente)
$to = 'test@example.com';
$name = 'Prueba CLI';
$peso = '1.23';
$credito_kwh = '0.05';

echo "== PRERMI - SMTP Test CLI ==\n";
echo "Enviando email de prueba a: $to\n";

$ok = sendUserDepositEmail($to, $name, $peso, $credito_kwh);

if ($ok) {
    echo "Resultado: OK - Email enviado.\n";
} else {
    echo "Resultado: ERROR - Revisa logs/email_errors.log\n";
    $logFile = __DIR__ . '/../logs/email_errors.log';
    if (file_exists($logFile)) {
        echo "--- Últimas entradas de log ---\n";
        $lines = array_slice(file($logFile), -30);
        echo implode("", $lines);
    }
}

echo "Hecho.\n";
