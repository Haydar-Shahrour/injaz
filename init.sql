CREATE DATABASE IF NOT EXISTS injaz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE injaz;

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(100) NOT NULL,
    section_name VARCHAR(100) NOT NULL,
    service_index INT NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    extra_details TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS uploaded_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user: username=admin, password=admin123
-- The password hash is generated using password_hash('admin123', PASSWORD_BCRYPT)
INSERT IGNORE INTO employees (username, password_hash) VALUES 
('admin', '$2y$10$YPuZrDzC6f/o0rdCIOsWB.Ibo5qQDHhAO0dBDB22vhSN4NxEFsNwq'),
('hydar', '$2y$10$F1a.vN1IvE.Z0Lm2uqKg0OqpYzgaC/hPM8ScIDymuLiwtQV0rOoqa'),
('ali', '$2y$10$Qk4PyHIRZg9iaZjKw2NKSebfX4m/qbsmI5BwY8gsRRftFomLAmvUW');
