"""
Configuration file for Project_Q - Python version
"""
import os

# Database configuration
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'database': 'esp32lock',
    'user': 'root',
    'password': '',
    'charset': 'utf8mb4'
}

# Application configuration
APP_CONFIG = {
    # Base URL for web application
    'base_url': 'http://localhost/Project_Q/public',
    
    # Upload directory for images
    'upload_dir': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'public', 'uploads'),
    
    # Public URL for uploads
    'upload_base': '/Project_Q/public/uploads',
    
    # ESP32 default IP
    'esp32_ip': '192.168.0.107',
    
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
    
    # Python process timeout in seconds
    'python_timeout': 10
}

# Export combined config
CONFIG = {
    'db': DB_CONFIG,
    'app': APP_CONFIG
}
