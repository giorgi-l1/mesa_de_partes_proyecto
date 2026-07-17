<?php
// Importamos las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requerimos los archivos que acabas de descargar
require 'lib/phpmailer/Exception.php';
require 'lib/phpmailer/PHPMailer.php';
require 'lib/phpmailer/SMTP.php';

// Creamos la instancia
$mail = new PHPMailer(true);

try {
    // Configuración del Servidor SMTP (Google)
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    
    // AQUÍ VAN TUS CREDENCIALES DE PRUEBA
    $mail->Username   = 'unjfscnotificacion@gmail.com'; 
    $mail->Password   = 'cksoggjjeempbxta'; 
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Encriptación SSL
    $mail->Port       = 465; // Puerto de Google

    // Configuración del Remitente y Destinatario
    $mail->setFrom('unjfscnotificacion@gmail.com', 'Mesa de Partes UNJFSC');
    
    // Pon aquí tu correo personal real para que veas si te llega
    $mail->addAddress('lcgeorge031@gmail.com'); 

    // Contenido del Correo
    $mail->isHTML(true); // Permite usar etiquetas HTML
    $mail->Subject = 'Actualización de tu Trámite UNJFSC';
    
    // Puedes diseñar el correo con HTML básico
    $mail->Body    = '
        <h2>¡Hola!</h2>
        <p>Tu trámite <b>EXP-2026-001</b> ha sido actualizado exitosamente.</p>
        <p>Atentamente,<br>Mesa de Partes.</p>
    ';

    // Enviar correo
    $mail->send();
    echo '<h3 style="color:green;">¡Magia pura! El mensaje se ha enviado correctamente.</h3>';
    
} catch (Exception $e) {
    echo '<h3 style="color:red;">Error al enviar:</h3> ' . $mail->ErrorInfo;
}
?>