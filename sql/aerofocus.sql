-- =========================================================
-- AeroFocus Database Schema
-- System: AeroFocus (CRUD Web App for In-School Working Students)
-- Main Entity: Assignment
-- Author: Junrel Alipogpog | BSIT-3C
-- =========================================================

CREATE DATABASE IF NOT EXISTS aerofocus_db;
USE aerofocus_db;

-- ---------------------------------------------------------
-- USERS TABLE
-- Minimal table to satisfy the FK requirement shown in the
-- ERD (Assignments -> FK UserID). A single demo user is
-- seeded below since this build focuses on the Assignment
-- entity, per the midterm requirements.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    occupation_type VARCHAR(50) DEFAULT 'Working Student'
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SUBJECTS TABLE
-- Supports the FK SubjectID column on Assignments so an
-- assignment can be tied to a specific subject/class.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    instructor_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ASSIGNMENTS TABLE  (Main Entity)
-- Columns match the ERD: AssignmentID, UserID(FK),
-- SubjectID(FK), Title, Description, DueDate, Priority, Status
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    priority ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    status ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SEED DATA
-- ---------------------------------------------------------
INSERT INTO users (username, full_name, email, occupation_type) VALUES
('kfrancisco', 'Korben Francisco', 'korben.francisco@crmc.edu.ph', 'In-School Working Student');

INSERT INTO subjects (user_id, subject_name, instructor_name) VALUES
(1, 'Information Management 2', 'Prof. Dela Cruz'),
(1, 'Data Structures and Algorithms', 'Prof. Santos'),
(1, 'Web Systems and Technologies', 'Prof. Reyes');

INSERT INTO assignments (user_id, subject_id, title, description, due_date, priority, status) VALUES
(1, 1, 'ERD Documentation', 'Finalize the entity relationship diagram for AeroFocus.', '2026-09-05', 'High', 'In Progress'),
(1, 2, 'Sorting Algorithms Problem Set', 'Answer items 1-10 on merge sort and quick sort.', '2026-09-10', 'Medium', 'Pending'),
(1, 3, 'Portfolio Website Wireframe', 'Create low-fidelity wireframes for the personal portfolio site.', '2026-09-15', 'Low', 'Pending');
