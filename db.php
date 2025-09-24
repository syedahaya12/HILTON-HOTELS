<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'db2n2zsdgx2y4l');
define('DB_USER', 'uannmukxu07nw');
define('DB_PASS', 'nhh1divf0d2c');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        
        return $this->conn;
    }

    // Close connection
    public function closeConnection() {
        $this->conn = null;
    }

    // Execute query with parameters
    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $exception) {
            echo "Query error: " . $exception->getMessage();
            return false;
        }
    }

    // Get single record
    public function getSingle($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetch() : false;
    }

    // Get multiple records
    public function getMultiple($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }

    // Insert record and return last insert ID
    public function insert($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $this->conn->lastInsertId() : false;
    }

    // Update/Delete record and return affected rows
    public function execute($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
}

// Create global database instance
$database = new Database();
$db = $database->getConnection();

// Helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateBookingReference() {
    return 'HLT' . date('Y') . rand(100000, 999999);
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>
