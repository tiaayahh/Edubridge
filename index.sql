CREATE DATABASE IF NOT EXISTS edubridge_platform;
USE edubridge_platform;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('youth', 'organization', 'admin') NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Youth profiles
CREATE TABLE IF NOT EXISTS youth_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    full_name VARCHAR(100),
    date_of_birth DATE,
    education_level VARCHAR(50),
    skills TEXT,
    interests TEXT,
    availability ENUM('full_time', 'part_time', 'flexible'),
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Organization profiles
CREATE TABLE IF NOT EXISTS organization_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    organization_name VARCHAR(100),
    industry VARCHAR(100),
    description TEXT,
    website VARCHAR(255),
    contact_email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Opportunities table
CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('internship', 'volunteering', 'training') NOT NULL,
    requirements TEXT,
    skills_required TEXT,
    duration VARCHAR(100),
    deadline DATE,
    is_approved BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    opportunity_id INT,
    cover_letter TEXT,
    status ENUM('pending', 'shortlisted', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE
);

-- Certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    opportunity_id INT,
    certificate_id VARCHAR(50) UNIQUE,
    completion_date DATE,
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, role, is_verified) VALUES 
('admin', 'admin@edubridge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);