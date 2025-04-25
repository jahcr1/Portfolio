<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;  // Importa la clase Dotenv

require __DIR__ . '/vendor/autoload.php';  // Importa PHPMailer

// Cargar las variables de entorno desde .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Validar Honeypot
  if (!empty($_POST['telefono'])) {
    header("Location: index.php?status=error#Contact"); // Bot detectado
    exit;
  }

  // Validar reCAPTCHA
  $recaptchaSecret = $_ENV['RECAPTCHA_SECRET_KEY'];
  $recaptchaResponse = $_POST['g-recaptcha-response'];

  if (empty($recaptchaResponse)) {
    header("Location: index.php?status=error#Contact");
    exit;
  }

  $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
  $captchaSuccess = json_decode($verify);

  if (!$captchaSuccess->success) {
    header("Location: index.php?status=error#Contact"); // Falla recaptcha
    exit;
  }

  // Validación y saneamiento
  $nombre = htmlspecialchars($_POST['nombre']);
  $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
  $asunto = htmlspecialchars($_POST['asunto']);
  $mensaje = htmlspecialchars($_POST['mensaje']);


  if (!$email) {
    header("Location: index.php?status=error#Contact");  // Redirigir si el email no es válido
    exit;
  }

  $mail = new PHPMailer(true);

  try {
    // Configuración del servidor SMTP usando variables de entorno
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];  // Servidor SMTP
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];  // Tu correo
    $mail->Password = $_ENV['SMTP_PASSWORD'];  // Contraseña de la aplicacion
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['SMTP_PORT'];
    $mail->CharSet = 'UTF-8';

    // Configuración del correo de contacto que me llega de CONTACTO
    $mail->setFrom($_ENV['SMTP_USER'], 'Formulario Web');
    $mail->addAddress($_ENV['SMTP_TO_EMAIL']);  // A mi mail
    $mail->addReplyTo($email, $nombre); // Al visitante como respuesta
    $mail->Subject = "Nuevo mensaje de contacto: $nombre";
    // Correo en HTML
    $mail->isHTML(true);
    $mail->Body = "
        <h2>Nuevo mensaje de seccion Contacto</h2>
        <p><strong>Nombre:</strong> {$nombre}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Asunto:</strong> {$asunto}</p>
        <hr>
        <p><strong>Mensaje:</strong><br>" . nl2br(htmlspecialchars($mensaje)) . "</p>";
    // Versión en texto plano
    $mail->AltBody = "Nombre: $nombre\nEmail: $email\nAsunto: $asunto\n\nMensaje:\n$mensaje";
    $mail->send();

    /*  Mail de cortesía al VISITANTE  */
    $mail->clearAllRecipients();
    $mail->setFrom($_ENV['SMTP_USER'], 'Martín Contreras'); // Desde tu correo
    $mail->addAddress($email); // Al mail del VISITANTE
    $mail->addReplyTo($_ENV['SMTP_USER'], 'Martin Contreras'); // A mi como respuesta

    $mail->Subject = "Gracias por contactarnos, $nombre 🙌🏼";
    $mail->isHTML(true);
    $mail->Body = "
        <p>Hola <strong>$nombre</strong> 👋🏼,</p>
        <p>Recibí tu mensaje y en breve te estaré respondiendo. Gracias por tomarte el tiempo de escribirme.</p>
        <hr>
        <p><strong>Este fue tu mensaje:</strong><br>" . nl2br(htmlspecialchars($mensaje)) . "</p>
        <br>
        <p>Saludos,<br><strong>Martín Contreras</strong></p>
    ";
    $mail->AltBody = "Hola $nombre,\n\nRecibí tu mensaje:\n\n$mensaje\n\nGracias por escribir.\n\nMartín Contreras";
    $mail->send();

    header("Location: index.php?status=success#Contact");  // Redirigir si se envía correctamente
  } catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}", 3, "logs/mailer_errors.log");
    header("Location: index.php?status=error#Contact");  // Redirigir si falla el envío
  }
}
