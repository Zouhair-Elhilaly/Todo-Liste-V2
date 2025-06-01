CREATE DATABASE IF NOT EXISTS todolist;
USE todolist;

-- Table des administrateurs
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Table des utilisateurs
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des modules
CREATE TABLE module (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
);

-- Table des notes
CREATE TABLE note (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    module_id INT NOT NULL,
    FOREIGN KEY (module_id) REFERENCES module(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
);