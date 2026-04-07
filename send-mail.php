<?php
/**
 * Ultraphysiocare Contact Form Mail Handler
 * Receives POST data from the appointment form and sends an email to
 * ultraphysiocare@gmail.com
 */

header('Content-Type: text/plain; charset=UTF-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

// -----------------------------------------------------------------------
// Helper: sanitize a plain text value
// -----------------------------------------------------------------------
function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

// -----------------------------------------------------------------------
// Collect & sanitise fields
// -----------------------------------------------------------------------
$name    = clean($_POST['name']    ?? '');
$phone   = clean($_POST['phone']   ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

// -----------------------------------------------------------------------
// Validate required fields
// -----------------------------------------------------------------------
if (empty($name) || empty($phone) || empty($service)) {
    http_response_code(400);
    echo 'Please fill in all required fields.';
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    http_response_code(400);
    echo 'Please enter a valid 10-digit mobile number.';
    exit;
}

// -----------------------------------------------------------------------
// Build email body
// -----------------------------------------------------------------------
$lines   = [];
$lines[] = "New appointment request from Ultraphysiocare website";
$lines[] = str_repeat('-', 50);
$lines[] = "Name:    {$name}";
$lines[] = "Phone:   {$phone}";
$lines[] = "Service: {$service}";
if (!empty($message)) {
    $lines[] = str_repeat('-', 50);
    $lines[] = "Message:\n{$message}";
}

$body = implode("\n", $lines);

// -----------------------------------------------------------------------
// Send email via PHP mail()
// -----------------------------------------------------------------------
$to      = 'ultraphysiocare@gmail.com';
$subject = "Appointment Request: {$name} – {$service}";

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$host = preg_replace('/[^a-z0-9.-]+/i', '', $host);
$from_address = 'noreply@' . ($host ?: 'localhost');

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: Ultraphysiocare <{$from_address}>\r\n";
$headers .= "Reply-To: Ultraphysiocare <{$from_address}>\r\n";
$headers .= "X-Mailer: PHP/" . PHP_VERSION;

if (mail($to, $subject, $body, $headers)) {
    echo 'success';
} else {
    http_response_code(500);
    echo 'Sorry, there was a problem sending your message. Please try again.';
}
