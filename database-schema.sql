

-- Update existing courses table structure
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- If the table doesn't exist yet, create it
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    instructor_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    instructor_email VARCHAR(255) NOT NULL,
    course_password VARCHAR(255) NOT NULL,
    document_path VARCHAR(500),
    total_students INT DEFAULT 0
);
CREATE TABLE IF NOT EXISTS course_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(50) NOT NULL,
    document_path VARCHAR(500) NOT NULL
);

-- Enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_code VARCHAR(50) NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    student_email VARCHAR(255) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (course_code) REFERENCES courses(course_code)   
);

-- Exams table


CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    duration_minutes INT NOT NULL,
    instructions TEXT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    instructor_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE
);


-- Questions table

CREATE TABLE exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_type ENUM('mcq', 'short_answer') NOT NULL,
    question_text TEXT NOT NULL,
    options JSON NULL, -- For MCQs: store as JSON ["Option A","Option B","Option C","Option D"]
    correct_option VARCHAR(255) NULL, -- Correct MCQ option
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);


-- Question options table (for multiple choice)
CREATE TABLE IF NOT EXISTS question_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT,
    option_text VARCHAR(255) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- Exam submissions table


CREATE TABLE exam_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT,
    student_email VARCHAR(255) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NULL, -- Optional PDF answer sheet
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    question_id INT NOT NULL,
    student_answer TEXT NOT NULL,
    correct_answer TEXT NULL,
    is_correct BOOLEAN NULL,
    FOREIGN KEY (submission_id) REFERENCES exam_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
);

-- Submission answers table


-- Student Details table
CREATE TABLE IF NOT EXISTS student_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    department VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    semester VARCHAR(10),
    cgpa DECIMAL(3,2),
    batch VARCHAR(10),
    blood_group VARCHAR(5),
    address VARCHAR(255),
    guardian VARCHAR(100),
    emergency_contact VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Student Grades table
CREATE TABLE IF NOT EXISTS student_grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_name VARCHAR(255) NOT NULL,
    student_email VARCHAR(255) NULL,
    course_code VARCHAR(50) NOT NULL,
    quiz_score DECIMAL(5,2) NULL,
    midterm_score DECIMAL(5,2) NULL,
    final_score DECIMAL(5,2) NULL,
    others_score DECIMAL(5,2) NULL,
    cgpa DECIMAL(3,2) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    course_code VARCHAR(50) NOT NULL,
    instructor_email VARCHAR(255) NOT NULL,
    pdf_path VARCHAR(255),
    due_date DATE,
    total_points INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (instructor_email) REFERENCES users(email)
);


-- Assignment submissions table
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_email VARCHAR(255) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    instructor_email VARCHAR(255) NOT NULL,
    file_path VARCHAR(255),
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    feedback TEXT,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (student_email) REFERENCES users(email),
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (instructor_email) REFERENCES users(email)
);

