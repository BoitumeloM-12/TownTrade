-- ============================================================
-- TownTrade Database Schema
-- ITECA3-B12 | Zanele Boitumelo Matjie | EDUV4963658
-- 
-- HOW TO USE:
-- 1. Go to your InfinityFree cPanel → phpMyAdmin
-- 2. Select your database (towntrade_db)
-- 3. Click "Import" → choose this file → click "Go"
-- All tables will be created automatically.
-- ============================================================


-- ------------------------------------------------------------
-- TABLE 1: roles
-- Stores the three role types for RBAC:
-- 1 = Admin, 2 = Seller, 3 = Buyer
-- RBAC means each role sees different pages and has
-- different permissions on the site.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL
);

-- Insert the three roles
INSERT INTO roles (role_name) VALUES ('Admin'), ('Seller'), ('Buyer');


-- ------------------------------------------------------------
-- TABLE 2: users
-- Stores all registered users.
-- role_id links to the roles table (foreign key).
-- password is stored as a hashed string (never plain text).
-- status lets admin block/unblock users.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    phone      VARCHAR(20),
    password   VARCHAR(255) NOT NULL,
    role_id    INT NOT NULL DEFAULT 3,
    area       VARCHAR(100),
    bio        TEXT,
    status     ENUM('active', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Insert a default admin account
-- Password is: admin123 (hashed with PHP password_hash)
INSERT INTO users (full_name, email, phone, password, role_id, area, status)
VALUES (
    'Site Admin',
    'admin@towntrade.co.za',
    '0000000000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1,
    'Pretoria',
    'active'
);


-- ------------------------------------------------------------
-- TABLE 3: listings
-- Stores all products posted by sellers.
-- user_id links to the seller who posted it.
-- status lets admin approve or reject listings.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS listings (
    listing_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    category    VARCHAR(80),
    image_url   VARCHAR(255),
    status      ENUM('active', 'sold', 'pending', 'rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);


-- ------------------------------------------------------------
-- TABLE 4: orders
-- Records every purchase made on the platform.
-- buyer_id = the user who bought it.
-- listing_id = which product was bought.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT NOT NULL,
    buyer_id    INT NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    status      ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id),
    FOREIGN KEY (buyer_id)   REFERENCES users(user_id)
);


-- ------------------------------------------------------------
-- TABLE 5: messages
-- Allows buyers to contact sellers directly (C2C feature).
-- sender_id sends a message to receiver_id.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    message_id  INT AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT NOT NULL,
    receiver_id INT NOT NULL,
    listing_id  INT,
    content     TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id),
    FOREIGN KEY (listing_id)  REFERENCES listings(listing_id)
);
