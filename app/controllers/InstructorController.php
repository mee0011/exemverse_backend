<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/StudentGrades.php';
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/User.php';

class InstructorController extends Controller {
    private $courseModel;
    private $examModel;
    private $studentGradesModel;
    private $enrollmentModel;
    private $userModel;

    public function __construct() {
        $this->courseModel = new Course();
        $this->examModel = new Exam();
        $this->studentGradesModel = new StudentGrades();
        $this->enrollmentModel = new Enrollment();
        $this->userModel = new User();
    }

    public function getDashboardStats() {
        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$instructorEmail) {
            $this->respond(400, ['error' => 'Instructor email is required']);
            return;
        }

        $totalCourses = $this->courseModel->getCourseCountByInstructor($instructorEmail);
        $totalStudents = $this->courseModel->getStudentCountByInstructor($instructorEmail);
        $totalExams = $this->examModel->getExamCountByInstructor($instructorEmail);
        $pendingGrades = $this->studentGradesModel->getPendingGrades($instructorEmail);

        $this->respond(200, [
            'total_courses' => $totalCourses,
            'total_students' => (int)$totalStudents,
            'total_exams' => $totalExams,
            'pending_grades' => (int)$pendingGrades
        ]);
    }

    public function getInstructorCourses() {
        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$instructorEmail) {
            $this->respond(400, ['error' => 'Instructor email is required']);
            return;
        }

        $courses = $this->courseModel->getCoursesWithStats($instructorEmail);
        $this->respond(200, ['courses' => $courses]);
    }

    public function getInstructorExams() {
        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$instructorEmail) {
            $this->respond(400, ['error' => 'Instructor email is required']);
            return;
        }

        $exams = $this->examModel->getExamsByInstructor($instructorEmail);
        $this->respond(200, ['exams' => $exams]);
    }

    public function getExamDetails($examId = null) {
        $examId = $examId ?? ($_GET['exam_id'] ?? null);
        if (!$examId) {
            $this->respond(400, ['error' => 'Exam ID is required']);
            return;
        }
        $exam = $this->examModel->getById((int)$examId);
        if (!$exam) {
            $this->respond(404, ['error' => 'Exam not found']);
            return;
        }
        require_once __DIR__ . '/../models/Question.php';
        $questionModel = new Question();
        $questions = $questionModel->getByExamId((int)$examId);
        $exam['questions'] = $questions;
        $this->respond(200, ['exam' => $exam]);
    }

    public function getGradesByCourse() {
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            $this->respond(400, ['error' => 'Course ID is required']);
            return;
        }

        $grades = $this->studentGradesModel->getGradesByCourse($courseId);
        $averageScores = $this->studentGradesModel->getAverageScores($courseId);
        
        $this->respond(200, [
            'grades' => $grades,
            'average_scores' => $averageScores
        ]);
    }

    public function getEnrolledStudentsForGrades() {
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            $this->respond(400, ['error' => 'Course ID is required']);
            return;
        }

        $students = $this->studentGradesModel->getEnrolledStudentsForGrades($courseId);
        $this->respond(200, ['students' => $students]);
    }

    public function updateGrades() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['grades']) || !is_array($data['grades'])) {
            $this->respond(400, ['error' => 'Grades data is required']);
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($data['grades'] as $grade) {
            if (isset($grade['id'])) {
                $result = $this->studentGradesModel->updateGrade(
                    $grade['id'],
                    $grade['quiz_score'] ?? 0,
                    $grade['midterm_score'] ?? 0,
                    $grade['final_score'] ?? 0,
                    $grade['others_score'] ?? 0
                );
            } else {
                $result = $this->studentGradesModel->createOrUpdateGrade(
                    $grade['student_id'],
                    $grade['course_id'],
                    $grade['student_name'],
                    $grade['student_id_number'],
                    $grade['course_code'],
                    $grade['quiz_score'] ?? 0,
                    $grade['midterm_score'] ?? 0,
                    $grade['final_score'] ?? 0,
                    $grade['others_score'] ?? 0
                );
            }

            if ($result) { $successCount++; } else { $errorCount++; }
        }

        $this->respond(200, [
            'message' => "Grades updated successfully",
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }

    public function createExam() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Expected payload per schema
        // title, course_code, exam_date (YYYY-MM-DD), start_time (HH:MM[:SS]), duration_minutes, instructions, status, instructor_email, questions[]
        $required = ['title','course_code','exam_date','start_time','duration_minutes','status','instructor_email'];
        foreach ($required as $r) {
            if (!isset($data[$r]) || $data[$r] === '') {
                $this->respond(400, ['error' => "Missing required field: $r"]);
                return;
            }
        }

        $examId = $this->examModel->create(
            $data['title'],
            $data['course_code'],
            $data['exam_date'],
            $data['start_time'],
            (int)$data['duration_minutes'],
            $data['instructions'] ?? null,
            $data['status'],
            $data['instructor_email']
        );

        if (!$examId) {
            $this->respond(500, ['error' => 'Failed to create exam']);
            return;
        }

        // Persist questions if provided
        if (!empty($data['questions']) && is_array($data['questions'])) {
            require_once __DIR__ . '/../models/Question.php';
            $questionModel = new Question();
            $bulk = [];
            foreach ($data['questions'] as $q) {
                $type = ($q['type'] ?? $q['question_type'] ?? '') === 'mcq' ? 'mcq' : 'short_answer';
                $questionText = $q['question'] ?? $q['question_text'] ?? '';
                if (!$questionText) { continue; }
                $entry = [
                    'question_type' => $type,
                    'question_text' => $questionText,
                ];
                if ($type === 'mcq') {
                    $entry['options'] = $q['options'] ?? [];
                    // correctAnswer may be index or value
                    if (isset($q['correctAnswer']) && is_numeric($q['correctAnswer']) && isset($entry['options'][(int)$q['correctAnswer']])) {
                        $entry['correct_option'] = (string)$entry['options'][(int)$q['correctAnswer']];
                    } else if (isset($q['correct_option'])) {
                        $entry['correct_option'] = (string)$q['correct_option'];
                    } else {
                        $entry['correct_option'] = null;
                    }
                }
                $bulk[] = $entry;
            }
            if ($bulk) {
                $ok = $questionModel->addQuestionsBulk($examId, $bulk);
                if (!$ok) {
                    $this->respond(500, ['error' => 'Exam created but failed to save questions']);
                    return;
                }
            }
        }

        $this->respond(201, [
            'message' => 'Exam created successfully',
            'exam_id' => (int)$examId
        ]);
    }

    public function updateExam() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            $this->respond(400, ['error' => 'Missing required field: id']);
            return;
        }
        $id = (int)$data['id'];
        unset($data['id']);
        $ok = $this->examModel->update($id, $data);
        if (!$ok) {
            $this->respond(500, ['error' => 'Failed to update exam']);
            return;
        }

        // Optionally update questions if provided
        if (isset($data['questions'])) {
            require_once __DIR__ . '/../models/Question.php';
            $questionModel = new Question();
            $questionModel->deleteByExamId($id);
            $bulk = [];
            foreach ($data['questions'] as $q) {
                $type = ($q['type'] ?? $q['question_type'] ?? '') === 'mcq' ? 'mcq' : 'short_answer';
                $questionText = $q['question'] ?? $q['question_text'] ?? '';
                if (!$questionText) { continue; }
                $entry = [
                    'question_type' => $type,
                    'question_text' => $questionText,
                ];
                if ($type === 'mcq') {
                    $entry['options'] = $q['options'] ?? [];
                    if (isset($q['correctAnswer']) && is_numeric($q['correctAnswer']) && isset($entry['options'][(int)$q['correctAnswer']])) {
                        $entry['correct_option'] = (string)$entry['options'][(int)$q['correctAnswer']];
                    } else if (isset($q['correct_option'])) {
                        $entry['correct_option'] = (string)$q['correct_option'];
                    } else {
                        $entry['correct_option'] = null;
                    }
                }
                $bulk[] = $entry;
            }
            if ($bulk) {
                $questionModel->addQuestionsBulk($id, $bulk);
            }
        }
        $this->respond(200, ['message' => 'Exam updated successfully']);
    }

    public function deleteExam($paramId = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        // Accept from route param or query
        $examId = $paramId ?? ($_GET['id'] ?? null);
        
        if (!$examId) {
            $this->respond(400, ['error' => 'Exam ID is required']);
            return;
        }

        $result = $this->examModel->delete($examId);

        if ($result) {
            $this->respond(200, ['message' => 'Exam deleted successfully']);
        } else {
            $this->respond(500, ['error' => 'Failed to delete exam']);
        }
    }

    // New methods for course management
    public function getCourseStudents($courseCode = null) {
        // Get course code from route parameter or URL parameter
        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$courseCode || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        $students = $this->enrollmentModel->getEnrolledStudentsByCourseCode($courseCode);
        $this->respond(200, ['students' => $students]);
    }

    public function enrollStudent($courseCode = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        $studentEmail = $data['student_email'] ?? null;
        $instructorEmail = $data['instructor_email'] ?? null;

        if (!$courseCode || !$studentEmail || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code, student email, and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        // Get student user
        $student = $this->userModel->findByEmail($studentEmail);
        if (!$student) {
            $this->respond(404, ['error' => 'Student not found']);
            return;
        }

        // Check if already enrolled
        if ($this->enrollmentModel->isEnrolledByEmail($studentEmail, $courseCode)) {
            $this->respond(409, ['error' => 'Student is already enrolled in this course']);
            return;
        }

        $result = $this->enrollmentModel->enrollByEmail($studentEmail, $courseCode);
        if ($result) {
            // Maintain total_students for dashboard stat 2
            $this->courseModel->adjustTotalStudentsByCourseCode($courseCode, +1);
            $this->respond(201, ['message' => 'Student enrolled successfully']);
        } else {
            $this->respond(500, ['error' => 'Failed to enroll student']);
        }
    }

    public function unenrollStudent($courseCode = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $studentEmail = $_GET['student_email'] ?? null;
        $instructorEmail = $_GET['instructor_email'] ?? null;

        if (!$courseCode || !$studentEmail || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code, student email, and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        $result = $this->enrollmentModel->unenrollByEmail($studentEmail, $courseCode);
        if ($result) {
            // Maintain total_students for dashboard stat 2
            $this->courseModel->adjustTotalStudentsByCourseCode($courseCode, -1);
            $this->respond(200, ['message' => 'Student unenrolled successfully']);
        } else {
            $this->respond(500, ['error' => 'Failed to unenroll student']);
        }
    }

    // Course materials methods
    public function getCourseMaterials($courseCode = null) {
        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$courseCode || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        $materials = $this->courseModel->getCourseMaterials($courseCode);
        $this->respond(200, ['materials' => $materials]);
    }

    public function uploadMaterial($courseCode = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $instructorEmail = $_POST['instructor_email'] ?? null;

        if (!$courseCode || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        if (!isset($_FILES['material']) || $_FILES['material']['error'] !== UPLOAD_ERR_OK) {
            $this->respond(400, ['error' => 'File upload failed']);
            return;
        }

        $uploadDir = __DIR__ . '/../../storage/materials/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $original = $_FILES['material']['name'];
        $cleanName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $original);
        $fileName = time() . '_' . $cleanName;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['material']['tmp_name'], $filePath)) {
            $result = $this->courseModel->addCourseMaterial($courseCode, 'materials/' . $fileName);
            if ($result) {
                $this->respond(201, ['message' => 'Material uploaded successfully']);
            } else {
                $this->respond(500, ['error' => 'Failed to save material record']);
            }
        } else {
            $this->respond(500, ['error' => 'Failed to upload file']);
        }
    }

    public function deleteMaterial($materialId = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $materialId = $materialId ?? ($_GET['material_id'] ?? null);
        $instructorEmail = $_GET['instructor_email'] ?? null;

        if (!$materialId || !$instructorEmail) {
            $this->respond(400, ['error' => 'Material ID and instructor email are required']);
            return;
        }

        $result = $this->courseModel->deleteCourseMaterial($materialId, $instructorEmail);
        if ($result) {
            $this->respond(200, ['message' => 'Material deleted successfully']);
        } else {
            $this->respond(500, ['error' => 'Failed to delete material']);
        }
    }

    // Grade management methods
    public function getStudentsForGrading($courseCode = null) {
        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$courseCode || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        $students = $this->studentGradesModel->getStudentsForGrading($courseCode);
        $this->respond(200, ['students' => $students]);
    }

    public function saveGrades($courseCode = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $courseCode = $courseCode ?? $_GET['course_code'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        $instructorEmail = $data['instructor_email'] ?? null;
        $grades = $data['grades'] ?? [];

        if (!$courseCode || !$instructorEmail) {
            $this->respond(400, ['error' => 'Course code and instructor email are required']);
            return;
        }

        // Verify instructor owns this course
        $course = $this->courseModel->getByCourseCode($courseCode);
        if (!$course || $course['instructor_email'] !== $instructorEmail) {
            $this->respond(403, ['error' => 'Access denied']);
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($grades as $grade) {
            $ok = $this->studentGradesModel->saveGrade(
                $grade['student_name'],
                $grade['student_email'],
                $courseCode,
                $grade['quiz'] ?? 0,
                $grade['midterm'] ?? 0,
                $grade['final'] ?? 0,
                $grade['other'] ?? 0
            );

            if ($ok) { $successCount++; } else { $errorCount++; }
        }

        $this->respond(200, [
            'message' => 'Grades saved successfully',
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }

    public function updateGrade($gradeId = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $gradeId = $gradeId ?? ($_GET['grade_id'] ?? null);
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$gradeId) {
            $this->respond(400, ['error' => 'Grade ID is required']);
            return;
        }

        $result = $this->studentGradesModel->updateGradeById(
            $gradeId,
            $data['quiz'] ?? 0,
            $data['midterm'] ?? 0,
            $data['final'] ?? 0,
            $data['other'] ?? 0
        );

        if ($result) {
            $this->respond(200, ['message' => 'Grade updated successfully']);
        } else {
            $this->respond(500, ['error' => 'Failed to update grade']);
        }
    }

    public function removePendingGrades() {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $instructorEmail = $_GET['instructor_email'] ?? null;
        
        if (!$instructorEmail) {
            $this->respond(400, ['error' => 'Instructor email is required']);
            return;
        }

        $ok = $this->studentGradesModel->removePendingGrades($instructorEmail);
        if ($ok) {
            $this->respond(200, ['message' => 'Pending grades removed successfully']);
        } else {
            $this->respond(200, ['message' => 'No pending grades to remove']);
        }
    }

    // Exam submissions for Answer Scripts view
    public function getExamSubmissions() {
        $instructorEmail = $_GET['instructor_email'] ?? null;
        if (!$instructorEmail) {
            $this->respond(400, ['error' => 'Instructor email is required']);
            return;
        }
        require_once __DIR__ . '/../models/Submission.php';
        $submissionModel = new Submission();
        $rows = $submissionModel->getSubmissionsByInstructor($instructorEmail);
        $this->respond(200, ['submissions' => $rows]);
    }

    public function getExamMcqAnswers() {
        $examId = $_GET['exam_id'] ?? null;
        $studentEmail = $_GET['student_email'] ?? null;
        if (!$examId || !$studentEmail) {
            $this->respond(400, ['error' => 'exam_id and student_email are required']);
            return;
        }
        require_once __DIR__ . '/../models/Submission.php';
        $submissionModel = new Submission();
        $rows = $submissionModel->getMcqAnswersForExamAndStudent((int)$examId, $studentEmail);
        $this->respond(200, ['answers' => $rows]);
    }

    private function respond($status, $data) {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json');
        }
        echo json_encode($data);
        exit;
    }
    public function getExamsForStudent() {
    $studentEmail = $_GET['student_email'] ?? null;
    if (!$studentEmail) {
        $this->respond(400, ['error' => 'student_email is required']);
        return;
    }

    // Get published exams from courses the student is enrolled in
    $sql = "SELECT e.id, e.title, e.course_code, e.exam_date, e.start_time, e.duration_minutes, e.instructions,
                   c.course_name,
                   CASE 
                       WHEN sub.id IS NOT NULL THEN 'completed'
                       WHEN CONCAT(e.exam_date, ' ', e.start_time) < NOW() THEN 'missed'
                       ELSE 'upcoming'
                   END as status,
                   sub.submitted_at
            FROM exams e
            INNER JOIN enrollments en ON e.course_code = en.course_code
            INNER JOIN courses c ON e.course_code = c.course_code
            LEFT JOIN exam_submissions sub ON e.id = sub.exam_id AND sub.student_email = ?
            WHERE en.student_email = ? AND e.status = 'published'
            ORDER BY e.exam_date ASC, e.start_time ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$studentEmail, $studentEmail]);
    $exams = $stmt->fetchAll();
    
    $this->respond(200, ['exams' => $exams]);
}

public function getStudentGrades() {
    $studentEmail = $_GET['student_email'] ?? null;
    if (!$studentEmail) {
        $this->respond(400, ['error' => 'student_email is required']);
        return;
    }

    // Get grades for courses the student is enrolled in
    $sql = "SELECT sg.*, c.course_name
            FROM student_grades sg
            INNER JOIN courses c ON sg.course_code = c.course_code
            INNER JOIN enrollments e ON sg.course_code = e.course_code
            WHERE e.student_email = ? AND sg.student_email = ?
            ORDER BY c.course_name";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$studentEmail, $studentEmail]);
    $grades = $stmt->fetchAll();
    
    // Calculate overall CGPA
    $totalCGPA = 0;
    $courseCount = count($grades);
    foreach ($grades as $grade) {
        $totalCGPA += $grade['cgpa'];
    }
    $overallCGPA = $courseCount > 0 ? round($totalCGPA / $courseCount, 2) : 0.00;
    
    $this->respond(200, [
        'grades' => $grades,
        'overall_cgpa' => $overallCGPA,
        'total_courses' => $courseCount
    ]);
}
}
