/* AZURITE RENTAL SYSTEM – FULL DATABASE SQL */

/* DEMO RESET ONLY: this file replaces all application tables. Back up first. */
SET FOREIGN_KEY_CHECKS=0;
/* Create or select the database */
CREATE DATABASE IF NOT EXISTS azurite_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE azurite_rental;

/* ---------------------------------------------------
   USERS TABLE (Admin, Host, Tenant)
--------------------------------------------------- */
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30),
  password_md5 VARCHAR(255) NOT NULL,
  role ENUM('admin','host','tenant') NOT NULL DEFAULT 'tenant',
  id_card_path VARCHAR(255),
  avatar_path VARCHAR(255),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

/* Default Accounts (MD5 encrypted) */
INSERT INTO users (full_name, username, email, phone, password_md5, role)
VALUES
('System Admin', 'admin', 'admin@azurite.com', '0700000000', MD5('admin123'), 'admin'),
('Default Tenant', 'user', 'user@azurite.com', '0711111111', MD5('123'), 'tenant'),
('Default Host', 'host', 'host@azurite.com', '0722222222', MD5('123'), 'host');

/* ---------------------------------------------------
   ROOM CATEGORIES
--------------------------------------------------- */
DROP TABLE IF EXISTS room_categories;
CREATE TABLE room_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  base_price_ksh DECIMAL(10,2) DEFAULT 0,
  image_path VARCHAR(255),
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

/* Sample Categories */
INSERT INTO room_categories (name, base_price_ksh) VALUES
('Standard Room', 2500),
('Deluxe Suite', 4500),
('Family Apartment', 6000);

/* ---------------------------------------------------
   ROOMS TABLE
--------------------------------------------------- */
DROP TABLE IF EXISTS rooms;
CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  owner_id INT NULL,
  name VARCHAR(150) NOT NULL,
  price_ksh DECIMAL(10,2) NOT NULL,
  cover_image VARCHAR(255),
  location_text VARCHAR(150),
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES room_categories(id),
  FOREIGN KEY (owner_id) REFERENCES users(id)
);

/* Sample Rooms (host owns them) */
INSERT INTO rooms (category_id, owner_id, name, price_ksh, location_text, description, is_active)
VALUES
(2, (SELECT id FROM users WHERE username='host'), 'Ocean View Apartment', 6500, 'Nyali, Mombasa', 'Sea-facing apartment with private balcony.', 1),
(1, (SELECT id FROM users WHERE username='host'), 'Deluxe Garden Suite', 4200, 'Bamburi, Mombasa', 'Fully furnished suite with kitchen.', 1);

/* ---------------------------------------------------
   ROOM PHOTOS
--------------------------------------------------- */
DROP TABLE IF EXISTS room_photos;
CREATE TABLE room_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

/* ---------------------------------------------------
   BOOKINGS TABLE
--------------------------------------------------- */
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref_code VARCHAR(40) NOT NULL,
  room_id INT NOT NULL,
  tenant_id INT NOT NULL,
  contact_phone VARCHAR(30) NOT NULL,
  checkin_date DATE NOT NULL,
  checkout_date DATE NOT NULL,
  guests INT NOT NULL DEFAULT 1,
  location_text VARCHAR(150),
  status ENUM('Pending','Approved','Declined','Confirmed','CheckedIn','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  total_amount_ksh DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id),
  FOREIGN KEY (tenant_id) REFERENCES users(id)
);

/* Sample Bookings */
INSERT INTO bookings (ref_code, room_id, tenant_id, contact_phone, checkin_date, checkout_date, guests, total_amount_ksh, status)
VALUES
('REF001', 1, (SELECT id FROM users WHERE username='user'), '0711111111', '2025-02-10', '2025-02-12', 2, 13000, 'Confirmed'),
('REF002', 2, (SELECT id FROM users WHERE username='user'), '0711111111', '2025-02-15', '2025-02-17', 1, 8400, 'Pending');

/* ---------------------------------------------------
   BOOKING EVENTS / LOGS
--------------------------------------------------- */
DROP TABLE IF EXISTS booking_events;
CREATE TABLE booking_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  actor_id INT NOT NULL,
  old_status VARCHAR(30),
  new_status VARCHAR(30),
  note VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id),
  FOREIGN KEY (actor_id) REFERENCES users(id)
);

/* ---------------------------------------------------
   BLOCKED DATES (HOST UNAVAILABLE DAYS)
--------------------------------------------------- */
DROP TABLE IF EXISTS room_block_dates;
CREATE TABLE room_block_dates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  block_date DATE NOT NULL,
  note VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS=1;
