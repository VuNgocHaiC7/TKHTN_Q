"""
Face Check API - Python Version
Chuyển đổi từ face_check.php
"""
import os
import tempfile
import subprocess
from flask import request
from _util import cfg, get_esp_ip, json_out, bad, http_get_bytes

def face_check():
    """Kiểm tra khuôn mặt"""
    config = cfg()
    
    # 1) Lấy ảnh đầu vào: ưu tiên file upload, nếu không thì chụp từ ESP32
    tmp_file = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
    tmp_path = tmp_file.name
    tmp_file.close()
    
    try:
        if 'image' in request.files:
            file = request.files['image']
            file.save(tmp_path)
        else:
            ip = get_esp_ip(request.args)
            ok = False
            for path in config['snapshot_paths']:
                url = f"http://{ip}{path}"
                img_bytes = http_get_bytes(url, 6)
                if img_bytes and img_bytes[:2] == b'\xFF\xD8':
                    with open(tmp_path, 'wb') as f:
                        f.write(img_bytes)
                    ok = True
                    break
            if not ok:
                return bad('Không chụp được ảnh từ ESP32')
        
        # 2) Gọi Python xử lý
        cmd = [
            config['python_bin'],
            os.path.join(config['tools_dir'], 'face_check.py'),
            '--image', tmp_path,
            '--db', config['faces_db_dir'],
            '--tolerance', str(config['tolerance'])
        ]
        
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=config['python_timeout'])
        
        if result.returncode != 0:
            return bad(f"Python error ({result.returncode}): {result.stderr}", 500)
        
        # 3) Python output JSON
        from flask import Response
        return Response(result.stdout, mimetype='application/json', headers={'Cache-Control': 'no-store'})
        
    finally:
        if os.path.exists(tmp_path):
            os.unlink(tmp_path)
