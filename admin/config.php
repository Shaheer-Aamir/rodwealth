<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u226757524_rodwealthadmin');         // XAMPP default
define('DB_PASS', 'RodWealth@2026');             // XAMPP default (empty)
define('DB_NAME', 'u226757524_constructiondb');

// Admin Credentials (change these!)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');  // Change this to a strong password

// Session timeout (in seconds) — 2 hours
define('SESSION_TIMEOUT', 7200);

// Site URL
define('SITE_URL', 'http://localhost/your-project-folder'); // Change for production

// Connect to DB
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
?>
