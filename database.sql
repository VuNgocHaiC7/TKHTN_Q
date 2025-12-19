-- ============================================================
-- PROJECT_Q - UNIFIED DATABASE SCHEMA & MIGRATION
-- ============================================================
-- File này có thể chạy nhiều lần an toàn:
--   - Lần đầu: Tạo mới database và tables
--   - Lần sau: Cập nhật schema và fix conflicts
-- ============================================================

-- Tạo database nếu chưa tồn tại
CREATE DATABASE IF NOT EXISTS ESP32KEY;

-- Chọn database để sử dụng
USE ESP32KEY;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) UNIQUE,
  role ENUM('admin','viewer') DEFAULT 'viewer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: devices
-- ============================================================
CREATE TABLE IF NOT EXISTS devices (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  secret VARCHAR(128) NOT NULL,       -- token cho thiết bị (giữ bí mật)
  ip VARCHAR(64),
  is_active TINYINT(1) DEFAULT 1,
  last_seen TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: access_logs
-- Bảng lưu lịch sử nhận diện với source để phân biệt:
--   - esp32_auto: ESP32 tự động gửi
--   - web_manual: Nhấn nút "Nhận diện" trên web
--   - lm393_auto: LM393 sensor tự động phát hiện
--   - unknown: Log cũ hoặc không xác định
-- ============================================================
CREATE TABLE IF NOT EXISTS access_logs (
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
  source ENUM('esp32_auto','web_manual','lm393_auto','unknown') DEFAULT 'unknown',
  INDEX(device_id), INDEX(user_id), INDEX(ts),
  INDEX(status), INDEX(recognized_name), INDEX(timestamp), INDEX(source)
);

-- ============================================================
-- TABLE: api_keys
-- ============================================================
CREATE TABLE IF NOT EXISTS api_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(120),
  api_key VARCHAR(128) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_used_at TIMESTAMP NULL
);

-- ============================================================
-- MIGRATION: Update existing tables (safe to run multiple times)
-- ============================================================

-- 1. Update access_logs source ENUM (nếu đã tồn tại nhưng thiếu lm393_auto)
ALTER TABLE access_logs 
MODIFY COLUMN source ENUM('esp32_auto','web_manual','lm393_auto','unknown') DEFAULT 'unknown';

-- 2. Update/recreate foreign key với ON DELETE CASCADE
ALTER TABLE access_logs DROP FOREIGN KEY IF EXISTS fk_log_device;
ALTER TABLE access_logs 
ADD CONSTRAINT fk_log_device 
FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE;

-- 3. Fix old data: Đổi device_id = '1' thành 'DOOR-01' (nếu tồn tại)
UPDATE access_logs SET device_id = 'DOOR-01' WHERE device_id = '1';

-- ============================================================
-- SEED DATA: Insert default device and API key
-- ============================================================
INSERT IGNORE INTO devices (id, name, secret) VALUES
('DOOR-01', 'Cửa chính', 'DEV_SECRET_123');

INSERT IGNORE INTO api_keys (label, api_key) VALUES
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
