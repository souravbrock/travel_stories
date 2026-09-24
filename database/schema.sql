-- Travel Stories schema (MySQL 5.7+/8, utf8mb4)
CREATE DATABASE IF NOT EXISTS `reddevil_tstory` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reddevil_tstory`;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL,
  password_hash VARCHAR(255) NULL,
  role ENUM('customer','vendor','admin') NOT NULL DEFAULT 'customer',
  vendor_type ENUM('travel_agent','hotel','homestay','transport','ticketing','other') NULL,
  company_name VARCHAR(160) NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (role), INDEX (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  code CHAR(5) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  consumed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (email), INDEX (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auth_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (token)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS states (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(100) NOT NULL UNIQUE,
  best_time VARCHAR(120) NULL,
  peak_note VARCHAR(255) NULL,
  highlights TEXT NULL,
  color VARCHAR(10) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS districts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  state_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_district (state_id, name),
  FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tourist_spots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  district_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  lat DECIMAL(10,7) NULL,
  lng DECIMAL(10,7) NULL,
  best_time VARCHAR(120) NULL,
  image_url VARCHAR(255) NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
  INDEX (district_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  spot_id INT NOT NULL,
  owner_id INT NULL,
  kind ENUM('hotel','homestay','resort') NOT NULL DEFAULT 'hotel',
  name VARCHAR(160) NOT NULL,
  tier ENUM('budget','deluxe','luxury') NOT NULL DEFAULT 'deluxe',
  price_per_night DECIMAL(10,2) NOT NULL DEFAULT 0,
  rating DECIMAL(2,1) NULL,
  amenities TEXT NULL,
  photos TEXT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (spot_id) REFERENCES tourist_spots(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX (spot_id), INDEX (tier)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NULL,
  category ENUM('suv_muv','group_luxury') NOT NULL DEFAULT 'suv_muv',
  name VARCHAR(120) NOT NULL,
  seats INT NULL,
  price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0,
  photo_url VARCHAR(255) NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agent_id INT NULL,
  title VARCHAR(180) NOT NULL,
  state_id INT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  duration_days INT NOT NULL DEFAULT 3,
  itinerary TEXT NULL,
  photos TEXT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS custom_trips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  origin VARCHAR(160) NOT NULL,
  transit_mode ENUM('flight','train','self_drive') NOT NULL,
  booking_pref ENUM('self','platform') NOT NULL DEFAULT 'self',
  vehicle_id INT NULL,
  payload JSON NULL,
  total_estimate DECIMAL(10,2) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
