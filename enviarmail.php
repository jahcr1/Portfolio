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

    // Configuración del correo
    $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_FROM_NAME']);
    $mail->addAddress($_ENV['SMTP_TO_EMAIL']);  // A dónde se enviará el mensaje
    $mail->Subject = $asunto;
    $mail->Body = "Nombre: $nombre\nCorreo: $email\nAsunto: $asunto\nMensaje: $mensaje\n";

    $mail->send();
    header("Location: index.php?status=success#Contact");  // Redirigir si se envía correctamente
  } catch (Exception $e) {
    header("Location: index.php?status=error#Contact");  // Redirigir si falla el envío
  }
}
