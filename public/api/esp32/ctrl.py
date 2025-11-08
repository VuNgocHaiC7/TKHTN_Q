"""
ESP32 Control API - Python Version
Chuyển đổi từ esp32/ctrl.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..', 'src'))

from flask import request
import requests
from helpers import json_out

def ctrl():
    """Điều khiển ESP32"""
    ip = request.args.get('ip', '')
    var = request.args.get('var', '')
    val = request.args.get('val', '')
    
    if not ip or not var or not val:
        return json_out({'ok': False, 'error': 'Missing ip/var/val'}, 422)
    
    url = f"http://{ip}/control?var={var}&val={val}"
    
    try:
        response = requests.get(url, timeout=5)
        
        if response.status_code < 200 or response.status_code >= 300:
            return json_out({'ok': False, 'error': f"ESP32 returns HTTP {response.status_code}"}, 502)
        
        return json_out({'ok': True, 'resp': response.text, 'url': url})
    except Exception as e:
        return json_out({'ok': False, 'error': str(e)}, 502)
