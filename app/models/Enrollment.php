<?php
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../models/Course.php';

class Enrollment extends Model {
    public function enroll($studentId, $courseId) {
        // Resolve course_code from course ID
        $courseModel = new Course();
        $course = $courseModel->getById($courseId);
        if (!$course) {
            return false;
        }
        $sql = "INSERT INTO enrollments (student_id, course_code) VALUES (:student_id, :course_code)";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            'student_id' => $studentId,
            'course_code' => $course['course_code']
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function enrollByEmail($studentEmail, $courseCode) {
        $sql = "INSERT INTO enrollments (student_email, course_code) VALUES (:student_email, :course_code)";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            'student_email' => $studentEmail,
            'course_code' => $courseCode
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function isEnrolled($studentId, $courseId) {
        // Resolve course_code from course ID
        $courseModel = new Course();
        $course = $courseModel->getById($courseId);
        if (!$course) {
            return false;
        }
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE student_id = :student_id AND course_code = :course_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'student_id' => $studentId,
            'course_code' => $course['course_code']
        ]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }

    public function isEnrolledByEmail($studentEmail, $courseCode) {
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE student_email = :student_email AND course_code = :course_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'student_email' => $studentEmail,
            'course_code' => $courseCode
        ]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function getEnrolledCourses($studentId) {
        $sql = "SELECT c.* FROM courses c 
                INNER JOIN enrollments e ON c.course_code = e.course_code 
                WHERE e.student_id = :student_id 
                ORDER BY e.enrolled_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    public function getEnrollmentCount($courseId) {
        // Prefer using Course::getEnrollmentCount, but provide compatibility
        $courseModel = new Course();
        $course = $courseModel->getById($courseId);
        if (!$course) {
            return 0;
        }
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE course_code = :course_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_code' => $course['course_code']]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    public function unenroll($studentId, $courseId) {
        // Resolve course_code from course ID
        $courseModel = new Course();
        $course = $courseModel->getById($courseId);
        if (!$course) {
            return false;
        }
        $sql = "DELETE FROM enrollments WHERE student_id = :student_id AND course_code = :course_code";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'student_id' => $studentId,
            'course_code' => $course['course_code']
        ]);
    }

    public function unenrollByEmail($studentEmail, $courseCode) {
        $sql = "DELETE FROM enrollments WHERE student_email = :student_email AND course_code = :course_code";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'student_email' => $studentEmail,
            'course_code' => $courseCode
        ]);
    }

    public function getEnrolledStudents($courseId) {
        // Resolve course_code from course ID
        $courseModel = new Course();
        $course = $courseModel->getById($courseId);
        if (!$course) {
            return [];
        }
        $sql = "SELECT u.* FROM users u 
                INNER JOIN enrollments e ON u.id = e.student_id 
                WHERE e.course_code = :course_code 
                ORDER BY e.enrolled_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_code' => $course['course_code']]);
        return $stmt->fetchAll();
    }

    public function getEnrolledStudentsByCourseCode($courseCode) {
        $sql = "SELECT u.*, e.enrolled_at FROM users u 
                INNER JOIN enrollments e ON u.email = e.student_email 
                WHERE e.course_code = :course_code 
                ORDER BY e.enrolled_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_code' => $courseCode]);
        return $stmt->fetchAll();
    }
     public function getByStudentEmail($studentEmail) {
        try {
            $sql = "SELECT * FROM enrollments WHERE student_email = :student_email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['student_email' => $studentEmail]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getByStudentEmail: ' . $e->getMessage());
            throw $e;
        }
    }
}
