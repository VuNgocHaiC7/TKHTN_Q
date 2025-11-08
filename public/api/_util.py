"""
Project_Q API Utilities - Python Version
Chuyển đổi từ _util.php và _config.php
"""
import os
import json
import requests
from typing import Optional, Dict, Any, Tuple
from flask import jsonify, Response

# Cấu hình cơ bản (tương đương _config.php)
CONFIG = {
    'esp32_ip': '192.168.0.107',
    'snapshot_paths': ['/capture', '/jpg', '/capture?_cb=1'],
    'python_bin': r'C:\Users\Acer\AppData\Local\Programs\Python\Python311\python.exe',
    'tools_dir': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'tool'),
    'faces_db_dir': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'tool', 'faces_db'),
    'tolerance': 0.6,
    'python_timeout': 10
}

def cfg() -> Dict[str, Any]:
    """Lấy config"""
    return CONFIG

def get_esp_ip(request_args: Dict) -> str:
    """Lấy ESP32 IP từ request hoặc config"""
    ip = request_args.get('ip', CONFIG['esp32_ip'])
    return ip.strip()

def http_get_bytes(url: str, timeout: int = 5) -> Optional[bytes]:
    """HTTP GET trả về bytes"""
    try:
        response = requests.get(url, timeout=timeout)
        if response.status_code == 200:
            return response.content
        return None
    except Exception:
        return None

def json_out(data: Dict[str, Any], code: int = 200) -> Tuple[Response, int]:
    """Output JSON với HTTP status code"""
    return jsonify(data), code

def bad(msg: str, code: int = 400) -> Tuple[Response, int]:
    """Trả về lỗi JSON"""
    return json_out({'ok': False, 'error': msg}, code)
