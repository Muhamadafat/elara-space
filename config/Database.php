<?php
/**
 * Database Configuration and Connection
 * Elara Space - Library Management System
 */

class Database {
    private $host = "localhost";
    private $db_name = "elara_space";
    private $username = "root";
    private $password = "root"; // Laragon default password
    private $charset = "utf8mb4";
    public $conn;

    /**
     * Constructor - automatically connects to database
     */
    public function __construct() {
        $this->getConnection();
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }

    /**
     * Execute a prepared statement
     * Returns statement object on success, false on failure
     */
    public function execute($query, $params = []) {
        if ($this->conn === null) {
            error_log("Database Error: No database connection available");
            die("Database connection failed. Please check your database configuration.");
        }

        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            // Always return the statement object if execution succeeded
            // Let the caller handle the statement as needed
            return $result ? $stmt : false;
        } catch(PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            error_log("Query: " . $query);
            return false;
        }
    }

    /**
     * Get single row
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Get multiple rows
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
}
