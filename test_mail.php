<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ernalytvasquez25@gmail.com'; // Tu correo Gmail
    $mail->Password   = 'rgdv gsxl tire szaf'; // Contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Configuración del correo
    $mail->setFrom('ernalytvasquez25@gmail.com', 'Tu Nombre');
    $mail->addAddress('ernalytvasquez25@gmail.com', 'Destinatario');
    $mail->Subject = 'Prueba de correo';
    $mail->Body    = 'Este es un correo de prueba enviado desde PHPMailer.';

    // Enviar correo
    $mail->send();
    echo 'Correo enviado correctamente.';
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}
?>
