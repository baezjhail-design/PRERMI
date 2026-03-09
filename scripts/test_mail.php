<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'baezjhail@gmail.com';
  $mail->Password = 'gzghfibxuryaebuj';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port = 465;
  $mail->setFrom('baezjhail@gmail.com','PRERMI Test');
  $mail->addAddress('baezjhail@gmail.com');
  $mail->isHTML(true);
  $mail->Subject = "Test PRERMI";
  $mail->Body = "Si recibes esto el SMTP funciona";
  $mail->send();
  echo "Enviado";
} catch (Exception $e) {
  echo "Error: " . $mail->ErrorInfo;
}
