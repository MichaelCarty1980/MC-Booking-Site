<?php
/**
 * Michael Carty Bookings & Artist Management — newsletter config TEMPLATE
 *
 * Copy this file to "subscribe-config.php" (same folder) and fill in the
 * real values below. subscribe-config.php is listed in .gitignore on
 * purpose — it holds a live Resend API key and must NEVER be committed to
 * GitHub. Because Hostinger deploys straight from the repo, this file has
 * to be created directly on the server too (Hostinger hPanel > File
 * Manager > upload/create it next to subscribe.php), not just locally.
 */

define('RESEND_API_KEY', 'PASTE_YOUR_RESEND_API_KEY_HERE');
define('RESEND_SEGMENT_ID', 'PASTE_THE_NEWSLETTER_SEGMENT_ID_HERE');
