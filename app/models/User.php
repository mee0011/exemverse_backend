<?php
require_once __DIR__ . '/../../core/Model.php';

class User extends Model {
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        return $this->findByEmail($email);
    }

    public function create($email, $password, $name, $role) {
        $sql = "INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'name' => $name,
            'role' => $role
        ]);
    }

    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    

    public function getAll() {
        $sql = "SELECT * FROM users";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    //create user details table
}