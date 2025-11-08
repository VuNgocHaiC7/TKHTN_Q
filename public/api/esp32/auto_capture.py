"""
ESP32 Auto Capture API - Python Version
Chuyển đổi từ esp32/auto_capture.php
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..', 'src'))

from flask import request
import requests
import time
from datetime import datetime
from PIL import Image
import io
from helpers import json_out, ensure_upload_dir
from config.env import CONFIG

def fetch_jpeg_bin(url: str, timeout: int = 6):
    """Fetch JPEG binary"""
    try:
        response = requests.get(url, timeout=timeout, headers={'Accept': 'image/jpeg, */*'})
        if response.status_code >= 200 and response.status_code < 300:
            return response.content, None
        return None, f"HTTP {response.status_code}"
    except Exception as e:
        return None, str(e)

def diff_percent(jpg1: bytes, jpg2: bytes) -> float:
    """Tính % khác biệt giữa 2 ảnh"""
    try:
        im1 = Image.open(io.BytesIO(jpg1))
        im2 = Image.open(io.BytesIO(jpg2))
        
        # Resize về kích thước nhỏ để tính nhanh
        tw, th = 96, 72
        im1 = im1.resize((tw, th))
        im2 = im2.resize((tw, th))
        
        # Convert to grayscale
        im1 = im1.convert('L')
        im2 = im2.convert('L')
        
        # Tính diff
        pixels1 = list(im1.getdata())
        pixels2 = list(im2.getdata())
        
        total_diff = sum(abs(p1 - p2) for p1, p2 in zip(pixels1, pixels2))
        cnt = tw * th
        
        return (total_diff / (cnt * 255.0)) * 100.0
    except:
        return 100.0

def auto_capture():
    """Auto capture khi có chuyển động"""
    ip = request.args.get('ip', '')
    thr = float(request.args.get('thr', 7.5))
    delay = int(request.args.get('delay', 300))
    do_full = int(request.args.get('full', 1)) == 1
    
    if not ip:
        return json_out({'ok': False, 'error': 'Missing ip'}, 422)
    
    thr = max(0, min(100, thr))
    delay = max(0, delay)
    
    cap_url = f"http://{ip}/capture"
    
    try:
        # Capture A
        a, err_a = fetch_jpeg_bin(cap_url, 6)
        if a is None:
            return json_out({'ok': False, 'error': f"capture#1: {err_a}"}, 502)
        
        # Delay
        time.sleep(delay / 1000.0)
        
        # Capture B
        b, err_b = fetch_jpeg_bin(cap_url, 6)
        if b is None:
            return json_out({'ok': False, 'error': f"capture#2: {err_b}"}, 502)
        
        score = round(diff_percent(a, b), 2)
        captured = False
        url = None
        
        if score >= thr and do_full:
            bin_data, err_c = fetch_jpeg_bin(cap_url, 8)
            if bin_data is not None:
                up = ensure_upload_dir()
                day, day_path = up[0], up[1]
                timestamp = int(datetime.now().timestamp())
                filename = f"{timestamp}_{os.urandom(4).hex()}.jpg"
                full_path = os.path.join(day_path, filename)
                
                with open(full_path, 'wb') as f:
                    f.write(bin_data)
                
                base = CONFIG['app']['upload_base'].rstrip('/')
                url = f"{base}/{day}/{filename}"
                captured = True
        
        return json_out({
            'ok': True,
            'captured': captured,
            'score': score,
            'url': url,
            'thr': thr,
            'delay': delay
        })
    except Exception as e:
        return json_out({'ok': False, 'error': f'Server exception: {str(e)}'}, 500)
