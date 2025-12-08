<?php
class Database {
    private $host = "localhost";
    private $db_name = "jessiecane";
    // XAMPP default MySQL credentials
    // Change these if your MySQL has different credentials
    private $username = "root";
    private $password = "";  // Empty password is XAMPP default
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error: ' . $exception->getMessage()
            ]);
            exit;
        }
        
        return $this->conn;
    }
}
?>

