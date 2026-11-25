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
