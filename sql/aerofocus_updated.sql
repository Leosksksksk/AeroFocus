-- =========================================================
-- AeroFocus Database Schema (Updated)
-- Added: password_hash, role to users
--        sessions table
--        transactions (activity log) table
-- =========================================================

CREATE DATABASE IF NOT EXISTS aerofocus_db;
USE aerofocus_db;

-- ---------------------------------------------------------
-- USERS TABLE (Updated)
-- Added: password_hash, role, created_at, is_active
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    occupation_type VARCHAR(50) DEFAULT 'Working Student',
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SESSIONS TABLE
-- Tracks login/logout events per user.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    session_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address    VARCHAR(45),
    user_agent    VARCHAR(255),
    logged_in_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logged_out_at TIMESTAMP NULL DEFAULT NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TRANSACTIONS TABLE  (Activity / Audit Log)
-- Records every create / update / delete action a user
-- performs so the system has a full audit trail.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    action           ENUM('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','REGISTER') NOT NULL,
    entity_type      VARCHAR(50)  NOT NULL COMMENT 'e.g. assignment, subject, note',
    entity_id        INT NULL     COMMENT 'PK of the affected row (NULL for auth events)',
    description      VARCHAR(255) NOT NULL,
    performed_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SUBJECTS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
    subject_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    subject_name    VARCHAR(100) NOT NULL,
    instructor_name VARCHAR(100),
    units           VARCHAR(10) DEFAULT NULL,
    room            VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ASSIGNMENTS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    subject_id    INT NULL,
    title         VARCHAR(150) NOT NULL,
    description   TEXT,
    due_date      DATE NOT NULL,
    priority      ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    status        ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)    ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- NOTES TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS notes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(255) NOT NULL,
    tag        VARCHAR(50)  NOT NULL DEFAULT 'quick',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- ALTER SCRIPTS (run these if you already have the old DB)
-- =========================================================
-- ALTER TABLE users
--     ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email,
--     ADD COLUMN role ENUM('student','admin') NOT NULL DEFAULT 'student' AFTER occupation_type,
--     ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
--     ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active;

-- ALTER TABLE subjects
--     ADD COLUMN units VARCHAR(10) DEFAULT NULL AFTER instructor_name,
--     ADD COLUMN room  VARCHAR(50) DEFAULT NULL AFTER units;

-- ALTER TABLE notes
--     ADD COLUMN user_id INT NOT NULL DEFAULT 1 AFTER id,
--     ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
