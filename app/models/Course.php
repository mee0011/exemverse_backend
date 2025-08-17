<?php
require_once __DIR__ . '/../../core/Model.php';

class Course extends Model {
    public function create($courseName, $courseCode, $instructorEmail, $description, $coursePassword, $documentPath = null) {
        $sql = "INSERT INTO courses (course_name, course_code, instructor_email, description, course_password, document_path) 
                VALUES (:course_name, :course_code, :instructor_email, :description, :course_password, :document_path)";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            'course_name' => $courseName,
            'course_code' => $courseCode,
            'instructor_email' => $instructorEmail,
            'description' => $description,
            'course_password' => $coursePassword,
            'document_path' => $documentPath
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getAll() {
        $sql = "SELECT * FROM courses ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM courses WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByCourseCode($courseCode) {
        // Match course_code ignoring case and trimming spaces
        $sql = "SELECT * FROM courses WHERE LOWER(TRIM(course_code)) = LOWER(TRIM(:course_code)) ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_code' => $courseCode]);
        return $stmt->fetch();
    }

    public function getByInstructorEmail($instructorEmail) {
        $sql = "SELECT * FROM courses WHERE instructor_email = :instructor_email ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['instructor_email' => $instructorEmail]);
        return $stmt->fetchAll();
    }

    public function update($id, $courseName, $courseCode, $instructorEmail, $description, $coursePassword = null, $documentPath = null) {
        $sql = "UPDATE courses SET 
                course_name = :course_name, 
                course_code = :course_code, 
                instructor_email = :instructor_email, 
                description = :description";
        
        $params = [
            'id' => $id,
            'course_name' => $courseName,
            'course_code' => $courseCode,
            'instructor_email' => $instructorEmail,
            'description' => $description
        ];

        if ($coursePassword !== null) {
            $sql .= ", course_password = :course_password";
            $params['course_password'] = $coursePassword;
        }

        if ($documentPath !== null) {
            $sql .= ", document_path = :document_path";
            $params['document_path'] = $documentPath;
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $sql = "DELETE FROM courses WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getEnrollmentCount($courseId) {
        // Get course code first, then count enrollments by course_code
        $course = $this->getById($courseId);
        if (!$course) return 0;
        
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE course_code = :course_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_code' => $course['course_code']]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    public function validateCoursePassword($courseId, $password) {
        $sql = "SELECT course_password FROM courses WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch();
        
        if ($course && $course['course_password'] === $password) {
            return true;
        }
        return false;
    }

    public function getCourseCountByInstructor($instructorEmail) {
        $sql = "SELECT COUNT(*) as count FROM courses WHERE instructor_email = :instructor_email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['instructor_email' => $instructorEmail]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    public function getStudentCountByInstructor($instructorEmail) {
        // Sum total_students across instructor courses; fallback to counting enrollments by course_code
        $sql = "SELECT SUM(COALESCE(total_students, 0)) as count FROM courses WHERE instructor_email = :instructor_email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['instructor_email' => $instructorEmail]);
        $result = $stmt->fetch();
        $sum = (int)($result['count'] ?? 0);
        
        if ($sum > 0) return $sum;
        
        // Fallback: derive from enrollments
        $sql2 = "SELECT COUNT(DISTINCT e.student_email) as count
                 FROM enrollments e
                 JOIN courses c ON e.course_code = c.course_code
                 WHERE c.instructor_email = :instructor_email";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute(['instructor_email' => $instructorEmail]);
        $row = $stmt2->fetch();
        return (int)($row['count'] ?? 0);
    }

    public function getCoursesWithStats($instructorEmail) {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT e.student_email) as student_count,
                       (SELECT COUNT(*) FROM exams ex WHERE ex.course_code = c.course_code AND ex.instructor_email = ?) as exam_count,
                       0 as assignment_count
                FROM courses c 
                LEFT JOIN enrollments e ON c.course_code = e.course_code 
                WHERE c.instructor_email = ? 
                GROUP BY c.id 
                ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail, $instructorEmail]);
        return $stmt->fetchAll();
    }

    // Course materials methods
    public function getCourseMaterials($courseCode) {
        $sql = "SELECT * FROM course_materials WHERE course_code = :course_code ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['course_code' => $courseCode]);
        return $stmt->fetchAll();
    }

    public function addCourseMaterial($courseCode, $documentPath) {
        $sql = "INSERT INTO course_materials (course_code, document_path) VALUES (:course_code, :document_path)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'course_code' => $courseCode,
            'document_path' => $documentPath
        ]);
    }

    public function deleteCourseMaterial($materialId, $instructorEmail) {
        // First verify the instructor owns the course for this material
        $sql = "SELECT cm.*, c.instructor_email FROM course_materials cm 
                JOIN courses c ON cm.course_code = c.course_code 
                WHERE cm.id = :material_id AND c.instructor_email = :instructor_email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'material_id' => $materialId,
            'instructor_email' => $instructorEmail
        ]);
        
        $material = $stmt->fetch();
        if (!$material) {
            return false;
        }

        // Delete the material
        $sql = "DELETE FROM course_materials WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $materialId]);
    }

    // Maintain total_students on enroll / unenroll
    public function adjustTotalStudentsByCourseCode(string $courseCode, int $delta): bool {
        $sql = "UPDATE courses SET total_students = COALESCE(total_students, 0) + :delta WHERE LOWER(TRIM(course_code)) = LOWER(TRIM(:course_code))";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['delta' => $delta, 'course_code' => $courseCode]);
    }
}
