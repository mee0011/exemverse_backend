<?php
require_once __DIR__ . '/../../core/Model.php';

class StudentDetails extends Model {
    public function findByUserId($userId) {
        $sql = "SELECT * FROM student_details WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    public function create($userId, $data) {
        $sql = "INSERT INTO student_details (user_id, student_id, department, email, phone, semester, cgpa, batch, blood_group, address, guardian, emergency_contact) 
                VALUES (:user_id, :student_id, :department, :email, :phone, :semester, :cgpa, :batch, :blood_group, :address, :guardian, :emergency_contact)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'student_id' => $data['student_id'] ?? '',
            'department' => $data['department'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'semester' => $data['semester'] ?? '',
            'cgpa' => $data['cgpa'] ?? null,
            'batch' => $data['batch'] ?? '',
            'blood_group' => $data['blood_group'] ?? '',
            'address' => $data['address'] ?? '',
            'guardian' => $data['guardian'] ?? '',
            'emergency_contact' => $data['emergency_contact'] ?? ''
        ]);
    }

    public function update($userId, $data) {
        $sql = "UPDATE student_details SET 
                student_id = :student_id,
                department = :department,
                email = :email, 
                phone = :phone, 
                semester = :semester, 
                cgpa = :cgpa, 
                batch = :batch, 
                blood_group = :blood_group, 
                address = :address, 
                guardian = :guardian, 
                emergency_contact = :emergency_contact 
                WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'student_id' => $data['student_id'] ?? '',
            'department' => $data['department'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'semester' => $data['semester'] ?? '',
            'cgpa' => $data['cgpa'] ?? null,
            'batch' => $data['batch'] ?? '',
            'blood_group' => $data['blood_group'] ?? '',
            'address' => $data['address'] ?? '',
            'guardian' => $data['guardian'] ?? '',
            'emergency_contact' => $data['emergency_contact'] ?? ''
        ]);
    }

    public function createOrUpdate($userId, $data) {
        $existing = $this->findByUserId($userId);
        if ($existing) {
            return $this->update($userId, $data);
        } else {
            return $this->create($userId, $data);
        }
    }
} 