-- Tạo database nếu chưa tồn tại
CREATE DATABASE IF NOT EXISTS ESP32KEY;

-- Chọn database để sử dụng
USE ESP32KEY;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) UNIQUE,
  role ENUM('admin','viewer') DEFAULT 'viewer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE devices (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  secret VARCHAR(128) NOT NULL,       -- token cho thiết bị (giữ bí mật)
  ip VARCHAR(64),
  is_active TINYINT(1) DEFAULT 1,
  last_seen TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng access_logs với cột source để phân biệt nguồn ghi log
-- esp32_auto: Log tự động từ ESP32
-- web_manual: Log thủ công từ Web
-- unknown: Log cũ hoặc không xác định
CREATE TABLE access_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(64) NOT NULL,
  user_id INT NULL,
  result ENUM('ALLOW','DENY','ENROLLED','UNKNOWN') DEFAULT 'ALLOW',
  recognized_name VARCHAR(120) NULL,
  confidence DECIMAL(5,2) NULL,
  status VARCHAR(20) NULL,
  image_url VARCHAR(255) NULL,
  photo_url VARCHAR(255) NULL,
  note VARCHAR(255) NULL,
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  source ENUM('esp32_auto','web_manual','unknown') DEFAULT 'unknown',
  INDEX(device_id), INDEX(user_id), INDEX(ts),
  INDEX(status), INDEX(recognized_name), INDEX(timestamp), INDEX(source),
  CONSTRAINT fk_log_device FOREIGN KEY (device_id) REFERENCES devices(id)
);

CREATE TABLE api_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(120),
  api_key VARCHAR(128) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_used_at TIMESTAMP NULL
);

/* Seed nhanh 1 thiết bị + 1 API key (thay giá trị khi triển khai thật) */
INSERT INTO devices (id, name, secret) VALUES
('DOOR-01', 'Cửa chính', 'DEV_SECRET_123');

INSERT INTO api_keys (label, api_key) VALUES
('AdminKey', 'ADMIN_API_KEY_123');

/* View để xem logs chi tiết dễ hơn */
CREATE OR REPLACE VIEW v_access_logs_detail AS
SELECT 
    al.id,
    al.device_id,
    d.name as device_name,
    al.recognized_name,
    al.confidence,
    al.status,
    al.result,
    al.photo_url,
    al.image_url,
    al.timestamp,
    al.ts,
    al.source,
    al.note
FROM access_logs al
LEFT JOIN devices d ON al.device_id = d.id
ORDER BY al.id DESC;
