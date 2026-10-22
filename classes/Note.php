<?php
class Note {
    private $conn;
    private $table_name = "notes";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch all notes from MySQL
    public function getNotes() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch notes for a specific user
    public function getNotesByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :uid ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insert a new note into MySQL
    public function addNote($title, $tag, $user_id = 1) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, title, tag) VALUES (:user_id, :title, :tag)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title',   $title);
        $stmt->bindParam(':tag',     $tag);

        return $stmt->execute();
    }

    // Delete a note from MySQL
    public function deleteNote($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>