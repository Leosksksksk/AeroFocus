<?php
/**
 * Database.php
 * -------------------------------------------------------
 * Handles the PDO connection for the AeroFocus system.
 * Uses a simple OOP wrapper so the connection can be
 * reused by any entity class (e.g. Assignment).
 * -------------------------------------------------------
 */

class Database
{
    // Update these to match your local XAMPP MySQL setup
    private $host = "localhost";
    private $dbName = "aerofocus_db";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";

    public $conn;

    /**
     * Opens and returns a PDO connection.
     * Throws a PDOException on failure so calling code
     * can catch it and show a friendly error message.
     */
    public function connect()
    {
        $this->conn = null;

        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbName . ";charset=" . $this->charset;

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            // Throw exceptions on errors instead of silent failures
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Return real data types (not everything as string)
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            // In production you would log this instead of echoing it
            die("Database Connection Error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
