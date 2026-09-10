<?php
/**
 * Michael Carty Bookings & Artist Management — newsletter unsubscribe handler
 * Marks the Resend contact as unsubscribed. Always shows the same
 * confirmation regardless of whether the email was on the list, so this
 * can't be used to check who is/isn't subscribed.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!empty($_POST['website'])) { exit('OK'); }

$email = clean($_POST['email'] ?? '');
if (!is_email($email)) {
    render_page('Please use a valid email', 'Enter a valid email address to unsubscribe.');
}

$configFile = __DIR__ . '/subscribe-config.php';
if (file_exists($configFile)) {
    require $configFile;
    if (defined('RESEND_API_KEY')) {
        $ch = curl_init('https://api.resend.com/contacts/' . rawurlencode($email));
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Bearer ' . RESEND_API_KEY,
                'Content-Type: application/json',
            ),
            CURLOPT_POSTFIELDS => json_encode(array('unsubscribed' => true)),
        ));
        curl_exec($ch);
        curl_close($ch);
    }
}

render_page("You're unsubscribed", "You will no longer receive emails from Michael Carty Bookings & Artist Management. If this was a mistake, you can subscribe again anytime.");
