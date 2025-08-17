<?php
require_once __DIR__ . '/../../core/Model.php';

class StudentGrades extends Model {
    private $table = 'student_grades';

    public function __construct() {
        parent::__construct();
    }

    public function getGradesByInstructor($instructorEmail) {
        $sql = "SELECT sg.*, c.course_name, u.name as student_name 
                FROM {$this->table} sg 
                JOIN courses c ON sg.course_code = c.course_code 
                LEFT JOIN users u ON sg.student_email = u.email 
                WHERE c.instructor_email = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        
        return $stmt->fetchAll();
    }

    public function getGradesByCourse($courseId) {
        // Resolve course_code from course ID
        $courseStmt = $this->db->prepare('SELECT course_code FROM courses WHERE id = ?');
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();
        if (!$course) {
            return [];
        }
        $sql = "SELECT sg.* 
                FROM {$this->table} sg 
                WHERE sg.course_code = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$course['course_code']]);
        
        return $stmt->fetchAll();
    }

    public function getPendingGrades($instructorEmail) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} sg 
                JOIN courses c ON sg.course_code = c.course_code 
                WHERE c.instructor_email = ? AND sg.cgpa = 0.00";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function createOrUpdateGrade($studentId, $courseId, $studentName, $studentIdNumber, $courseCode, $quizScore, $midtermScore, $finalScore, $othersScore) {
        $cgpa = $this->calculateCGPA($quizScore, $midtermScore, $finalScore, $othersScore);
        
        $sql = "INSERT INTO {$this->table} (student_id, course_code, student_name, student_id_number, student_email, quiz_score, midterm_score, final_score, others_score, cgpa) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name),
                student_id_number = VALUES(student_id_number),
                student_email = VALUES(student_email),
                quiz_score = VALUES(quiz_score),
                midterm_score = VALUES(midterm_score),
                final_score = VALUES(final_score),
                others_score = VALUES(others_score),
                cgpa = VALUES(cgpa)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId, $courseCode, $studentName, $studentIdNumber, null, $quizScore, $midtermScore, $finalScore, $othersScore, $cgpa]);
        
        return $stmt->rowCount() > 0;
    }

    public function updateGrade($id, $quizScore, $midtermScore, $finalScore, $othersScore) {
        $cgpa = $this->calculateCGPA($quizScore, $midtermScore, $finalScore, $othersScore);
        
        $sql = "UPDATE {$this->table} 
                SET quiz_score = ?, midterm_score = ?, final_score = ?, others_score = ?, cgpa = ? 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quizScore, $midtermScore, $finalScore, $othersScore, $cgpa, $id]);
        
        return $stmt->rowCount() > 0;
    }

    public function updateGradeById($gradeId, $quizScore, $midtermScore, $finalScore, $othersScore) {
        $cgpa = $this->calculateCGPA($quizScore, $midtermScore, $finalScore, $othersScore);
        
        $sql = "UPDATE {$this->table} 
                SET quiz_score = ?, midterm_score = ?, final_score = ?, others_score = ?, cgpa = ? 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quizScore, $midtermScore, $finalScore, $othersScore, $cgpa, $gradeId]);
        
        return $stmt->rowCount() > 0;
    }

    public function getAverageScores($courseId) {
        $courseStmt = $this->db->prepare('SELECT course_code FROM courses WHERE id = ?');
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();
        if (!$course) {
            return [
                'avg_quiz' => 0,
                'avg_midterm' => 0,
                'avg_final' => 0,
                'avg_others' => 0,
                'avg_cgpa' => 0,
            ];
        }
        $sql = "SELECT 
                    AVG(quiz_score) as avg_quiz,
                    AVG(midterm_score) as avg_midterm,
                    AVG(final_score) as avg_final,
                    AVG(others_score) as avg_others,
                    AVG(cgpa) as avg_cgpa
                FROM {$this->table} 
                WHERE course_code = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$course['course_code']]);
        
        return $stmt->fetch();
    }

    private function calculateCGPA($quizScore, $midtermScore, $finalScore, $othersScore) {
        // Interpret inputs as raw marks: Quiz out of 15, Midterm 25, Final 40, Others 20
        $totalScore = (float)$quizScore + (float)$midtermScore + (float)$finalScore + (float)$othersScore; // Max 100
        
        if ($totalScore >= 80) return 4.00;
        elseif ($totalScore >= 75) return 3.75;
        elseif ($totalScore >= 70) return 3.50;
        elseif ($totalScore >= 65) return 3.25;
        elseif ($totalScore >= 60) return 3.00;
        elseif ($totalScore >= 55) return 2.75;
        elseif ($totalScore >= 50) return 2.50;
        elseif ($totalScore >= 45) return 2.25;
        elseif ($totalScore >= 40) return 2.00;
        else return 0.00;
    }

    public function getEnrolledStudentsForGrades($courseId) {
        $courseStmt = $this->db->prepare('SELECT course_code FROM courses WHERE id = ?');
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();
        if (!$course) {
            return [];
        }
        $sql = "SELECT u.id, u.name, u.email, sd.student_id as student_id_number
                FROM users u 
                LEFT JOIN student_details sd ON u.id = sd.user_id
                JOIN enrollments e ON u.id = e.student_id 
                WHERE e.course_code = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$course['course_code']]);
        
        return $stmt->fetchAll();
    }

    public function getStudentsForGrading($courseCode) {
        $sql = "SELECT u.name, u.email, e.student_email
                FROM users u 
                JOIN enrollments e ON u.email = e.student_email 
                WHERE e.course_code = ? 
                AND u.email NOT IN (
                    SELECT student_email FROM {$this->table} WHERE course_code = ?
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseCode, $courseCode]);
        
        return $stmt->fetchAll();
    }

    public function saveGrade($studentName, $studentEmail, $courseCode, $quiz, $midterm, $final, $other) {
        $cgpa = $this->calculateCGPA($quiz, $midterm, $final, $other);
        
        $sql = "INSERT INTO {$this->table} (student_name, student_email, course_code, quiz_score, midterm_score, final_score, others_score, cgpa) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name),
                quiz_score = VALUES(quiz_score),
                midterm_score = VALUES(midterm_score),
                final_score = VALUES(final_score),
                others_score = VALUES(others_score),
                cgpa = VALUES(cgpa)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentName, $studentEmail, $courseCode, $quiz, $midterm, $final, $other, $cgpa]);
        
        return $stmt->rowCount() > 0;
    }

    public function removePendingGrades($instructorEmail) {
        $sql = "DELETE sg FROM {$this->table} sg 
                JOIN courses c ON sg.course_code = c.course_code 
                WHERE c.instructor_email = ? AND sg.cgpa = 0.00";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        
        return $stmt->rowCount() > 0;
    }
}
