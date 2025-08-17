<?php
require_once __DIR__ . '/../../core/Model.php';

class Submission extends Model {
    private $submissionsTable = 'exam_submissions';
    private $answersTable = 'exam_answers';

    public function __construct() {
        parent::__construct();
    }

    public function getSubmissionsByInstructor($instructorEmail) {
        $sql = "SELECT e.title, s.course_code, s.student_name, s.student_email, s.submitted_at, s.file_path, s.exam_id, s.id as submission_id
                FROM {$this->submissionsTable} s
                JOIN exams e ON s.exam_id = e.id
                WHERE e.instructor_email = ?
                ORDER BY s.submitted_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        return $stmt->fetchAll();
    }

    public function getMcqAnswersForExamAndStudent($examId, $studentEmail) {
        $sql = "SELECT q.question_text, q.correct_option, a.student_answer
                FROM {$this->answersTable} a
                JOIN exam_questions q ON a.question_id = q.id
                JOIN {$this->submissionsTable} s ON a.submission_id = s.id
                WHERE s.exam_id = ? AND s.student_email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$examId, $studentEmail]);
        return $stmt->fetchAll();
    }
}
?>
