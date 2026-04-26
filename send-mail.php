<?php
/**
 * Ultraphysiocare Contact Form Mail Handler
 * Receives POST data from the contact form and sends an email
 * to ultraphysiocare@gmail.com
 *
 * Works with Hostinger shared hosting (php mail() is supported out of the box).
 * The From address uses the site domain so Hostinger's MTA accepts it.
 * The Reply-To is set to the visitor's email so you can reply directly.
 */

header('Content-Type: application/json; charset=UTF-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// -----------------------------------------------------------------------
// Helper: sanitise a plain text value
// -----------------------------------------------------------------------
function clean(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

// -----------------------------------------------------------------------
// Collect & sanitise all form fields
// -----------------------------------------------------------------------
$name    = clean($_POST['name']    ?? '');
$email   = filter_var(trim($_POST['email']   ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = clean($_POST['phone']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['msg']     ?? '');

// -----------------------------------------------------------------------
// Validate required fields
// -----------------------------------------------------------------------
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// -----------------------------------------------------------------------
// Build email body
// -----------------------------------------------------------------------
$lines   = [];
$lines[] = "New appointment/enquiry from the Ultraphysiocare website";
$lines[] = str_repeat('-', 55);
$lines[] = "Name:          {$name}";
$lines[] = "Email:         {$email}";
$lines[] = "Phone:         {$phone}";
$lines[] = "Subject:       {$subject}";
$lines[] = str_repeat('-', 55);
$lines[] = "Message:";
$lines[] = $message;
$lines[] = str_repeat('-', 55);
$lines[] = "Sent from:     ultraphysiocare.com/contact.html";

$body = implode("\n", $lines);

// -----------------------------------------------------------------------
// Mail configuration
// -----------------------------------------------------------------------
$to           = 'ultraphysiocare@gmail.com';
$mail_subject = "New Enquiry: {$subject} — from {$name}";

// Use your domain address in From so Hostinger's MTA accepts it.
$from_name    = 'Ultraphysiocare Website';
$from_address = 'noreply@ultraphysiocare.com';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: {$from_name} <{$from_address}>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . PHP_VERSION;

// -----------------------------------------------------------------------
// Send email via PHP mail()
// -----------------------------------------------------------------------
if (mail($to, $mail_subject, $body, $headers)) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent. We will get back to you shortly.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was a problem sending your message. Please try again or contact us directly at +91 92114 01779.'
    ]);
}
