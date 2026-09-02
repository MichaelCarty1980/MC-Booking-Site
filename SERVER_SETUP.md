# Server Setup & Go-Live — michael-carty.com

Michael buys a VPS/server. Hermes configures it, uploads the site, wires the
booking email, and points the domain. This file lists exactly what to send.

## IMPORTANT: Do NOT use Wix Studio (or Squarespace/Webflow)
- This site is a hand-built static site (HTML/CSS/JS) + a PHP mail handler
  (mail.php). Wix Studio is a closed builder — it cannot run our folder or our
  PHP booking backend, and would force a full rebuild. Skip it.
- Two valid hosts only: (1) the VPS detailed below, or (2) Hostinger hosting
  (already has the bookings@ mailbox). Either runs our site as-built.

## What I need from you (send when the server is ready)

1. **Server provider + IP**
   - e.g. Hetzner / DigitalOcean / Vultr
   - The server's public IPv4 address

2. **OS**
   - Recommended: Ubuntu 22.04 LTS (I'll assume this unless you say otherwise)

3. **SSH access** (pick one)
   - Option A (easiest): root login + temporary password  — I'll harden it immediately
     (create a sudo user, disable root password login, install SSH keys, enable firewall)
   - Option B (safer): you create a sudo user + give me its password or an SSH public key
   - DO NOT paste your main Hostinger/master password here.

4. **Domain control**
   - Is michael-carty.com already registered? Where (Hostinger / another registrar)?
   - Can you change its DNS A record (or nameservers) to point at the new server IP?
     (I'll give you the exact IP + record to set, or do it if you grant registrar access.)

5. **Hostinger email SMTP credentials** (for bookings@michael-carty.com)
   - SMTP host (usually something like smtp.hostinger.com)
   - Full email: bookings@michael-carty.com
   - An **app password** (not your main mailbox password) — create one in Hostinger
   - Port (usually 465 SSL or 587 STARTTLS)
   This lets mail.php send reliably from the VPS instead of PHP's default mail().

## What I will do (once I have the above)

1. SSH in, update packages, install **Nginx + PHP-FPM**.
2. Harden: UFW firewall (allow 22/80/443 only), SSH key login, disable root pw login.
3. Upload the whole site folder to /var/www/michael-carty.com/html.
4. Configure Nginx vhost for michael-carty.com (HTTP + free SSL via Certbot).
5. Convert **mail.php** to SMTP (PHPMailer) using your Hostinger SMTP creds so
   booking/contact submissions deliver to bookings@michael-carty.com (no spam folder).
6. You point the domain A record at the server IP (or I do it with registrar access).
7. Test: open the live URL, submit a booking + contact form, confirm mail arrives.

## Current site inventory (already built, ready to upload)
- 7 pages: index, artists, booking, management, about, news, contact (.html)
- mail.php  (booking + contact handler)
- assets/: styles.css, script.js, logo.png, favicon.png, img/ (25+ images)
- assets/music/clarence/: 7 MP3s (Kompa City — Clarence x Jeon)
- Roster COMPLETE — all 8 artists have genre + teaser/Read-more bio:
  - Clarence — Kompa
  - Jeon — Kompa
  - Shine Music — Variation of Local & Latin Music
  - Essovilla — R&B
  - Issabeach — Kompa
  - BB Bad — Multi-talented open-format DJ
  - DJ Whiteboy — Prominent Aruban DJ and music personality
  - JRalph — Caribbean sounds, island vibes, and global music styles
- Featured blocks on Artists page: A1 Clarence, A2 Shine Music, A3 Jeon
- Kompa City music player (7 tracks) under Clarence's A1 block

## DNS Records (point michael-carty.com at the new server)
Set these at your DOMAIN REGISTRAR / DNS host (wherever michael-carty.com is
registered — likely Hostinger). Replace YOUR_SERVER_IP with the VPS public IP.

| Type | Name/Host | Value / Points to      | TTL  |
|------|-----------|------------------------|------|
| A    | @         | YOUR_SERVER_IP         | 3600 |
| A    | www       | YOUR_SERVER_IP         | 3600 |
| CNAME| www       | michael-carty.com      | 3600 |

Notes:
- The A records only change where the WEBSITE loads. They do NOT affect email.
- LEAVE the MX records alone — bookings@michael-carty.com stays on Hostinger,
  so its mail keeps working after the site moves to the VPS.
- After DNS propagates (minutes to a few hours), the site loads from the VPS.
- I'll install a free Let's Encrypt SSL cert on the server so https:// works.

## What I need from you to finish (recap)
1. Server provider + public IP
2. OS (default Ubuntu 22.04 LTS)
3. SSH access (root temp pw, or a sudo user)
4. Domain DNS access (or just confirm you can edit the A records above)
5. Hostinger email SMTP creds (host, bookings@michael-carty.com, app password, port)
