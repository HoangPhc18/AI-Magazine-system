CREATE DATABASE IF NOT EXISTS AiMagazineDB;
USE AiMagazineDB;

-- Bảng quản lý người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng danh mục bài viết
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng lưu bài viết gốc từ nguồn tin
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    source VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Bảng lưu bài viết AI đã viết lại
CREATE TABLE rewritten_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT UNIQUE NOT NULL,
    rewritten_content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Bảng lưu lịch sử chỉnh sửa bài viết của admin
CREATE TABLE edit_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rewritten_article_id INT NOT NULL,
    edited_by INT NOT NULL,
    previous_content TEXT NOT NULL,
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rewritten_article_id) REFERENCES rewritten_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng lưu cài đặt AI
CREATE TABLE ai_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm người dùng
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@example.com', 'password', 'admin'),
('Editor', 'editor@example.com', 'password', 'editor'),
('User', 'user@example.com', 'password', 'user');

-- Thêm danh mục bài viết
INSERT INTO categories (name) VALUES
('Công Nghệ'),
('Kinh Tế');

-- Thêm bài viết gốc
INSERT INTO articles (title, url, source, content, category_id) VALUES
('AI đang thay đổi thế giới', 'https://news.com/ai', 'News Site', 'AI đang tạo ra cách mạng công nghệ', 1),
('Thị trường chứng khoán tăng trưởng mạnh', 'https://news.com/stock', 'Finance Blog', 'Thị trường chứng khoán đang tăng mạnh', 2);

-- Thêm bài viết AI viết lại
INSERT INTO rewritten_articles (article_id, rewritten_content, status, reviewed_by) VALUES
(1, 'Trí tuệ nhân tạo đang dẫn đầu cuộc cách mạng số.', 'approved', 1);

-- Thêm lịch sử chỉnh sửa
INSERT INTO edit_history (rewritten_article_id, edited_by, previous_content) VALUES
(1, 1, 'AI đang cách mạng hóa ngành công nghiệp.');

-- Thêm cài đặt AI
INSERT INTO ai_settings (setting_key, setting_value) VALUES
('rewrite_model', 'Gemma 2'),
('max_length', '100'); 