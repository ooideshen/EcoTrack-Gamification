-- =========================================================
-- ECOTRACK DATABASE MASTER SCRIPT
-- Updated: Jan 05, 2026
-- Includes: All Roles, Logic Fixes, Quantity Column, & Badges
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 0. DROP TABLES (children -> parents)
DROP TABLE IF EXISTS user_activity_log;
DROP TABLE IF EXISTS submission_proof;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS weekly_challenges;
DROP TABLE IF EXISTS eco_activities;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS users;

-- 1. USERS TABLE
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin', 'organizer') DEFAULT 'student',
    intake_code VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. ECO ACTIVITIES (Master list of actions)
CREATE TABLE eco_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    points_awarded INT NOT NULL -- Base points per unit
);

-- 3. WEEKLY CHALLENGES
CREATE TABLE weekly_challenges (
    challenge_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    bonus_points INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL
);

-- 4. BADGES
CREATE TABLE badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_path VARCHAR(255),
    points_required INT NOT NULL
);

-- 5. SUBMISSION PROOF (Pending verification)
CREATE TABLE submission_proof (
    proof_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_id INT NOT NULL,
    challenge_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1, -- Added Quantity Column
    file_path VARCHAR(255) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (activity_id) REFERENCES eco_activities(activity_id),
    FOREIGN KEY (challenge_id) REFERENCES weekly_challenges(challenge_id)
);

-- 6. USER ACTIVITY LOG (Official history of points)
CREATE TABLE user_activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_id INT NOT NULL,
    challenge_id INT DEFAULT NULL,
    proof_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1, -- Added Quantity Column
    points_earned INT NOT NULL, -- (Base * Qty) + Bonus
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (activity_id) REFERENCES eco_activities(activity_id),
    FOREIGN KEY (proof_id) REFERENCES submission_proof(proof_id)
);

-- 7. USER BADGES (Who owns what)
CREATE TABLE user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id)
);

-- 8. ANNOUNCEMENTS
CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- DUMMY DATA INSERTION
-- ==========================================

-- 1. Insert Users
-- PASSWORD IS: "password" (all lowercase) for everyone
INSERT INTO users (name, email, password_hash, role, intake_code) VALUES
('De Shen (Student)', 'student@apu.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'UCDF2407ICT'),
('Pravin (Admin)', 'admin@apu.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'STAFF001'),
('Nathan (Organizer)', 'organizer@apu.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer', 'STAFF002');

-- 2. Insert Activities
INSERT INTO eco_activities (activity_name, description, points_awarded) VALUES
('Use Reusable Bottle', 'Drank water using a reusable bottle instead of plastic.', 10),
('Carpooling', 'Shared a ride to campus.', 20),
('Recycling', 'Separated waste into recycling bins.', 15),
('Meat-Free Meal', 'Ate a vegetarian lunch at the cafeteria.', 25);

-- 3. Insert Badges (Description Logic Fixed)
INSERT INTO badges (name, description, icon_path, points_required) VALUES
('Newbie', 'Earned your first 10 points.', 'assets/badges/newbie.png', 10),
('Recycler', 'Earn 100 Eco-Points to unlock this badge.', 'assets/badges/recycler.png', 100),
('Super Star', 'Reached 1000 points.', 'assets/badges/superStar.png', 1000);

-- 4. Insert Challenges
INSERT INTO weekly_challenges (title, description, bonus_points, start_date, end_date) VALUES
('Plastic Free Week', 'Avoid all single-use plastics for a week.', 50, '2026-01-01', '2026-01-07'),
('Walk to Class', 'Don\'t use the elevators for a day.', 30, '2026-01-08', '2026-01-14');

-- 5. Insert History (So Dashboard isn't empty)
-- We manually link the IDs here assuming auto-increment starts at 1
INSERT INTO submission_proof (user_id, activity_id, quantity, file_path, status, submitted_at) VALUES 
(1, 1, 1, 'uploads/dummy1.jpg', 'approved', NOW() - INTERVAL 2 DAY),
(1, 2, 1, 'uploads/dummy2.jpg', 'approved', NOW() - INTERVAL 1 DAY);

INSERT INTO user_activity_log (user_id, activity_id, proof_id, quantity, points_earned, logged_at) VALUES 
(1, 1, 1, 1, 10, NOW() - INTERVAL 2 DAY),
(1, 2, 2, 1, 20, NOW() - INTERVAL 1 DAY);