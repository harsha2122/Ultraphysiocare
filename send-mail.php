<?php
/**
 * Ultraphysiocare — Appointment Form Mailer
 * Sends form submissions to ultraphysiocare@gmail.com via Gmail SMTP.
 *
 * SETUP REQUIRED (one-time):
 *   1. Enable 2-Step Verification on your Google account.
 *   2. Go to https://myaccount.google.com/apppasswords
 *   3. Create an App Password for "Mail" → copy the 16-character password.
 *   4. Paste it in SMTP_PASS below (remove spaces).
 *   5. Upload this file and the PHPMailer folder to your server.
 *
 * PHPMailer: Place the /PHPMailer/ folder (src/ files) next to this file.
 * Download: https://github.com/PHPMailer/PHPMailer/releases
 */

// ── CONFIGURATION ───────────────────────────────────────────────────────────
define('SMTP_HOST',  'smtp.gmail.com');
define('SMTP_PORT',  587);
define('SMTP_USER',  'ultraphysiocare@gmail.com');   // Your Gmail address
define('SMTP_PASS',  'your_app_password_here');       // Gmail App Password (no spaces)
define('MAIL_TO',    'ultraphysiocare@gmail.com');    // Recipient
define('MAIL_FROM',  'ultraphysiocare@gmail.com');    // Sender (same as SMTP_USER for Gmail)
define('SITE_NAME',  'Ultraphysiocare');
// ────────────────────────────────────────────────────────────────────────────

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

header('Content-Type: application/json');

// ── INPUT SANITIZATION ──────────────────────────────────────────────────────
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$phone   = clean($_POST['phone']   ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

// ── VALIDATION ──────────────────────────────────────────────────────────────
$errors = [];
if (empty($name))                         $errors[] = 'Name is required.';
if (empty($phone))                        $errors[] = 'Phone number is required.';
if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';
if (empty($service))                      $errors[] = 'Please select a service.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── EMAIL BODY ───────────────────────────────────────────────────────────────
$subject = "New Appointment Request – $service | " . SITE_NAME;

$bodyHtml = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'/>
  <style>
    body { font-family: 'DM Sans', Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
    .card { background: #fff; max-width: 560px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .header { background: #152d3c; padding: 28px 32px; text-align: center; }
    .header h1 { color: #fff; font-size: 22px; margin: 0; }
    .header p  { color: rgba(255,255,255,0.7); font-size: 13px; margin: 6px 0 0; }
    .body { padding: 32px; }
    .row { margin-bottom: 18px; }
    .label { font-size: 11px; font-weight: 700; color: #0c96c4; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .value { font-size: 16px; color: #152d3c; font-weight: 500; }
    .badge { display: inline-block; background: #0c96c4; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .footer { background: #f8f8f8; padding: 20px 32px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    .cta { display: inline-block; margin-top: 20px; background: #25D366; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; }
  </style>
</head>
<body>
  <div class='card'>
    <div class='header'>
      <h1>&#128203; New Appointment Request</h1>
      <p>" . SITE_NAME . " — Physiotherapy Clinic, Noida</p>
    </div>
    <div class='body'>
      <div class='row'>
        <div class='label'>Patient Name</div>
        <div class='value'>$name</div>
      </div>
      <div class='row'>
        <div class='label'>Mobile Number</div>
        <div class='value'>$phone</div>
      </div>
      <div class='row'>
        <div class='label'>Service / Condition</div>
        <div class='value'><span class='badge'>$service</span></div>
      </div>
      " . ($message ? "
      <div class='row'>
        <div class='label'>Message / Additional Details</div>
        <div class='value' style='background:#f8f8f8; padding:12px; border-radius:8px; font-size:14px;'>$message</div>
      </div>" : "") . "
      <a href='https://api.whatsapp.com/send?phone=919211401779&text=Hi%20$name%2C%20this%20is%20Ultraphysiocare.%20We%20received%20your%20appointment%20request%20for%20$service.%20' class='cta'>&#128241; Reply via WhatsApp</a>
    </div>
    <div class='footer'>
      Received on " . date('d M Y, h:i A') . " IST &nbsp;|&nbsp; " . SITE_NAME . " &nbsp;|&nbsp; +91 92114 01779
    </div>
  </div>
</body>
</html>";

$bodyText = "New Appointment Request\n\n"
    . "Name    : $name\n"
    . "Phone   : $phone\n"
    . "Service : $service\n"
    . ($message ? "Message : $message\n" : "")
    . "\nReceived: " . date('d M Y, h:i A') . " IST";

// ── SEND VIA PHPMAILER ────────────────────────────────────────────────────────
$phpmailerPath = __DIR__ . '/PHPMailer/src/';

if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    // Fallback: native PHP mail() — works only if server has a local MTA configured
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";

    if (mail(MAIL_TO, $subject, $bodyHtml, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Your appointment request has been sent! We will call you back shortly.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please call us directly at +91 92114 01779.']);
    }
    exit;
}

// PHPMailer path found — use SMTP
require $phpmailerPath . 'Exception.php';
require $phpmailerPath . 'PHPMailer.php';
require $phpmailerPath . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom(MAIL_FROM, SITE_NAME);
    $mail->addAddress(MAIL_TO, SITE_NAME);
    $mail->addReplyTo(MAIL_FROM, SITE_NAME);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $bodyText;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Your appointment request has been sent! We will call you back shortly.']);

} catch (Exception $e) {
    error_log('Mailer Error: ' . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Could not send email. Please call us at +91 92114 01779.']);
}
