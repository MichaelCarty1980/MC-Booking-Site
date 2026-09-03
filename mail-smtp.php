<?php
/**
 * Michael Carty Bookings & Artist Management — form backend (SMTP version)
 *
 * Use this file on a generic VPS where PHP's built-in mail() will not deliver
 * (or will land in spam). It sends through Hostinger's SMTP server using only
 * PHP's standard openssl stream functions — NO composer / PHPMailer required.
 *
 * To deploy on the VPS: rename this file to mail.php (it replaces the
 * cPanel mail() version). Then fill in the SMTP credentials in the
 * CONFIGURATION block below.
 */

// ---------------------------- CONFIGURATION -------------------------------
$TO_EMAIL   = 'bookings@michael-carty.com';
$FROM_EMAIL = 'bookings@michael-carty.com';   // must match an existing Hostinger mailbox
$SITE_NAME  = 'Michael Carty Bookings & Artist Management';

// Hostinger SMTP — fill these in (get the app password from Hostinger panel)
$SMTP_HOST   = 'smtp.hostinger.com';
$SMTP_PORT   = 465;          // 465 = SSL, 587 = STARTTLS (set $SMTP_SECURE below)
$SMTP_SECURE = 'ssl';        // 'ssl' for port 465, 'tls' for port 587
$SMTP_USER   = 'bookings@michael-carty.com';
$SMTP_PASS   = 'APP_PASSWORD_HERE';   // Hostinger app password, NOT the main mailbox password
// -------------------------------------------------------------------------

// Map of form ids -> human-readable subject prefix
$FORM_LABELS = array(
    'booking-form'    => 'Booking Request',
    'contact-form'    => 'Contact Inquiry',
    'submission-form' => 'Artist Submission',
);

// ------------------------------ Helpers -----------------------------------
function clean($v) { return trim(strip_tags($v)); }
function is_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); }
function safe_line($v) { return preg_replace('/(\r\n|\r|\n)/', ' ', $v); }

// Minimal SMTP client using PHP streams (no external dependencies)
function smtp_send($host, $port, $secure, $user, $pass, $from, $to, $subject, $body, $replyTo, $siteName) {
    $timeout = 30;
    $ctx = stream_context_create(array('ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
    )));
    $scheme = ($secure === 'ssl') ? 'ssl://' : '';
    $fp = @stream_socket_client($scheme . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return "Connection failed: $errstr ($errno)";

    function smtp_cmd($fp, $cmd, $expect = null) {
        if ($cmd !== null) {
            fwrite($fp, $cmd . "\r\n");
        }
        $resp = '';
        while (($line = fgets($fp, 515)) !== false) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if ($expect !== null && (int)substr(trim($resp), 0, 3) !== $expect) {
            return "SMTP error: " . trim($resp);
        }
        return true;
    }

    $r = smtp_cmd($fp, null);                         // banner
    if ($r !== true) return $r;
    smtp_cmd($fp, "EHLO " . gethostname());
    if ($secure === 'tls') {
        smtp_cmd($fp, "STARTTLS", 220);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return "STARTTLS failed";
        }
        smtp_cmd($fp, "EHLO " . gethostname());
    }
    smtp_cmd($fp, "AUTH LOGIN", 334);
    smtp_cmd($fp, base64_encode($user), 334);
    $a = smtp_cmd($fp, base64_encode($pass), 235);
    if ($a !== true) return "Auth failed: $a";
    smtp_cmd($fp, "MAIL FROM:<$from>", 250);
    smtp_cmd($fp, "RCPT TO:<$to>", 250);
    smtp_cmd($fp, "DATA", 354);
    $headers = "From: $siteName <$from>\r\n"
             . "Reply-To: $replyTo\r\n"
             . "Subject: $subject\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
    smtp_cmd($fp, "QUIT", 221);
    fclose($fp);
    return true;
}

// ---------------------- Only accept POST ----------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot: real users leave this empty; bots fill it.
if (!empty($_POST['website'])) {
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
$replyTo = ($senderName ? "$senderName <$senderEmail>" : $senderEmail);

$result = smtp_send(
    $SMTP_HOST, $SMTP_PORT, $SMTP_SECURE, $SMTP_USER, $SMTP_PASS,
    $FROM_EMAIL, $TO_EMAIL, $subject, $body, $replyTo, $SITE_NAME
);

if ($result === true) {
    // Auto-reply confirmation to the sender (best-effort via local mail())
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
