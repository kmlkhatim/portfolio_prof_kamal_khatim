<?php
/*
  contact_smtp.php — PHPMailer + SMTP handler
  - Requires: composer require phpmailer/phpmailer
  - Reads SMTP credentials from environment variables (do NOT hardcode passwords):
      SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE (tls or ssl)
      FROM_EMAIL (optional), TO_EMAIL (optional)
  - Validates inputs, uses honeypot anti-spam, sends via authenticated SMTP, redirects back to index.html

  Security notes:
  - Never commit credentials into the repository. Use environment variables or a server configuration.
  - For Gmail, enable 2FA and create an App Password to use as SMTP password.
*/

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.html');
  exit;
}

// Basic sanitization
function safe_trim($v){ return trim(htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); }

$name = safe_trim($_POST['name'] ?? '');
$email = safe_trim($_POST['email'] ?? '');
$subject = safe_trim($_POST['subject'] ?? 'Message depuis le portfolio');
$message = safe_trim($_POST['message'] ?? '');
$honeypot = trim($_POST['website'] ?? ''); // should be empty

// Honeypot check
if ($honeypot !== ''){
  header('Location: index.html?spam=1');
  exit;
}

$errors = [];
if ($name === '') $errors[] = 'name';
if ($message === '') $errors[] = 'message';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';

if (!empty($errors)){
  header('Location: index.html?error=1');
  exit;
}

// PHPMailer
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration SMTP direct pour Gmail
$smtpHost   = getenv('SMTP_HOST')   ?: 'smtp.gmail.com';
$smtpPort   = getenv('SMTP_PORT')   ?: 587;
$smtpUser   = getenv('SMTP_USER')   ?: 'kmlkhatim@gmail.com';
$smtpPass   = getenv('SMTP_PASS')   ?: 'ngvi mzxv ppcp vipe'; // Mot de passe d'application inséré ici
$smtpSecure = getenv('SMTP_SECURE') ?: 'tls'; // 'tls' (port 587) ou 'ssl' (port 465)

// Adresses d'expédition et de réception
$fromEmail  = getenv('FROM_EMAIL')  ?: $smtpUser;
$toEmail    = getenv('TO_EMAIL')    ?: $smtpUser;

// Fail early if SMTP credentials missing
if (empty($smtpUser) || empty($smtpPass)){
  // SMTP not configured; redirect with error
  header('Location: index.html?error=1');
  exit;
}

$mail = new PHPMailer(true);
try{
  // Server settings
  $mail->isSMTP();
  $mail->Host = $smtpHost;
  $mail->SMTPAuth = true;
  $mail->Username = $smtpUser;
  $mail->Password = $smtpPass;
  $mail->SMTPSecure = $smtpSecure; // 'tls' or 'ssl'
  $mail->Port = (int)$smtpPort;
  $mail->CharSet = 'UTF-8';

  // Recipients
  $mail->setFrom($fromEmail, $name ?: 'Contact Form');
  $mail->addAddress($toEmail);
  $mail->addReplyTo($email, $name);

  // Content
  $mail->isHTML(false);
  $mail->Subject = $subject;
  $body = "Nouveau message depuis le site de portfolio:\n\n";
  $body .= "Nom: " . $name . "\n";
  $body .= "Email: " . $email . "\n";
  $body .= "Sujet: " . $subject . "\n\n";
  $body .= "Message:\n" . $message . "\n";
  $mail->Body = $body;

  $mail->send();
  header('Location: index.html?sent=1');
  exit;
} catch (Exception $e){
  // Log error server-side if desired (do not expose to user)
  // error_log('Mail error: ' . $e->getMessage());
  header('Location: index.html?error=1');
  exit;
}
?>