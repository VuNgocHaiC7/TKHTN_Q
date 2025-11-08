"""
Logs API - Python Version
Chuyển đổi từ logs.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', 'src'))

from flask import request
from db import get_db
from auth import auth_api_key
from helpers import json_out

def logs():
    """Lấy danh sách logs"""
    auth_api_key(request)
    
    device_id = request.args.get('device_id')
    user_id = request.args.get('user_id')
    limit = min(max(int(request.args.get('limit', 50)), 1), 200)
    
    sql = '''SELECT l.id, l.ts, l.result, l.image_url, l.note,
                    l.device_id, d.name AS device_name, l.user_id
             FROM access_logs l
             JOIN devices d ON d.id = l.device_id'''
    
    conditions = []
    params = []
    
    if device_id:
        conditions.append('l.device_id = %s')
        params.append(device_id)
    if user_id:
        conditions.append('l.user_id = %s')
        params.append(user_id)
    
    if conditions:
        sql += ' WHERE ' + ' AND '.join(conditions)
    
    sql += f' ORDER BY l.id DESC LIMIT {limit}'
    
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(sql, params)
    data = cursor.fetchall()
    cursor.close()
    
    return json_out({'data': data})
