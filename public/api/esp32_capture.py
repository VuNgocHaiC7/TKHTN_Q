"""
ESP32 Capture API - Python Version
Chuyển đổi từ esp32_capture.php
"""
from flask import request
from _util import cfg, get_esp_ip, json_out, http_get_bytes

def esp32_capture():
    """Chụp ảnh từ ESP32"""
    config = cfg()
    ip = get_esp_ip(request.args)
    
    last_err = None
    for path in config['snapshot_paths']:
        url = f"http://{ip}{path}"
        img_bytes = http_get_bytes(url, 6)
        
        if img_bytes and img_bytes[:2] == b'\xFF\xD8':  # JPEG magic number
            from flask import Response
            return Response(img_bytes, mimetype='image/jpeg', headers={'Cache-Control': 'no-store'})
        else:
            last_err = f"Không lấy được snapshot tại {url}"
    
    return json_out({'ok': False, 'error': last_err or 'Không lấy được ảnh từ ESP32'}, 502)
