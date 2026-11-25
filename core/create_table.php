<?php
require 'config.php';
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            reset_token TEXT,
            reset_expires DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    ensure_login_attempts_table($db);
    echo "Users and login_attempts tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>