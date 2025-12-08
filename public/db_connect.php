<?php
$servername = "localhost";
$username = "root";  // default XAMPP username
$password = "";      // default XAMPP password is empty
$dbname = "jessiecane";

$conn = null;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        // Don't die, just set to null so calling code can handle it
        $conn = null;
    } else {
        // Set charset to UTF-8 to handle special characters
        $conn->set_charset("utf8mb4");
    }
} catch (Exception $e) {
    // Connection failed, set to null
    $conn = null;
}
?>
