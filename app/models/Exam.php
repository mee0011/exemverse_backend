<?php
require_once __DIR__ . '/../../core/Model.php';

class Exam extends Model {
    private $table = 'exams';

    public function __construct() {
        parent::__construct();
    }

    public function getExamsByInstructor($instructorEmail) {
        // Align with exams schema that stores course_code directly
        $sql = "SELECT e.*
                FROM {$this->table} e
                WHERE e.instructor_email = ?
                ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        return $stmt->fetchAll();
    }

    public function getExamCountByInstructor($instructorEmail) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} e 
                WHERE e.instructor_email = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function create($title, $courseCode, $examDate, $startTime, $durationMinutes, $instructions, $status, $instructorEmail) {
        $sql = "INSERT INTO {$this->table}
                (title, course_code, exam_date, start_time, duration_minutes, instructions, status, instructor_email)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([$title, $courseCode, $examDate, $startTime, $durationMinutes, $instructions, $status, $instructorEmail]);
        if ($ok) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $fields) {
        if (!is_array($fields) || empty($fields)) return false;
        $allowed = ['title','course_code','exam_date','start_time','duration_minutes','instructions','status'];
        $set = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (in_array($k, $allowed, true)) { $set[] = "$k = ?"; $params[] = $v; }
        }
        if (!$set) return false;
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET ".implode(', ', $set)." WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() >= 0;
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
