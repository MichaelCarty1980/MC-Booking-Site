<?php
/**
 * Michael Carty Bookings & Artist Management — form backend
 * Hosted on any PHP host. Receives POST from the booking and contact
 * forms and emails the contents to the appropriate inbox.
 */

// ---- Configuration -------------------------------------------------------
$FROM_EMAIL = 'bookings@michael-carty.com';
$SITE_NAME  = 'Michael Carty Bookings & Artist Management';

// Per-form: recipient email + subject prefix
$FORM_CONFIG = array(
    'booking-form'    => array('to' => 'bookings@michael-carty.com', 'prefix' => 'Booking Request'),
    'contact-form'    => array('to' => 'info@michael-carty.com',     'prefix' => 'Contact Inquiry'),
    'submission-form' => array('to' => 'bookings@michael-carty.com', 'prefix' => 'Artist Submission'),
);

// ---- Helpers -------------------------------------------------------------
function clean($v) { return trim(strip_tags($v)); }
function is_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); }
function safe_line($v) { return preg_replace('/(\r\n|\r|\n)/', ' ', $v); }

// ---- Only accept POST ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot
if (!empty($_POST['website'])) { exit('OK'); }

$formId = isset($_POST['form_id']) ? clean($_POST['form_id']) : '';
$formConfig = isset($FORM_CONFIG[$formId]) ? $FORM_CONFIG[$formId] : array('to' => 'bookings@michael-carty.com', 'prefix' => 'Website Inquiry');
$TO_EMAIL = $formConfig['to'];
$subjectPrefix = $formConfig['prefix'];

$senderName  = clean($_POST['name'] ?? $_POST['organizer'] ?? '');
$senderEmail = clean($_POST['email'] ?? '');

if (!is_email($senderEmail)) {
    http_response_code(400);
    exit('A valid email address is required.');
}

$lines = array();
foreach ($_POST as $key => $value) {
    if (in_array($key, array('form_id', 'website', 'submit'), true)) continue;
    if (is_array($value)) $value = implode(', ', $value);
    $value = clean($value);
    if ($value === '') continue;
    $label = ucwords(str_replace(array('_', '-'), ' ', $key));
    $lines[] = $label . ": " . $value;
}
$body = implode("\n", $lines);
if ($body === '') { http_response_code(400); exit('No form data received.'); }

$subject = safe_line($subjectPrefix . ' — ' . ($senderName ?: $senderEmail));
$headers = array(
    'From: ' . $SITE_NAME . ' <' . $FROM_EMAIL . '>',
    'Reply-To: ' . $senderName . ' <' . $senderEmail . '>',
    'Content-Type: text/plain; charset=UTF-8',
);

$mailOk = mail($TO_EMAIL, $subject, $body, implode("\r\n", $headers));

if ($mailOk) {
    // Auto-reply confirmation to the sender
    $confirmSubject = 'We received your request — Michael Carty Bookings';
    $confirmBody = "Hi" . ($senderName ? " $senderName" : "") . ",\n\n"
        . "Thank you for submitting your request to Michael Carty Bookings & Artist Management.\n"
        . "We have received it and will review it shortly.\n\n"
        . "Best regards,\nMichael Carty Bookings & Artist Management\n"
        . "bookings@michael-carty.com\n+297 731 7771";
    $confirmHeaders = "From: $SITE_NAME <$FROM_EMAIL>\r\n"
        . "Content-Type: text/plain; charset=UTF-8";
    @mail($senderEmail, $confirmSubject, $confirmBody, $confirmHeaders);

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Request Sent</title><link rel="stylesheet" href="assets/styles.css"></head><body><section style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;"><div class="container"><div class="card" style="text-align:center;max-width:500px;margin:0 auto;"><img src="assets/logo.png" alt="Michael Carty Bookings" style="width:90px;margin-bottom:20px;"><h2 style="font-size:1.6rem;margin-bottom:14px;">Thank you for submitting your request to Michael Carty Bookings &amp; Artist Management.</h2><p style="color:var(--ivory-dim);">We will review it and contact you shortly.</p><a class="btn btn-solid" href="./" style="margin-top:22px;display:inline-block;">Back to Home</a></div></div></section></body></html>';
    exit;
} else {
    http_response_code(500);
    exit('Mail could not be sent. Please email us directly at ' . $TO_EMAIL);
}
