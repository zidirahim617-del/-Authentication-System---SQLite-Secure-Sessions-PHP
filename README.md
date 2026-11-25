# 🔐 PHP Authentication System with SQLite & Secure Sessions

A lightweight and secure user authentication system built using **core PHP**, **SQLite**, and secure session management. Perfect for small to medium projects that don't require full frameworks like Laravel.

---

## ✅ Features

- 📧 User Signup and Login
- 🔒 Password hashing using `password_hash()`
- 🔁 Secure session management (`session_regenerate_id(true)`)
- 🕒 48-hour session expiry
- 📬 Forgot Password (Reset Link)
- 🧱 Uses **SQLite** (no external DB server needed)
- 📦 Simple, portable, framework-free

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
