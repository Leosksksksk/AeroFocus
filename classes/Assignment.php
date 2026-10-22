<?php
/**
 * Assignment.php
 * -------------------------------------------------------
 * Main entity class for the AeroFocus system.
 * Encapsulates all database operations for the
 * "assignments" table using PDO prepared statements.
 * -------------------------------------------------------
 */

class Assignment
{
    // Database connection and table name
    private $conn;
    private $table = "assignments";

    // Public properties -> map directly to table columns
    public $assignment_id;
    public $user_id;
    public $subject_id;
    public $title;
    public $description;
    public $due_date;
    public $priority;
    public $status;

    /**
     * The class receives an already-open PDO connection
     * through its constructor (dependency injection).
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * CREATE
     * Inserts a new assignment record using the object's
     * current property values.
     */
    public function addAssignment()
    {
        $sql = "INSERT INTO {$this->table}
                    (user_id, subject_id, title, description, due_date, priority, status)
                VALUES
                    (:user_id, :subject_id, :title, :description, :due_date, :priority, :status)";

        try {
            $stmt = $this->conn->prepare($sql);

            // Sanitize text input
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));

            $stmt->bindParam(":user_id",     $this->user_id,     PDO::PARAM_INT);
            $stmt->bindParam(":subject_id",  $this->subject_id,  PDO::PARAM_INT);
            $stmt->bindParam(":title",       $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":due_date",    $this->due_date);
            $stmt->bindParam(":priority",    $this->priority);
            $stmt->bindParam(":status",      $this->status);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("addAssignment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * READ (all)
     * Retrieves every assignment for the given user, joined with subject_name
     * for display, ordered by soonest due date first.
     */
    public function getAssignments($user_id = null)
    {
        $where = $user_id !== null ? "WHERE a.user_id = :uid" : "";
        $sql = "SELECT a.*, s.subject_name
                FROM {$this->table} a
                LEFT JOIN subjects s ON a.subject_id = s.subject_id
                {$where}
                ORDER BY a.due_date ASC";

        try {
            $stmt = $this->conn->prepare($sql);
            if ($user_id !== null) $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getAssignments error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * READ (single)
     * Retrieves one assignment by its ID. Used to
     * pre-fill the edit form.
     */
    public function getAssignmentById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE assignment_id = :id LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getAssignmentById error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * UPDATE
     * Updates an existing assignment identified by $id
     * with the object's current property values.
     */
    public function updateAssignment($id)
    {
        $sql = "UPDATE {$this->table}
                SET subject_id = :subject_id,
                    title = :title,
                    description = :description,
                    due_date = :due_date,
                    priority = :priority,
                    status = :status
                WHERE assignment_id = :id";

        try {
            $stmt = $this->conn->prepare($sql);

            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));

            $stmt->bindParam(":subject_id",  $this->subject_id,  PDO::PARAM_INT);
            $stmt->bindParam(":title",       $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":due_date",    $this->due_date);
            $stmt->bindParam(":priority",    $this->priority);
            $stmt->bindParam(":status",      $this->status);
            $stmt->bindParam(":id",          $id,                PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updateAssignment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE
     * Removes an assignment record by its ID.
     */
    public function deleteAssignment($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE assignment_id = :id";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("deleteAssignment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * SEARCH
     * Looks for the keyword across multiple fields,
     * optionally scoped to a single user.
     */
    public function searchAssignments($keyword, $user_id = null)
    {
        $userClause = $user_id !== null ? "AND a.user_id = :uid" : "";
        $sql = "SELECT a.*, s.subject_name
                FROM {$this->table} a
                LEFT JOIN subjects s ON a.subject_id = s.subject_id
                WHERE (a.title LIKE :kw1
                   OR a.description LIKE :kw2
                   OR a.priority LIKE :kw3
                   OR a.status LIKE :kw4
                   OR s.subject_name LIKE :kw5)
                {$userClause}
                ORDER BY a.due_date ASC";

        try {
            $stmt = $this->conn->prepare($sql);
            $search = "%" . $keyword . "%";

            // Bind the search string to each unique placeholder
            $stmt->bindParam(":kw1", $search, PDO::PARAM_STR);
            $stmt->bindParam(":kw2", $search, PDO::PARAM_STR);
            $stmt->bindParam(":kw3", $search, PDO::PARAM_STR);
            $stmt->bindParam(":kw4", $search, PDO::PARAM_STR);
            $stmt->bindParam(":kw5", $search, PDO::PARAM_STR);
            if ($user_id !== null) $stmt->bindParam(":uid", $user_id, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("searchAssignments error: " . $e->getMessage());
            return [];
        }
    }
}