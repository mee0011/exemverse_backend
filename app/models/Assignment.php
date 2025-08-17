<?php
require_once __DIR__ . '/../../core/Model.php';

class Assignment extends Model {
    private function tableHasColumn(string $table, string $column): bool {
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetch();
    }

    public function create(string $title, string $description, string $courseCode, string $instructorEmail, string $dueDate, int $totalPoints, ?string $pdfPath = null): int|false {
        $hasCourseId = $this->tableHasColumn('assignments', 'course_id');
        $hasCourseCode = $this->tableHasColumn('assignments', 'course_code');
        $hasPdfPath = $this->tableHasColumn('assignments', 'pdf_path');

        $columns = ['title', 'description', 'instructor_email', 'due_date', 'total_points'];
        $placeholders = ['?', '?', '?', '?', '?'];
        $values = [$title, $description, $instructorEmail, $dueDate, $totalPoints];

        if ($hasCourseId) {
            $stmtCourse = $this->db->prepare('SELECT id FROM courses WHERE LOWER(TRIM(course_code)) = LOWER(TRIM(?))');
            $stmtCourse->execute([$courseCode]);
            $course = $stmtCourse->fetch();
            if (!$course) { return false; }
            $columns[] = 'course_id';
            $placeholders[] = '?';
            $values[] = $course['id'];
        } elseif ($hasCourseCode) {
            $columns[] = 'course_code';
            $placeholders[] = '?';
            $values[] = $courseCode;
        }

        if ($hasPdfPath && $pdfPath) {
            $columns[] = 'pdf_path';
            $placeholders[] = '?';
            $values[] = $pdfPath;
        }

        $sql = 'INSERT INTO assignments (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($values);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function update(int $id, string $title, string $description, string $courseCode, string $dueDate, int $totalPoints): bool {
        $hasCourseId = $this->tableHasColumn('assignments', 'course_id');
        $hasCourseCode = $this->tableHasColumn('assignments', 'course_code');
        $sets = ['title = ?', 'description = ?', 'due_date = ?', 'total_points = ?'];
        $values = [$title, $description, $dueDate, $totalPoints];

        if ($hasCourseId) {
            $stmtCourse = $this->db->prepare('SELECT id FROM courses WHERE LOWER(TRIM(course_code)) = LOWER(TRIM(?))');
            $stmtCourse->execute([$courseCode]);
            $course = $stmtCourse->fetch();
            if (!$course) { return false; }
            $sets[] = 'course_id = ?';
            $values[] = $course['id'];
        } elseif ($hasCourseCode) {
            $sets[] = 'course_code = ?';
            $values[] = $courseCode;
        }

        $values[] = $id;
        $sql = 'UPDATE assignments SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id, string $instructorEmail): bool {
        $stmt = $this->db->prepare('DELETE a FROM assignments a WHERE a.id = ? AND a.instructor_email = ?');
        $stmt->execute([$id, $instructorEmail]);
        return $stmt->rowCount() > 0;
    }

    public function getByInstructor(string $instructorEmail): array {
        $hasCourseId = $this->tableHasColumn('assignments', 'course_id');
        $hasCourseCode = $this->tableHasColumn('assignments', 'course_code');

        if ($hasCourseId) {
            $sql = "SELECT a.id, a.title, a.description, c.course_code, a.due_date, a.total_points, a.created_at,
                           (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) as submission_count
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.id
                    WHERE a.instructor_email = ?
                    ORDER BY a.created_at DESC";
        } elseif ($hasCourseCode) {
            $sql = "SELECT a.id, a.title, a.description, a.course_code, a.due_date, a.total_points, a.created_at,
                           (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) as submission_count
                    FROM assignments a
                    WHERE a.instructor_email = ?
                    ORDER BY a.created_at DESC";
        } else {
            $sql = "SELECT a.* FROM assignments a WHERE a.instructor_email = ? ORDER BY a.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instructorEmail]);
        return $stmt->fetchAll();
    }

    public function getRecentSubmissions(string $instructorEmail, int $limit = 10): array {
        $hasCourseId = $this->tableHasColumn('assignments', 'course_id');
        $assignJoinLeft = $hasCourseId ? 'a.course_id' : 'a.course_code';
        $courseJoinRight = $hasCourseId ? 'c.id' : 'c.course_code';
        $hasStudentEmail = $this->tableHasColumn('assignment_submissions', 'student_email');
        $studentJoin = $hasStudentEmail ? 'u.email = s.student_email' : 'u.id = s.student_id';

        $sql = "SELECT s.id, a.title AS assignment_title, c.course_code, u.name AS student_name, s.submitted_at
                FROM assignment_submissions s
                JOIN assignments a ON s.assignment_id = a.id
                JOIN courses c ON {$assignJoinLeft} = {$courseJoinRight}
                JOIN users u ON {$studentJoin}
                WHERE a.instructor_email = ?
                ORDER BY s.submitted_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $instructorEmail, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
