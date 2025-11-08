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

CREATE TABLE access_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(64) NOT NULL,
  user_id INT NULL,
  result ENUM('ALLOW','DENY','ENROLLED','UNKNOWN') DEFAULT 'ALLOW',
  image_url VARCHAR(255) NULL,
  note VARCHAR(255) NULL,
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(device_id), INDEX(user_id), INDEX(ts),
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
