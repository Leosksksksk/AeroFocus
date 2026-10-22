<?php
class Subject {
    private $conn;
    private $table_name = "subjects";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getSubjects() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get subjects owned by a specific user
    public function getSubjectsByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :uid";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSubject($units, $room, $subject_name, $instructor_name, $user_id = 1) {
        $query = "INSERT INTO " . $this->table_name .
                 " (user_id, units, room, subject_name, instructor_name) VALUES (:user_id, :units, :room, :subject_name, :instructor_name)";
        $stmt  = $this->conn->prepare($query);

        $stmt->bindParam(':user_id',         $user_id,         PDO::PARAM_INT);
        $stmt->bindParam(':units',           $units);
        $stmt->bindParam(':room',            $room);
        $stmt->bindParam(':subject_name',    $subject_name);
        $stmt->bindParam(':instructor_name', $instructor_name);

        return $stmt->execute();
    }

    public function getSubjectById($subject_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE subject_id = :subject_id LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateSubject($subject_id, $units, $room, $subject_name, $instructor_name) {
        $query = "UPDATE " . $this->table_name .
                 " SET units = :units, room = :room, subject_name = :subject_name, instructor_name = :instructor_name WHERE subject_id = :subject_id";
        $stmt  = $this->conn->prepare($query);

        $stmt->bindParam(':units',           $units);
        $stmt->bindParam(':room',            $room);
        $stmt->bindParam(':subject_name',    $subject_name);
        $stmt->bindParam(':instructor_name', $instructor_name);
        $stmt->bindParam(':subject_id',      $subject_id);

        return $stmt->execute();
    }

    public function deleteSubject($subject_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE subject_id = :subject_id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        return $stmt->execute();
    }
}
?>