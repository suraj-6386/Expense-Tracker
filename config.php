<?php
// ============================================================
//  config.php — Database Connection Configuration
//  Uses: mysqli (Procedural style)
//  Change the constants below to match your environment.
// ============================================================

define('DB_HOST',     'localhost');   // MySQL server host
define('DB_USER',     'root');        // MySQL username
define('DB_PASS',     '15056324');            // MySQL password (empty for XAMPP default)
define('DB_NAME',     'expense_tracker');
define('DB_PORT',     3306);

// -- Create and return a mysqli connection --------------------
function getConnection(): mysqli
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        // Show a user-friendly message and stop execution
        die('
            <div style="font-family:sans-serif;background:#fff3cd;color:#856404;
                        border:1px solid #ffc107;padding:20px;margin:40px auto;
                        max-width:600px;border-radius:8px;">
                <h3>&#9888; Database Connection Failed</h3>
                <p><strong>Error:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>
                <p>Please make sure:</p>
                <ul>
                    <li>XAMPP / WAMP is running and MySQL service is ON.</li>
                    <li>You have imported <code>DB_SETUP.txt</code> into phpMyAdmin.</li>
                    <li>The credentials in <code>config.php</code> match your MySQL setup.</li>
                </ul>
            </div>
        ');
    }

    // Set character encoding to utf8mb4 for full Unicode support
    $conn->set_charset('utf8mb4');

    return $conn;
}
?>
