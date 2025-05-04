<?php
class Database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "general_service_office";
    private $conn;
    
    private static $instance = null;
    
    private function __construct() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        $data = $this->conn->real_escape_string($data);
        return $data;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    private function __clone() {}
 
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }
}
?>