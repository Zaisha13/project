<?php
$servername = "localhost";
$username = "pma";  // default XAMPP username
$password = "Akolangnamanto123";      // default XAMPP password is empty
$dbname = "jessiecane";

$conn = null;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        // Don't die, just set to null so calling code can handle it
        $conn = null;
    }
} catch (Exception $e) {
    // Connection failed, set to null
    $conn = null;
}
?>
