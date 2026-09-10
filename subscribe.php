<?php
/**
 * Michael Carty Bookings & Artist Management — newsletter subscribe handler
 *
 * Adds the visitor's email as a Resend contact (segment: Website Newsletter)
 * so it can be emailed later via Resend broadcasts. Requires
 * subscribe-config.php (NOT committed to git — see subscribe-config.example.php)
 * defining RESEND_API_KEY and RESEND_SEGMENT_ID.
 */

function clean($v) { return trim(strip_tags($v)); }
function is_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); }

function render_page($title, $message) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . htmlspecialchars($title) . '</title><link rel="icon" href="assets/favicon.png" type="image/png">'
        . '<link rel="stylesheet" href="assets/styles.css"></head><body>'
        . '<section style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">'
        . '<div class="container"><div class="card" style="text-align:center;max-width:500px;margin:0 auto;">'
        . '<img src="assets/logo.png" alt="Michael Carty Bookings" style="width:90px;margin-bottom:20px;">'
        . '<h2 style="font-size:1.6rem;margin-bottom:14px;">' . htmlspecialchars($title) . '</h2>'
        . '<p style="color:var(--ivory-dim);">' . htmlspecialchars($message) . '</p>'
        . '<a class="btn btn-solid" href="./" style="margin-top:22px;display:inline-block;">Back to Home</a>'
        . '</div></div></section></body></html>';
    exit;
}

function resend_request($apiKey, $method, $path, $body) {
    $ch = curl_init('https://api.resend.com' . $path);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ),
        CURLOPT_POSTFIELDS => json_encode($body),
    ));
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $status;
}

// ---- Only accept POST ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot — bots fill every field, real visitors never see this one.
if (!empty($_POST['website'])) { exit('OK'); }

$email = clean($_POST['email'] ?? '');
if (!is_email($email)) {
    render_page('Please use a valid email', 'Enter a valid email address to subscribe.');
}

$configFile = __DIR__ . '/subscribe-config.php';
if (!file_exists($configFile)) {
    error_log('subscribe.php: missing subscribe-config.php');
    render_page('Subscription unavailable', 'We could not process your subscription right now. Please try again later or email us at bookings@michael-carty.com.');
}
require $configFile;
if (!defined('RESEND_API_KEY') || !defined('RESEND_SEGMENT_ID')) {
    render_page('Subscription unavailable', 'We could not process your subscription right now. Please try again later or email us at bookings@michael-carty.com.');
}

$payload = array(
    'email'        => $email,
    'unsubscribed' => false,
    'segments'     => array(array('id' => RESEND_SEGMENT_ID)),
);

// Try updating an existing contact first (re-subscribe case); if that
// fails (contact doesn't exist yet), create a new one.
$status = resend_request(RESEND_API_KEY, 'PATCH', '/contacts/' . rawurlencode($email), $payload);
if ($status < 200 || $status >= 300) {
    $status = resend_request(RESEND_API_KEY, 'POST', '/contacts', $payload);
}

if ($status >= 200 && $status < 300) {
    render_page("You're subscribed!", "Thanks for subscribing \xe2\x80\x94 you'll hear from us about bookings, releases, and events.");
} else {
    error_log('subscribe.php: Resend API error, status ' . $status);
    render_page('Subscription unavailable', 'We could not process your subscription right now. Please try again later or email us at bookings@michael-carty.com.');
}
