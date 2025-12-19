"""
Configuration file for Project_Q - Python version
"""
import os

# Database configuration
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'database': 'ESP32KEY',
    'user': 'root',
    'password': '',
    'charset': 'utf8mb4'
}

# Application configuration
APP_CONFIG = {
    # Base URL for web application (thay đổi theo môi trường của bạn)
    'base_url': 'http://10.87.241.224/Project_Q/public',
    
    # Upload directory for images
    'upload_dir': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'public', 'uploads'),
    
    # Public URL for uploads
    'upload_base': '/Project_Q/public/uploads',
    
    # ESP32 default IP
    'esp32_ip': '10.87.241.74',
    
    # ESP32 snapshot endpoints to try
    'snapshot_paths': ['/capture', '/jpg', '/capture?_cb=1'],
    
    # Python binary path
    'python_bin': r'C:\Users\Acer\AppData\Local\Programs\Python\Python311\python.exe',
    
    # Tools directory
    'tools_dir': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'public', 'tool'),
    
    # Face database directory
    'faces_db_dir': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'public', 'tool', 'faces_db'),
    
    # Face recognition tolerance (0.6 = standard, 0.55 = strict, 0.65-0.7 = relaxed)
    'tolerance': 0.6,
    
    # Python process timeout in seconds (20s for first run cache rebuild, then 2-3s)
    'python_timeout': 20,
    
    # Save photos for unlock attempts (True/False)
    'save_unlock_photos': True,
    
    # LM393 sensor settings
    'lm393_cooldown_ms': 5000,  # Cooldown time in milliseconds
    'lm393_enabled': True  # Enable/disable LM393 auto trigger
}

# Export combined config
CONFIG = {
    'db': DB_CONFIG,
    'app': APP_CONFIG
}
