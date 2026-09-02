# Deploying michael-carty.com — Handoff Guide for the Server Admin

This site was built for Michael Carty Bookings & Artist Management. It is a
static website (HTML/CSS/JS) plus ONE PHP file (`mail.php`) that emails the
booking and contact forms to `bookings@michael-carty.com`.

Everything below is what the person setting up the server needs to know.
No coding experience beyond copy/paste and running commands is required.

====================================================================
0. WHAT YOU ARE DEPLOYING
====================================================================
Folder: the whole `michael-carty-site/` directory.
Contents:
  - 7 HTML pages: index, artists, booking, management, about, news, contact
  - mail.php            (form backend — sends email)
  - assets/             (styles.css, script.js, logo.png, favicon.png,
                         img/ with 26 images, music/ with 7 MP3s)
Upload ALL of it. Do not rename files or folders.

Requirements on the server:
  - A web server (Nginx or Apache)
  - PHP 8.0+ with the `mail()` function OR an SMTP library (we use SMTP below)
  - The domain michael-carty.com pointed at the server's IP
  - A way to receive mail at bookings@michael-carty.com (Hostinger mailbox)

====================================================================
1. SERVER OS + PACKAGES (Ubuntu 22.04 LTS assumed)
====================================================================
SSH into the server, then:

  sudo apt update && sudo apt upgrade -y
  sudo apt install -y nginx php-fpm php-cli unzip certbot python3-certbot-nginx

Find your PHP version (e.g. 8.1) — note it, used below:
  php -v

Start and enable Nginx:
  sudo systemctl enable --now nginx

====================================================================
2. UPLOAD THE SITE
====================================================================
On the server create the web root and upload the folder there:

  sudo mkdir -p /var/www/michael-carty.com/html
  sudo chown -R $USER:$USER /var/www/michael-carty.com/html

From your LOCAL machine (the one that has the site folder), copy it up.
Replace 1.2.3.4 with the server IP and `ubuntu` with the SSH user:

  scp -r /path/to/michael-carty-site/* ubuntu@1.2.3.4:/var/www/michael-carty.com/html/

Then on the server make files readable by the web server:

  sudo chown -R www-data:www-data /var/www/michael-carty.com/html
  sudo find /var/www/michael-carty.com/html -type d -exec chmod 755 {} \;
  sudo find /var/www/michael-carty.com/html -type f -exec chmod 644 {} \;

====================================================================
3. NGINX SITE CONFIG
====================================================================
Create the vhost:

  sudo nano /etc/nginx/sites-available/michael-carty.com

Paste this (replace 1.2.3.4 note: no IP here, Nginx uses server_name):

  server {
      listen 80;
      server_name michael-carty.com www.michael-carty.com;
      root /var/www/michael-carty.com/html;
      index index.html index.php;

      location / {
          try_files $uri $uri/ =404;
      }

      # PHP handler for the booking/contact form
      location ~ \.php$ {
          include snippets/fastcgi-php.conf;
          fastcgi_pass unix:/run/php/php8.1-fpm.sock;   # match your PHP version
      }

      # Don't serve hidden files
      location ~ /\.ht { deny all; }
  }

Enable it:

  sudo ln -s /etc/nginx/sites-available/michael-carty.com /etc/nginx/sites-enabled/
  sudo nginx -t            # should say "test is successful"
  sudo systemctl reload nginx

====================================================================
4. DNS (set at the domain registrar — likely Hostinger)
====================================================================
At michael-carty.com's DNS, point the website at this server:

  Type A    @       ->  SERVER_PUBLIC_IP
  Type A    www     ->  SERVER_PUBLIC_IP

LEAVE the MX records alone — email (bookings@michael-carty.com) stays on
Hostinger and keeps working. Only the website moves to this server.
DNS changes take minutes to a few hours to propagate.

====================================================================
5. SSL (free HTTPS)
====================================================================
  sudo certbot --nginx -d michael-carty.com -d www.michael-carty.com

Follow prompts (enter email, agree to terms). Certbot auto-edits Nginx for
HTTPS and sets up auto-renewal. Visit https://www.michael-carty.com to confirm.

====================================================================
6. CONFIGURE THE BOOKING EMAIL (IMPORTANT)
====================================================================
The form (mail.php) must send mail through a real mail server, otherwise
messages bounce or land in spam. We send via Hostinger SMTP.

Install PHPMailer (on the server):
  cd /var/www/michael-carty.com/html
  sudo -u www-data composer require phpmailer/phpmailer
  (If composer is not installed: sudo apt install -y composer)

If composer is unavailable, use the bundled single-file drop-in instead:
  Download https://github.com/PHPMailer/PHPMailer/raw/master/src/...
  (ask the site builder for the pre-bundled PHPMailer file).

Then EDIT mail.php and replace the mail() call with SMTP. The values to set:

  SMTP host:     smtp.hostinger.com
  SMTP port:     465  (SSL)   — or 587 (STARTTLS)
  SMTP user:     bookings@michael-carty.com
  SMTP password: <Hostinger app password for that mailbox>
  From / To:     bookings@michael-carty.com

TURNKEY OPTION: the folder already includes a ready-to-use SMTP backend named
`mail-smtp.php`. It sends via Hostinger SMTP using only PHP's built-in
functions — NO composer or PHPMailer install needed. To use it:

  1. Open mail-smtp.php and set $SMTP_PASS to the Hostinger app password
     (the other SMTP values are already filled in for Hostinger).
  2. Rename it to mail.php (replacing the cPanel mail() version):
       mv mail.php mail-cpanel.php
       mv mail-smtp.php mail.php
  3. Done — the booking and contact forms now deliver via SMTP.

Without this (or equivalent SMTP config), the form will not deliver reliably
from a generic VPS.

====================================================================
7. TEST
====================================================================
1. Open https://www.michael-carty.com — all 7 pages load.
2. Open /artists.html — the Kompa City player has 7 playable tracks.
3. Open /booking.html — fill the form, submit.
4. Check the bookings@michael-carty.com inbox — the email should arrive.
5. Open /contact.html — submit, confirm arrival too.

If mail does NOT arrive:
  - Confirm the Hostinger mailbox bookings@michael-carty.com exists.
  - Confirm SMTP user/password in mail.php are correct.
  - Check server mail log: sudo tail -f /var/log/nginx/error.log

====================================================================
8. QUICK TROUBLESHOOTING
====================================================================
- 403 on a page: check file permissions (step 2) and that index.html exists.
- PHP file downloads instead of runs: fastcgi_pass socket path wrong / PHP
  version mismatch in the nginx config.
- Site loads but form shows "Mail could not be sent": SMTP creds wrong or
  mailbox missing — see step 6.
- HTTPS not working: re-run certbot; ensure ports 80+443 open in firewall
  (sudo ufw allow 'Nginx Full').

================================================================
SUMMARY OF WHAT THE ADMIN NEEDS FROM MICHAEL
================================================================
1. The full `michael-carty-site/` folder (zipped or via scp).
2. The server's public IP.
3. SSH access to the server.
4. Hostinger SMTP credentials for bookings@michael-carty.com
   (host, app password, port).
5. Permission to edit the michael-carty.com DNS A records.
