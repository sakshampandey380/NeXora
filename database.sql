CREATE DATABASE ecommerce;
USE ecommerce;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  phone VARCHAR(15) UNIQUE,
  password VARCHAR(255),
  fav_category VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ADMINS
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  phone VARCHAR(15) UNIQUE,
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CATEGORIES
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50)
);

-- Insert default categories
INSERT INTO categories (name) VALUES ('Fruits');
INSERT INTO categories (name) VALUES ('Vegetables');
INSERT INTO categories (name) VALUES ('Cloths');
INSERT INTO categories (name) VALUES ('Sports');
INSERT INTO categories (name) VALUES ('Toys');

-- PRODUCTS
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  price DECIMAL(10,2),
  offer_price DECIMAL(10,2),
  description TEXT,
  image VARCHAR(255),
  category_id INT,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- CART
CREATE TABLE cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  product_id INT,
  qty INT DEFAULT 1
);

-- OTP
CREATE TABLE otp_verification (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(15),
  role VARCHAR(20) DEFAULT 'user',
  otp VARCHAR(6),
  expires_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
