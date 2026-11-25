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
