<?php
/*
  contact.php — Minimal contact form handler
  - Validates required fields (name, email, message)
  - Uses a simple honeypot ("website") to reduce spam
  - Sanitizes inputs and sends an email using mail()
  - Redirects back to index.html with ?sent=1 or ?error=1

  Notes:
  - mail() must be configured on the server. For reliable delivery use an authenticated SMTP library
    (PHPMailer, Symfony Mailer, etc.) or an external service (SendGrid, Mailgun) configured with SMTP/API.
  - This script avoids echoing raw user content; it redirects back to the site. For an AJAX integration,
    return JSON instead.
*/

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.html');
  exit;
}

// Simple helpers
function safe_trim($v){ return trim(htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); }

$name = safe_trim($_POST['name'] ?? '');
$email = safe_trim($_POST['email'] ?? '');
$subject = safe_trim($_POST['subject'] ?? 'Message depuis le portfolio');
$message = safe_trim($_POST['message'] ?? '');
$honeypot = trim($_POST['website'] ?? ''); // should be empty

// Honeypot check
if ($honeypot !== ''){
  // Likely spam
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

// Prepare email
$to = 'kmlkhatim@gmail.com';
$body = "Nouveau message depuis le site de portfolio:\n\n";
$body .= "Nom: " . $name . "\n";
$body .= "Email: " . $email . "\n";
$body .= "Sujet: " . $subject . "\n\n";
$body .= "Message:\n" . $message . "\n";

$headers = "From: \"" . addslashes($name) . "\" <" . $email . ">\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Try to send
$sent = false;
try{
  $sent = mail($to, $subject, $body, $headers);
} catch (\Exception $e){
  // ignore and treat as failed
  $sent = false;
}

if ($sent){
  header('Location: index.html?sent=1');
  exit;
} else {
  header('Location: index.html?error=1');
  exit;
}
?>