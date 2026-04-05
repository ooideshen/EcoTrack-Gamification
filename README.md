# EcoTrack Gamification Platform 🌍

### 🚀 Overview
A gamified web application built with PHP and MySQL, designed to encourage and track eco-friendly behaviors. The platform features a robust backend architecture engineered to handle concurrent user interactions and data consistency.

### 🛠️ Tech Stack
* **Backend:** PHP (Procedural & OOP concepts)
* **Database:** MySQL (InnoDB for transactional integrity)
* **Frontend:** HTML5, CSS3, JavaScript
* **Environment:** Apache / WAMP Server

### 🏗️ Key Engineering Features
* **Concurrency Collision Prevention:** Implemented a unique identifier algorithm combining `UserID` and `Unix Timestamps` to mathematically guarantee file uniqueness during concurrent media uploads, preventing race conditions and file overwriting.
* **Gamification Engine:** Engineered a relational database schema to track user points, dynamic leaderboard rankings, and achievement unlocks based on verified eco-actions.
* **Stateless Authentication:** Managed secure user sessions and role-based access control (RBAC) to differentiate between standard users and system administrators.

### 📂 Quick Start & Setup
1. Clone the repository to your local server directory (e.g., `htdocs` or `www`).
2. Import the included `config/ecotrack.sql` or `config/ecotrack_db.sql` blueprint into your MySQL instance to instantly generate the relational schema.
3. Ensure `dbConnect.php` is configured with your local database credentials (defaults to root / no password).
