# 🔐 PHP Authentication System with SQLite & Secure Sessions

A lightweight and secure user authentication system built using **core PHP**, **SQLite**, and secure session management. Perfect for small to medium projects that don't require full frameworks like Laravel.

---

## ✅ Features

- 📧 User Signup and Login
- 🔒 Password hashing using `password_hash()`
- 🔁 Secure session management (`session_regenerate_id(true)`)
- 🕒 48-hour session expiry (inactivity timeout, `session.gc_maxlifetime` set to match)
- 🍪 Session cookie is `HttpOnly`, `SameSite=Lax`, and `Secure` when served over HTTPS; `session.use_strict_mode` on
- 🛡️ CSRF tokens on signup, login, logout, forgot-password and reset-password forms
- 🚦 Login rate limiting: 5 failed attempts per email+IP (20 per IP) in 15 minutes
- 📬 Forgot Password (Reset Link)
- 🧱 Uses **SQLite** (no external DB server needed)
- 📦 Simple, portable, framework-free

---

## ⚙️ Setup

1. **Choose where the database lives.** Keep it outside the web root and set
   the `AUTH_DB_PATH` environment variable to its full path, for example
   `/var/lib/auth-app/users.db` (the folder must be writable by PHP).
   If you don't set it, the app uses `databases/users.db`.
2. **Create the table from the command line** (this script refuses web requests):

   ```bash
   php core/create_table.php
   ```

3. **Block web access** if the database is inside the project:
   - Apache: `databases/.htaccess` and `core/.htaccess` already deny access
     (requires `AllowOverride` to be enabled).
   - nginx: add
     ```nginx
     location ~ ^/(databases|core)/ { deny all; }
     location ~ \.(db|sqlite3?)$   { deny all; }
     ```

> The database file is ignored by git (`.gitignore`). Never commit it: it
> contains user emails and password hashes.

---

## ⚙️ Password reset email settings

The forgot-password page emails the reset link; it never shows it on the page. Set these environment variables on the web server:

| Variable | Required | Meaning |
|---|---|---|
| `APP_BASE_URL` | yes | Public URL of the app with no trailing slash, e.g. `https://example.com/auth`. Without it no reset email is sent and an error is logged. |
| `MAIL_TRANSPORT` | no | `mail` (default) sends with PHP `mail()`. `log` writes the email to the PHP error log, for local development only. |
| `MAIL_FROM` | no | Sender address. Default `no-reply@localhost`. |

To use SMTP or an email API instead, edit `send_app_mail()` in `core/mailer.php`.

Reset tokens are stored as SHA-256 hashes, expire after 1 hour (UTC), and work once.
