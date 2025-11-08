"""
Access Log API - Python Version
Chuyển đổi từ access-log.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', 'src'))

from flask import request
from werkzeug.utils import secure_filename
from datetime import datetime
from db import get_db
from auth import auth_device
from helpers import json_out, require_fields, ensure_upload_dir
from config.env import CONFIG

def access_log():
    """Ghi log truy cập"""
    device_id = request.form.get('deviceId', '')
    token = request.form.get('token', '')
    result = request.form.get('result', 'ALLOW')  # ALLOW/DENY/ENROLLED/UNKNOWN
    user_id = request.form.get('userId')
    note = request.form.get('note')
    
    require_fields(['deviceId', 'token', 'result'], request.form)
    auth_device(device_id, token)
    
    image_url = None
    if 'image' in request.files:
        file = request.files['image']
        if file.filename:
            day, day_path = ensure_upload_dir()
            timestamp = int(datetime.now().timestamp())
            name = f"{timestamp}_{os.urandom(4).hex()}.jpg"
            dst = os.path.join(day_path, name)
            file.save(dst)
            image_url = f"{CONFIG['app']['upload_base']}/{day}/{name}"
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute(
        'INSERT INTO access_logs(device_id, user_id, result, image_url, note, ts) VALUES (%s, %s, %s, %s, %s, NOW())',
        (device_id, user_id, result, image_url, note)
    )
    conn.commit()
    cursor.close()
    
    return json_out({'ok': True, 'image': image_url})
