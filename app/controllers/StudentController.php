<?php
// app/controllers/StudentController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/StudentModel.php';

class StudentController extends Controller {
    private $studentModel;

    public function __construct() {
        $this->studentModel = new StudentModel();
    }

    /**
     * Fetch student dashboard data
     */
    public function getDashboard() {
        // Start output buffering to catch any unwanted output
        ob_start();
        
        try {
            error_log("Entering getDashboard method, Request Method: " . $_SERVER['REQUEST_METHOD']);
            
            session_start();
            
            // Check if user is authenticated and is a student (temporarily allowing instructor for testing)
            if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['student', 'instructor'])) {
                error_log("Unauthorized access attempt - no user session or invalid role");
                ob_end_clean(); // Clear any buffered output
                $this->json(['error' => 'Unauthorized access'], 401);
                return;
            }

            error_log("User session found: " . print_r($_SESSION['user'], true));

            // Get student data first
            $studentData = $this->studentModel->findById($_SESSION['user']['id']);
            if (!$studentData) {
                error_log("Student not found with ID: " . $_SESSION['user']['id']);
                ob_end_clean();
                $this->json(['error' => 'Student not found'], 404);
                return;
            }

            $studentEmail = $studentData['email'];
            if (!$studentEmail) {
                error_log("Student email not found for user ID: " . $_SESSION['user']['id']);
                ob_end_clean();
                $this->json(['error' => 'Student email not found'], 404);
                return;
            }

            error_log("Student email: " . $studentEmail);

            // Fetch dashboard data
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentEmail);
            $pendingAssignments = $this->studentModel->getPendingAssignments($studentEmail);
            $upcomingExams = $this->studentModel->getUpcomingExams($studentEmail);

            error_log("Enrolled courses count: " . count($enrolledCourses));
            error_log("Pending assignments count: " . count($pendingAssignments));
            error_log("Upcoming exams count: " . count($upcomingExams));

            // Prepare response
            $response = [
                'enrolledCourses' => [
                    'count' => count($enrolledCourses),
                    'courses' => $enrolledCourses
                ],
                'pendingAssignments' => [
                    'count' => count($pendingAssignments),
                    'assignments' => $pendingAssignments
                ],
                'upcomingExams' => [
                    'count' => count($upcomingExams),
                    'exams' => $upcomingExams
                ]
            ];

            // Clear any buffered output before sending JSON
            ob_end_clean();
            $this->json($response, 200);

        } catch (Exception $e) {
            error_log("Error in getDashboard: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            ob_end_clean();
            $this->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }
}
?>