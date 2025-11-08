"""
ESP32 Capture API - Python Version
Chuyển đổi từ esp32/capture.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..', 'src'))

from flask import request
import requests
from datetime import datetime
from helpers import json_out, ensure_upload_dir
from config.env import CONFIG

def capture():
    """Capture từ ESP32 và lưu file"""
    ip = request.args.get('ip', '')
    if not ip:
        return json_out({'ok': False, 'error': 'Missing ip'}, 422)
    
    try:
        url = f"http://{ip}/capture"
        response = requests.get(url, timeout=10)
        
        if response.status_code < 200 or response.status_code >= 300:
            return json_out({'ok': False, 'error': f"ESP32 returns HTTP {response.status_code}"}, 502)
        
        img_bytes = response.content
        
        day, day_path = ensure_upload_dir()
        timestamp = int(datetime.now().timestamp())
        filename = f"{timestamp}_{os.urandom(4).hex()}.jpg"
        
        with open(os.path.join(day_path, filename), 'wb') as f:
            f.write(img_bytes)
        
        base = CONFIG['app']['upload_base'].rstrip('/')
        public_url = f"{base}/{day}/{filename}"
        
        return json_out({'ok': True, 'url': public_url})
    except Exception as e:
        return json_out({'ok': False, 'error': f'Exception: {str(e)}'}, 500)
