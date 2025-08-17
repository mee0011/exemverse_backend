


<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/StudentDetails.php';

class AuthController extends Controller {
    private $userModel;
    private $studentDetailsModel;

    public function __construct() {
        $this->userModel = new User();
        $this->studentDetailsModel = new StudentDetails();
    }

    public function login() {
        error_log("Entering login method, Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
            $this->respond(400, ['error' => 'Missing required fields: email, password, or role']);
            return;
        }

        $user = $this->userModel->findByEmail($data['email']);

        if (!$user) {
            $this->respond(401, ['error' => 'Invalid email or password']);
            return;
        }

        if (!password_verify($data['password'], $user['password'])) {
            $this->respond(401, ['error' => 'Invalid email or password']);
            return;
        }

        if ($user['role'] !== $data['role']) {
            $this->respond(403, ['error' => 'Role mismatch']);
            return;
        }

        session_start();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        // Fetch student details if user is a student
        $studentDetails = null;
        if ($user['role'] === 'student') {
            $studentDetails = $this->studentDetailsModel->findByUserId($user['id']);
        }

        $response = [
            'name' => $user['name'],
            'role' => $user['role'],
            'message' => 'Login successful'
        ];

        if ($studentDetails) {
            $response['studentDetails'] = $studentDetails;
        }

        $this->respond(200, $response);
    }

    public function register() {
        error_log("Entering register method, Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name']) || !isset($data['role'])) {
            $this->respond(400, ['error' => 'Missing required fields: email, password, name, or role']);
            return;
        }

        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(400, ['error' => 'Invalid email format']);
            return;
        }
        if (strlen($data['password']) < 6) {
            $this->respond(400, ['error' => 'Password must be at least 6 characters']);
            return;
        }
        if (!in_array($data['role'], ['student', 'instructor', 'admin'])) {
            $this->respond(400, ['error' => 'Invalid role']);
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            $this->respond(409, ['error' => 'Email already registered']);
            return;
        }

        $result = $this->userModel->create($email, $data['password'], $data['name'], $data['role']);

        if ($result) {
            $this->respond(201, ['message' => 'Registration successful']);
        } else {
            $this->respond(500, ['error' => 'Registration failed. Please try again later.']);
        }
    }

    public function logout() {
        error_log("Entering logout method, Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        session_start();
        session_destroy();
        $this->respond(200, ['message' => 'Logout successful']);
    }

    public function getProfile() {
        error_log("Entering getProfile method, Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        session_start();
        
        if (!isset($_SESSION['user'])) {
            $this->respond(401, ['error' => 'Not authenticated']);
            return;
        }

        $user = $this->userModel->findById($_SESSION['user']['id']);
        
        if (!$user) {
            $this->respond(404, ['error' => 'User not found']);
            return;
        }

        $response = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // Fetch student details if user is a student
        if ($user['role'] === 'student') {
            $studentDetails = $this->studentDetailsModel->findByUserId($user['id']);
            if ($studentDetails) {
                $response['studentDetails'] = $studentDetails;
            }
        }

        $this->respond(200, $response);
    }

    public function getAllUsers() {
        $users = $this->userModel->getAll();
        $this->respond(200, ['users' => $users]);
    }

    private function respond($status, $data) {
        // Ensure no output has been sent before setting headers
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json');
        }
        echo json_encode($data);
        exit;
    }
}