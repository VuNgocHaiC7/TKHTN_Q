"""
Heartbeat API - Python Version
Chuyển đổi từ device/heartbeat.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..', 'src'))

from flask import request
from db import get_db
from auth import auth_device
from helpers import json_out, require_fields

def heartbeat():
    """Device heartbeat"""
    device_id = request.form.get('deviceId', '')
    token = request.form.get('token', '')
    
    require_fields(['deviceId', 'token'], request.form)
    auth_device(device_id, token)
    
    ip = request.form.get('ip', request.remote_addr)
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute('UPDATE devices SET ip=%s, last_seen=NOW() WHERE id=%s', (ip, device_id))
    conn.commit()
    cursor.close()
    
    return json_out({'ok': True, 'ip': ip, 'device': device_id})
