<?php
/**
 * Michael Carty Bookings & Artist Management — form backend
 * Hosted on Titan (cPanel/PHP). Receives POST from the booking and contact
 * forms and emails the contents to bookings@michael-carty.com.
 */

// ---- Configuration -------------------------------------------------------
$TO_EMAIL   = 'bookings@michael-carty.com';
$FROM_EMAIL = 'bookings@michael-carty.com';   // must be a real domain address on Titan
$SITE_NAME  = 'Michael Carty Bookings & Artist Management';

// Map of form ids -> human-readable subject prefix
$FORM_LABELS = array(
    'booking-form'    => 'Booking Request',
    'contact-form'    => 'Contact Inquiry',
    'submission-form' => 'Artist Submission',
);

// ---- Helpers -------------------------------------------------------------
function clean($v) {
    return trim(strip_tags($v));
}
function is_email($e) {
    return filter_var($e, FILTER_VALIDATE_EMAIL);
}
// Block header-injection attempts
function safe_line($v) {
    return preg_replace('/(\r\n|\r|\n)/', ' ', $v);
}

// ---- Only accept POST ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot: real users leave this empty; bots fill it.
if (!empty($_POST['website'])) {
    // Pretend success to the bot, but send nothing.
    exit('OK');
}

$formId = isset($_POST['form_id']) ? clean($_POST['form_id']) : '';
$subjectPrefix = isset($FORM_LABELS[$formId]) ? $FORM_LABELS[$formId] : 'Website Inquiry';

$senderName  = clean($_POST['name'] ?? $_POST['organizer'] ?? '');
$senderEmail = clean($_POST['email'] ?? '');

if (!is_email($senderEmail)) {
    http_response_code(400);
    exit('A valid email address is required.');
}

// Build the message body from all submitted fields
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
if ($body === '') {
    http_response_code(400);
    exit('No form data received.');
}

$subject = safe_line($subjectPrefix . ' — ' . ($senderName ?: $senderEmail));
$headers = array(
    'From: ' . $SITE_NAME . ' <' . $FROM_EMAIL . '>',
    'Reply-To: ' . $senderName . ' <' . $senderEmail . '>',
    'Content-Type: text/plain; charset=UTF-8',
);

$mailOk = mail($TO_EMAIL, $subject, $body, implode("\r\n", $headers));

if ($mailOk) {
    exit('OK');
} else {
    http_response_code(500);
    exit('Mail could not be sent. Please email us directly at ' . $TO_EMAIL);
}
