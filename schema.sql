
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','guide') NOT NULL DEFAULT 'guide',
  display_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guide_id INT NOT NULL,
  group_name VARCHAR(150) NOT NULL,
  title VARCHAR(200) GENERATED ALWAYS AS (CONCAT(group_name)) STORED,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_users FOREIGN KEY (guide_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_guide_time (guide_id, start_datetime, end_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- seed admin (password: admin123) use your own in production
INSERT INTO users (username, password_hash, role, display_name)
VALUES ('admin', '$2y$10$HKk3Ry5cR7mGfB8g6QWkV.3b2C8u4iT3i4ibI3Vj0Qq0gT3ncmr9y', 'admin', 'Administrator')
ON DUPLICATE KEY UPDATE username=username;
