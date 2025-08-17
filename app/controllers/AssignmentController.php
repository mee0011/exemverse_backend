<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Assignment.php';

class AssignmentController extends Controller {
    private Assignment $assignmentModel;

    public function __construct() {
        $this->assignmentModel = new Assignment();
    }
    

    public function listByInstructor() {
        $email = $_GET['instructor_email'] ?? null;
        if (!$email) { return $this->respond(400, ['error' => 'instructor_email is required']); }
        $data = $this->assignmentModel->getByInstructor($email);
        return $this->respond(200, ['assignments' => $data]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->respond(405, ['error' => 'Method not allowed']);
        }
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $courseCode = $_POST['course_code'] ?? '';
        $instructorEmail = $_POST['instructor_email'] ?? '';
        $dueDate = $_POST['due_date'] ?? '';
        $totalPoints = (int)($_POST['total_points'] ?? 100);
        if (!$title || !$courseCode || !$instructorEmail || !$dueDate) {
            return $this->respond(400, ['error' => 'Missing fields']);
        }
        // Handle optional PDF upload
        $pdfPath = null;
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../storage/assignments/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $base = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $_FILES['pdf']['name']);
            $name = time() . '_' . $base;
            $target = $uploadDir . $name;
            if (move_uploaded_file($_FILES['pdf']['tmp_name'], $target)) {
                $pdfPath = 'assignments/' . $name;
            }
        }
        $id = $this->assignmentModel->create($title, $description, $courseCode, $instructorEmail, $dueDate, $totalPoints);
        if (!$id) { return $this->respond(500, ['error' => 'Failed to create assignment']); }
        return $this->respond(201, ['message' => 'Assignment created', 'id' => $id, 'pdf_path' => $pdfPath]);
    }

    public function update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->respond(405, ['error' => 'Method not allowed']);
        }
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) return $this->respond(400, ['error' => 'id is required']);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $courseCode = $_POST['course_code'] ?? '';
        $dueDate = $_POST['due_date'] ?? '';
        $totalPoints = (int)($_POST['total_points'] ?? 100);
        $ok = $this->assignmentModel->update((int)$id, $title, $description, $courseCode, $dueDate, $totalPoints);
        if (!$ok) return $this->respond(500, ['error' => 'Failed to update assignment']);
        return $this->respond(200, ['message' => 'Assignment updated']);
    }

    public function delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->respond(405, ['error' => 'Method not allowed']);
        }
        $id = $id ?? ($_GET['id'] ?? null);
        $email = $_GET['instructor_email'] ?? null;
        if (!$id || !$email) return $this->respond(400, ['error' => 'id and instructor_email required']);
        $ok = $this->assignmentModel->delete((int)$id, $email);
        if (!$ok) return $this->respond(404, ['error' => 'Not found or not owned']);
        return $this->respond(200, ['message' => 'Assignment deleted']);
    }

    public function recentSubmissions() {
        $email = $_GET['instructor_email'] ?? null;
        if (!$email) return $this->respond(400, ['error' => 'instructor_email is required']);
        $list = $this->assignmentModel->getRecentSubmissions($email, 10);
        return $this->respond(200, ['submissions' => $list]);
    }

    private function respond($status, $data) {
        if (!headers_sent()) { http_response_code($status); header('Content-Type: application/json'); }
        echo json_encode($data); exit;
    }
    public function getByStudent() {
    $studentEmail = $_GET['student_email'] ?? null;
    if (!$studentEmail) {
        $this->respond(400, ['error' => 'student_email is required']);
        return;
    }

    // Get assignments from courses the student is enrolled in
    $sql = "SELECT a.id, a.title, a.description, a.course_code, a.due_date, a.total_points, a.created_at,
                   c.course_name,
                   CASE 
                       WHEN sub.id IS NOT NULL THEN 'submitted'
                       WHEN a.due_date < CURDATE() THEN 'overdue'
                       ELSE 'pending'
                   END as status,
                   sub.grade,
                   sub.submitted_at
            FROM assignments a
            INNER JOIN enrollments e ON a.course_code = e.course_code
            INNER JOIN courses c ON a.course_code = c.course_code
            LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_email = ?
            WHERE e.student_email = ?
            ORDER BY a.due_date ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$studentEmail, $studentEmail]);
    $assignments = $stmt->fetchAll();
    
    $this->respond(200, ['assignments' => $assignments]);
}
}
