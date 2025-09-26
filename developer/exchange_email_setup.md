# Exchange email setup (macOS + XAMPP)

This project sends emails using PHP's `mail()` (queue drained by `api/scheduler_email.php` and PTW `Class_email::send_email()`). To deliver via Microsoft Exchange (Exchange Online or On‑Prem), wire PHP's sendmail interface to `msmtp` and point it at your Exchange server.

## Overview
- You do NOT need to modify the app code; we keep using `mail()`.
- We configure `msmtp` as the sendmail transport for PHP.
- Choose one of these modes:
  - Exchange Online (Microsoft 365) – SMTP submission with auth/TLS on `smtp.office365.com:587`.
  - On‑Prem Exchange – SMTP relay on port 25 (IP allow) or submission on 587 if enabled.

## 1) Install msmtp

```bash
brew install msmtp
which msmtp
msmtp --version
```

Note the path (Apple Silicon usually `/opt/homebrew/bin/msmtp`; Intel `/usr/local/bin/msmtp`).

## 2) Create `~/.msmtprc`

Create a config file for the OS user running Apache (on macOS XAMPP, this is typically your user when started from the control app). Set file perms to 600.

```bash
cat > ~/.msmtprc <<'EOF'
# Choose ONE account (uncomment the relevant block) and set default-account accordingly.

# --- Exchange Online (M365) – SMTP submission (STARTTLS on 587) ---
# account exchange-office365
# host smtp.office365.com
# port 587
# protocol smtp
# auth on
# user service_mailbox@yourdomain.com
# password "YOUR_APP_PASSWORD_OR_MAILBOX_PASSWORD"
# from service_mailbox@yourdomain.com
# tls on
# tls_starttls on
# # Optionally, set trust store explicitly (often not needed on macOS):
# # tls_trust_file /etc/ssl/cert.pem
# logfile ~/.msmtp.log

# --- On-Prem Exchange – IP-based relay (port 25, no auth) ---
# account exchange-relay
# host exchange.yourcompany.local
# port 25
# protocol smtp
# auth off
# # If your relay enforces TLS, switch these:
# tls off
# # tls on
# # tls_starttls on
# from noreply@yourdomain.com
# logfile ~/.msmtp.log

# Set the default account you enabled above
# default-account exchange-office365
# default-account exchange-relay
EOF

chmod 600 ~/.msmtprc
```

Tips:
- Exchange Online requires SMTP AUTH enabled for the mailbox and TLS 1.2+. The `from` must be that mailbox or you must grant Send As permissions.
- On‑Prem relay requires your Mac's IP be allowed on the receive connector. If your relay requires TLS or authentication, adapt the Exchange Online block instead.

## 3) Test msmtp directly

```bash
printf "To: your.email@yourdomain.com\nFrom: service_mailbox@yourdomain.com\nSubject: msmtp test via Exchange\n\nHello from msmtp." | msmtp -t
```

- Check `~/.msmtp.log` for errors.
- If this fails, fix msmtp before wiring PHP.

## 4) Point PHP to msmtp (php.ini)

Edit XAMPP's PHP config and set the sendmail path. Use the path from `which msmtp`.

```ini
; /Applications/XAMPP/xamppfiles/etc/php.ini
sendmail_path = "/opt/homebrew/bin/msmtp -t -i"
```

Notes:
- `-t` reads recipients from headers; `-i` ignores lines starting with a single dot.
- If you defined multiple accounts, you can force one with `-a account_name`.
- Some code paths pass `-f` for the envelope sender. `msmtp` in sendmail mode respects `-f`; ensure the address has Send As permission or matches the mailbox.

Restart Apache after editing:

```bash
sudo /Applications/XAMPP/xamppfiles/xampp restart
```

## 5) Verify with a simple PHP script

Use the helper below (already added under `developer/test_exchange_mail.php`).

```bash
php /Applications/XAMPP/xamppfiles/htdocs/gems2/developer/test_exchange_mail.php
```

Then check your inbox and `~/.msmtp.log`.

## 6) Verify through the app queue

- The app enqueues rows into `email_send` and drains them via `api/scheduler_email.php`.
- For a quick test, queue an email using existing flows (e.g., perform an action that sends a notification), then run:

```bash
php /Applications/XAMPP/xamppfiles/htdocs/gems2/api/scheduler_email.php
```

or hit in a browser while logged in locally:

```
http://localhost/gems2/api/scheduler_email.php
```

Confirm the message arrives via Exchange.

## 7) Common pitfalls

- Exchange Online: SMTP AUTH disabled tenant-wide or mailbox-level → enable SMTP AUTH for the sending mailbox.
- From/Return-Path mismatch → set `from` in `~/.msmtprc` to the mailbox; ensure Send As permissions if sending on behalf of another address.
- TLS/certs: If msmtp errors on trust store, set `tls_trust_file` to a valid CA bundle (e.g., `/etc/ssl/cert.pem` or Homebrew's OpenSSL CA path) or omit it to use system defaults.
- Rate limits/throttling: Microsoft 365 throttles unauthenticated and shared mailboxes; space out batch sends.

## 8) Production notes

- Use a dedicated mailbox (e.g., `noreply@yourdomain.com`) or a relay connector. Configure SPF/DKIM/DMARC for the domain.
- Ensure the web server identity/IP is permitted on the Exchange receive connector if using relay.
- Log and monitor: keep `logfile` enabled in `~/.msmtprc`; rotate as needed.
- If you prefer code-level SMTP instead of `mail()`, consider PHPMailer with SMTP to Exchange (see below).

## 9) Optional: PHPMailer (code-level SMTP)

If you later choose to bypass `mail()` and send directly via SMTP in PHP, add PHPMailer via Composer and configure it to use Exchange:

```bash
composer require phpmailer/phpmailer
```

Example snippet:

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.office365.com';
$mail->Port = 587;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->SMTPAuth = true;
$mail->Username = 'service_mailbox@yourdomain.com';
$mail->Password = 'YOUR_PASSWORD_OR_APP_PASSWORD';
$mail->setFrom('service_mailbox@yourdomain.com', 'GEMS2');
$mail->addAddress('your.email@yourdomain.com');
$mail->Subject = 'PHPMailer test via Exchange';
$mail->Body = 'Hello from PHPMailer via Exchange.';
$mail->send();
```

Only adopt this path if/when you plan to refactor the app’s mail-sending code away from `mail()`.

---

If you need help validating tenant settings (SMTP AUTH, Send As) or choosing between submission vs relay, reach out to your Exchange admin and share this document.
