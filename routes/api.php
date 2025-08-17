<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/CourseController.php';
require_once __DIR__ . '/../app/controllers/InstructorController.php';
require_once __DIR__ . '/../app/controllers/AssignmentController.php';
require_once __DIR__ . '/../app/controllers/InstructorController.php';

$router = new Router();

// Test route for CORS verification
$router->post('/test', function() {
    header('Content-Type: application/json');
    echo json_encode(['message' => 'CORS test successful', 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
});
$router->options('/test', function() {
    http_response_code(204);
    exit;
});

// Auth routes
$router->post('/auth/login', [AuthController::class, 'login']);
$router->options('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->options('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->options('/auth/logout', [AuthController::class, 'logout']);

// Profile routes
$router->get('/profile', [AuthController::class, 'getProfile']);
$router->options('/profile', [AuthController::class, 'getProfile']);

$router->get('/users', [AuthController::class, 'getAllUsers']);
$router->options('/users', [AuthController::class, 'getAllUsers']);

// Course routes
$router->get('/courses', [CourseController::class, 'getAll']);
$router->options('/courses', [CourseController::class, 'getAll']);
$router->post('/courses', [CourseController::class, 'create']);
$router->options('/courses', [CourseController::class, 'create']);
$router->get('/courses/{id}', [CourseController::class, 'getById']);
$router->options('/courses/{id}', [CourseController::class, 'getById']);
$router->post('/courses/{id}', [CourseController::class, 'update']);
$router->options('/courses/{id}', [CourseController::class, 'update']);
$router->delete('/courses/{id}', [CourseController::class, 'delete']);
$router->options('/courses/{id}', [CourseController::class, 'delete']);

// Enrollment routes
$router->post('/enroll', [CourseController::class, 'enroll']);
$router->options('/enroll', [CourseController::class, 'enroll']);
$router->get('/enrolled-courses', [CourseController::class, 'getEnrolledCourses']);
$router->options('/enrolled-courses', [CourseController::class, 'getEnrolledCourses']);
$router->get('/enrollment-count', [CourseController::class, 'getEnrollmentCount']);
$router->options('/enrollment-count', [CourseController::class, 'getEnrollmentCount']);

// Instructor Dashboard routes
$router->get('/instructor/dashboard-stats', [InstructorController::class, 'getDashboardStats']);
$router->options('/instructor/dashboard-stats', [InstructorController::class, 'getDashboardStats']);
$router->get('/instructor/courses', [InstructorController::class, 'getInstructorCourses']);
$router->options('/instructor/courses', [InstructorController::class, 'getInstructorCourses']);
$router->get('/instructor/exams', [InstructorController::class, 'getInstructorExams']);
$router->options('/instructor/exams', [InstructorController::class, 'getInstructorExams']);
$router->get('/instructor/exams/{id}', [InstructorController::class, 'getExamDetails']);
$router->options('/instructor/exams/{id}', [InstructorController::class, 'getExamDetails']);
$router->get('/instructor/grades/{course_id}', [InstructorController::class, 'getGradesByCourse']);
$router->options('/instructor/grades/{course_id}', [InstructorController::class, 'getGradesByCourse']);
$router->get('/instructor/students/{course_id}', [InstructorController::class, 'getEnrolledStudentsForGrades']);
$router->options('/instructor/students/{course_id}', [InstructorController::class, 'getEnrolledStudentsForGrades']);
$router->post('/instructor/update-grades', [InstructorController::class, 'updateGrades']);
$router->options('/instructor/update-grades', [InstructorController::class, 'updateGrades']);
$router->post('/instructor/exams', [InstructorController::class, 'createExam']);
$router->options('/instructor/exams', [InstructorController::class, 'createExam']);
$router->put('/instructor/exams', [InstructorController::class, 'updateExam']);
$router->options('/instructor/exams', [InstructorController::class, 'updateExam']);
$router->delete('/instructor/exams/{id}', [InstructorController::class, 'deleteExam']);
$router->options('/instructor/exams/{id}', [InstructorController::class, 'deleteExam']);

// Answer scripts
$router->get('/instructor/exam-submissions', [InstructorController::class, 'getExamSubmissions']);
$router->options('/instructor/exam-submissions', [InstructorController::class, 'getExamSubmissions']);
$router->get('/instructor/exam-mcq-answers', [InstructorController::class, 'getExamMcqAnswers']);
$router->options('/instructor/exam-mcq-answers', [InstructorController::class, 'getExamMcqAnswers']);

// Additional instructor routes for new features
$router->get('/instructor/course/{course_code}/students', [InstructorController::class, 'getCourseStudents']);
$router->options('/instructor/course/{course_code}/students', [InstructorController::class, 'getCourseStudents']);
$router->post('/instructor/course/{course_code}/enroll-student', [InstructorController::class, 'enrollStudent']);
$router->options('/instructor/course/{course_code}/enroll-student', [InstructorController::class, 'enrollStudent']);
$router->get('/instructor/course/{course_code}/materials', [InstructorController::class, 'getCourseMaterials']);
$router->options('/instructor/course/{course_code}/materials', [InstructorController::class, 'getCourseMaterials']);
$router->post('/instructor/course/{course_code}/upload-material', [InstructorController::class, 'uploadMaterial']);
$router->options('/instructor/course/{course_code}/upload-material', [InstructorController::class, 'uploadMaterial']);
$router->get('/instructor/course/{course_code}/grade-students', [InstructorController::class, 'getStudentsForGrading']);
$router->options('/instructor/course/{course_code}/grade-students', [InstructorController::class, 'getStudentsForGrading']);
$router->post('/instructor/course/{course_code}/save-grades', [InstructorController::class, 'saveGrades']);
$router->options('/instructor/course/{course_code}/save-grades', [InstructorController::class, 'saveGrades']);
$router->delete('/instructor/remove-pending-grades', [InstructorController::class, 'removePendingGrades']);
$router->options('/instructor/remove-pending-grades', [InstructorController::class, 'removePendingGrades']);

// Assignment management
$router->get('/instructor/assignments', [AssignmentController::class, 'listByInstructor']);
$router->options('/instructor/assignments', [AssignmentController::class, 'listByInstructor']);
$router->post('/instructor/assignments', [AssignmentController::class, 'create']);
$router->options('/instructor/assignments', [AssignmentController::class, 'create']);
$router->post('/instructor/assignments/{id}', [AssignmentController::class, 'update']);
$router->options('/instructor/assignments/{id}', [AssignmentController::class, 'update']);
$router->delete('/instructor/assignments/{id}', [AssignmentController::class, 'delete']);
$router->options('/instructor/assignments/{id}', [AssignmentController::class, 'delete']);
$router->get('/instructor/assignment-submissions', [AssignmentController::class, 'recentSubmissions']);
$router->options('/instructor/assignment-submissions', [AssignmentController::class, 'recentSubmissions']);


$router->dispatch();