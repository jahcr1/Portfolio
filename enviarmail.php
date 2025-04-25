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

    // Configuración del correo a la tienda
    $mail->setFrom($_ENV['SMTP_USER'], 'Formulario de Contacto');
    $mail->addAddress($_ENV['SMTP_TO_EMAIL']);  // A dónde se enviará el mensaje
    $mail->addReplyTo($email, "$nombre");
    $mail->Subject = "Asunto: $asunto";
    $body  = "<b>Nombre:</b> $nombre <br>";
    $body .= "<b>Asunto:</b> $asunto <br><hr>";
    $body .= "<b>Email:</b> $email<br><hr>";
    $body .= nl2br(htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8'));
    $mail->isHTML(true);
    $mail->Body = $body;
    $mail->send();

    /*  Mail de cortesía al usuario  */
    $mail->clearAllRecipients();
    $mail->addAddress($email);
    $mail->Subject = '¡Gracias por contactarnos!';
    $mail->Body    = "Hola $nombre 👋🏼,\n\nRecibimos tu mensaje y te responderemos a la brevedad.\n\nSaludos,\nMartín Contreras.";
    $mail->isHTML(false);
    $mail->send();

    header("Location: index.php?status=success#Contact");  // Redirigir si se envía correctamente
  } catch (Exception $e) {
    header("Location: index.php?status=error#Contact");  // Redirigir si falla el envío
  }
}
