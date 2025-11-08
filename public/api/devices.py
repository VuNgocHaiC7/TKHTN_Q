"""
Devices API - Python Version
Chuyển đổi từ devices.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', 'src'))

from flask import request
from db import get_db
from auth import auth_api_key
from helpers import json_out

def devices():
    """Lấy danh sách devices"""
    auth_api_key(request)
    
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute('SELECT id, name, ip, is_active, last_seen, created_at FROM devices ORDER BY id')
    data = cursor.fetchall()
    cursor.close()
    
    return json_out({'data': data})
