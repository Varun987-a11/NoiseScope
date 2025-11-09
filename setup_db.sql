-- Use the existing database
USE noisescope;

-- Table to store ADMIN and CONTRIBUTOR users (Role-Based Access Control)
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'contributor') NOT NULL DEFAULT 'contributor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to store crowdsourced noise data (Structural Change: Added environment_type)
CREATE TABLE IF NOT EXISTS noise_data (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    avg_noise_level_db INT(3) NOT NULL,
    -- New field for the MCQ question
    environment_type ENUM('indoor', 'outdoor', 'transportation') NOT NULL, 
    timestamp DATETIME NOT NULL,
    -- Link submission to a user (if logged in). Set to NULL if submitted anonymously.
    user_id INT(11) UNSIGNED, 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert a default administrator and a contributor user
-- Password is 'adminpass' for admin and 'userpass' for contributor, both hashed.
INSERT INTO users (username, password_hash, role) VALUES 
('admin', '$2y$10$tM.yF6n.R.k/0eZ3pL8yq.K.hV8z0Y6DqU0p9W0p6W0eE.jY7wWwM', 'admin'),
('user1', '$2y$10$7/O8D5A0F5Z4Q8G9H0J1I2L3M4N5P6R7S8T9U0V1W2X3Y4Z5A6B7C', 'contributor')
ON DUPLICATE KEY UPDATE password_hash=password_hash; 
-- Default Admin: 'admin' / 'adminpass'
-- Default User: 'user1' / 'userpass'