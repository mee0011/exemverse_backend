<?php
require_once __DIR__ . '/../../core/Model.php';

class Question extends Model {
    private $table = 'exam_questions';

    public function __construct() {
        parent::__construct();
    }

    public function addQuestion($examId, $questionType, $questionText, $optionsJson = null, $correctOption = null) {
        $sql = "INSERT INTO {$this->table} (exam_id, question_type, question_text, options, correct_option)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$examId, $questionType, $questionText, $optionsJson, $correctOption]);
    }

    public function addQuestionsBulk($examId, $questions) {
        $sql = "INSERT INTO {$this->table} (exam_id, question_type, question_text, options, correct_option)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        foreach ($questions as $q) {
            $optionsJson = isset($q['options']) ? json_encode($q['options']) : null;
            $correctOption = $q['correct_option'] ?? null;
            $ok = $stmt->execute([
                $examId,
                $q['question_type'],
                $q['question_text'],
                $optionsJson,
                $correctOption
            ]);
            if (!$ok) return false;
        }
        return true;
    }

    public function getByExamId($examId) {
        $sql = "SELECT * FROM {$this->table} WHERE exam_id = ? ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$examId]);
        $rows = $stmt->fetchAll();
        // Decode JSON options
        foreach ($rows as &$row) {
            if (isset($row['options']) && $row['options']) {
                $decoded = json_decode($row['options'], true);
                $row['options'] = is_array($decoded) ? $decoded : [];
            } else {
                $row['options'] = [];
            }
        }
        return $rows;
    }

    public function deleteByExamId($examId) {
        $sql = "DELETE FROM {$this->table} WHERE exam_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$examId]);
    }
}
?>
