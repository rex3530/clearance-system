CREATE DATABASE IF NOT EXISTS clearance2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clearance2_db;

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) UNIQUE,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  department VARCHAR(100),
  password_hash VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS offices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO offices (name) VALUES ('library'), ('bookstore'), ('sports');

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clearance_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  comments TEXT,
  overall_status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_clearance_requests_student FOREIGN KEY (student_id)
    REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS office_clearance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  office_id INT NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  comment TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_req_office (request_id, office_id),
  CONSTRAINT fk_office_clearance_request FOREIGN KEY (request_id)
    REFERENCES clearance_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_office_clearance_office FOREIGN KEY (office_id)
    REFERENCES offices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO admin_users (username, password_hash)
VALUES ('rex@gmail.com', '123456789');

-- Office users for role-based office login
CREATE TABLE IF NOT EXISTS office_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  office_id INT NOT NULL,
  username VARCHAR(100) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_office_users_office FOREIGN KEY (office_id)
    REFERENCES offices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed sample office users (plaintext passwords for quick test; replace with password_hash for production)
INSERT IGNORE INTO office_users (office_id, username, password_hash)
SELECT o.id, 'library_user', 'lib123' FROM offices o WHERE o.name = 'library' LIMIT 1;
INSERT IGNORE INTO office_users (office_id, username, password_hash)
SELECT o.id, 'bookstore_user', 'book123' FROM offices o WHERE o.name = 'bookstore' LIMIT 1;
INSERT IGNORE INTO office_users (office_id, username, password_hash)
SELECT o.id, 'sports_user', 'sport123' FROM offices o WHERE o.name = 'sports' LIMIT 1;
