"""
Main Flask application for Project_Q - Python Backend
"""
from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import os
import sys
import time
import subprocess
import tempfile
import requests
from PIL import Image
import io
import hashlib
import binascii

# Add parent directory to path
sys.path.insert(0, os.path.dirname(__file__))
from src.db import execute_query, Database
from src.helpers import (json_response, error_response, success_response,
                         require_fields, ensure_upload_dir, validate_ip)
from src.auth import require_api_key, require_device_auth
from config.env import APP_CONFIG

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# ==================== UTILITY FUNCTIONS ====================

def get_esp_ip():
    """Get ESP32 IP from request or config"""
    ip = request.args.get('ip', request.json.get('ip') if request.is_json else None)
    if not ip:
        ip = APP_CONFIG['esp32_ip']
    return ip.strip()

def fetch_esp32_image(ip, timeout=6):
    """Fetch image from ESP32 camera"""
    for path in APP_CONFIG['snapshot_paths']:
        url = f"http://{ip}{path}"
        try:
            response = requests.get(url, timeout=timeout)
            if response.status_code == 200 and response.content.startswith(b'\xff\xd8'):
                return response.content
        except:
            continue
    return None

def calculate_image_diff(img1_bytes, img2_bytes):
    """
    Calculate percentage difference between two images
    Returns percentage (0-100)
    """
    try:
        # Open images
        img1 = Image.open(io.BytesIO(img1_bytes))
        img2 = Image.open(io.BytesIO(img2_bytes))
        
        # Resize to standard size for comparison
        size = (96, 72)
        img1 = img1.resize(size).convert('L')  # Grayscale
        img2 = img2.resize(size).convert('L')
        
        # Calculate pixel differences
        pixels1 = list(img1.getdata())
        pixels2 = list(img2.getdata())
        
        total_diff = sum(abs(p1 - p2) for p1, p2 in zip(pixels1, pixels2))
        max_diff = len(pixels1) * 255
        
        return (total_diff / max_diff) * 100
    except Exception as e:
        print(f"Image diff error: {e}")
        return 100.0

# ==================== API ROUTES ====================

@app.route('/api/devices', methods=['GET'])
@require_api_key
def get_devices():
    """Get all devices"""
    devices = execute_query(
        'SELECT id, name, ip, is_active, last_seen, created_at FROM devices ORDER BY id',
        fetch_all=True
    )
    return json_response({'data': devices})

@app.route('/api/device/heartbeat', methods=['POST'])
def device_heartbeat():
    """Device heartbeat endpoint"""
    data = request.get_json() or request.form.to_dict()
    
    device_id = data.get('deviceId')
    token = data.get('token')
    
    if not device_id or not token:
        return error_response('Missing deviceId or token', 422)
    
    # Verify device
    device = execute_query(
        'SELECT * FROM devices WHERE id = %s AND secret = %s AND is_active = 1',
        (device_id, token),
        fetch_one=True
    )
    
    if not device:
        return error_response('Unauthorized device', 401)
    
    # Update last seen and IP
    ip = data.get('ip', request.remote_addr)
    execute_query(
        'UPDATE devices SET ip = %s, last_seen = NOW() WHERE id = %s',
        (ip, device_id)
    )
    
    return success_response({'ip': ip, 'device': device_id})

@app.route('/api/esp32/capture', methods=['GET'])
def esp32_capture():
    """Capture image from ESP32 and save it"""
    try:
        ip = get_esp_ip()
        
        # Fetch image from ESP32
        img_bytes = fetch_esp32_image(ip, timeout=10)
        if not img_bytes:
            return error_response('Cannot capture image from ESP32', 502)
        
        # Save image
        day, day_path = ensure_upload_dir()
        timestamp = int(time.time())
        random_hex = binascii.hexlify(os.urandom(4)).decode()
        filename = f"{timestamp}_{random_hex}.jpg"
        
        filepath = os.path.join(day_path, filename)
        with open(filepath, 'wb') as f:
            f.write(img_bytes)
        
        # Generate public URL
        base = APP_CONFIG['upload_base'].rstrip('/')
        public_url = f"{base}/{day}/{filename}"
        
        return success_response({'url': public_url})
        
    except Exception as e:
        return error_response(f'Exception: {str(e)}', 500)

@app.route('/api/esp32/ctrl', methods=['GET'])
def esp32_control():
    """Control ESP32 camera settings"""
    ip = request.args.get('ip')
    var = request.args.get('var')
    val = request.args.get('val')
    
    if not ip or not var or val is None:
        return error_response('Missing ip, var, or val', 422)
    
    try:
        url = f"http://{ip}/control?var={var}&val={val}"
        response = requests.get(url, timeout=5)
        
        if response.status_code < 200 or response.status_code >= 300:
            return error_response(f'ESP32 returns HTTP {response.status_code}', 502)
        
        return success_response({'resp': response.text, 'url': url})
        
    except Exception as e:
        return error_response(str(e), 502)

@app.route('/api/esp32/auto-capture', methods=['GET'])
@app.route('/api/esp32/auto_capture', methods=['GET'])
def esp32_auto_capture():
    """
    Auto capture when motion detected
    Parameters:
        - ip: ESP32 IP
        - thr: Threshold percentage (0-100)
        - delay: Delay between captures in ms
        - full: Whether to save full image (0 or 1)
    """
    try:
        ip = get_esp_ip()
        thr = float(request.args.get('thr', 7.5))
        delay = int(request.args.get('delay', 300))
        do_full = int(request.args.get('full', 1)) == 1
        
        # Validate params
        thr = max(0, min(100, thr))
        delay = max(0, delay)
        
        # Capture first image
        img1 = fetch_esp32_image(ip)
        if not img1:
            return error_response('Cannot capture first image', 502)
        
        # Wait
        time.sleep(delay / 1000.0)
        
        # Capture second image
        img2 = fetch_esp32_image(ip)
        if not img2:
            return error_response('Cannot capture second image', 502)
        
        # Calculate difference
        score = round(calculate_image_diff(img1, img2), 2)
        captured = False
        url = None
        
        # If difference exceeds threshold, save image
        if score >= thr and do_full:
            img3 = fetch_esp32_image(ip, timeout=8)
            if img3:
                day, day_path = ensure_upload_dir()
                timestamp = int(time.time())
                random_hex = binascii.hexlify(os.urandom(4)).decode()
                filename = f"{timestamp}_{random_hex}.jpg"
                
                filepath = os.path.join(day_path, filename)
                with open(filepath, 'wb') as f:
                    f.write(img3)
                
                base = APP_CONFIG['upload_base'].rstrip('/')
                url = f"{base}/{day}/{filename}"
                captured = True
        
        return success_response({
            'captured': captured,
            'score': score,
            'url': url,
            'thr': thr,
            'delay': delay
        })
        
    except Exception as e:
        return error_response(f'Server exception: {str(e)}', 500)

@app.route('/api/face-check', methods=['GET', 'POST'])
@app.route('/api/face_check', methods=['GET', 'POST'])
def face_check():
    """
    Face detection and recognition
    Accepts either uploaded image or fetches from ESP32
    """
    try:
        # Get image data
        tmp_file = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
        
        if request.files and 'image' in request.files:
            # From file upload
            file = request.files['image']
            file.save(tmp_file.name)
        else:
            # From ESP32
            ip = get_esp_ip()
            img_bytes = fetch_esp32_image(ip, timeout=6)
            if not img_bytes:
                os.unlink(tmp_file.name)
                return error_response('Cannot capture image from ESP32', 502)
            
            tmp_file.write(img_bytes)
            tmp_file.flush()
        
        tmp_file.close()
        
        # Build Python command
        python_bin = APP_CONFIG['python_bin']
        script_path = os.path.join(APP_CONFIG['tools_dir'], 'face_check.py')
        db_path = APP_CONFIG['faces_db_dir']
        tolerance = APP_CONFIG['tolerance']
        
        cmd = [
            python_bin,
            script_path,
            '--image', tmp_file.name,
            '--db', db_path,
            '--tolerance', str(tolerance)
        ]
        
        # Execute Python script
        print(f"[DEBUG] Running command: {' '.join(cmd)}")
        
        try:
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=APP_CONFIG['python_timeout']
            )
            
            print(f"[DEBUG] Return code: {result.returncode}")
            print(f"[DEBUG] Stdout length: {len(result.stdout)}")
            print(f"[DEBUG] Stderr: {result.stderr[:200] if result.stderr else 'None'}")
            
        except subprocess.TimeoutExpired as e:
            os.unlink(tmp_file.name)
            return error_response(f'Python script timeout after {APP_CONFIG["python_timeout"]}s', 500)
        except Exception as e:
            os.unlink(tmp_file.name)
            return error_response(f'Subprocess error: {str(e)}', 500)
        
        # Clean up temp file
        os.unlink(tmp_file.name)
        
        if result.returncode != 0:
            return error_response(f'Python error ({result.returncode}): {result.stderr}', 500)
        
        # Return Python output as JSON
        return app.response_class(
            response=result.stdout,
            status=200,
            mimetype='application/json'
        )
        
    except Exception as e:
        return error_response(f'Exception: {str(e)}', 500)

@app.route('/api/face-detect-fast', methods=['GET'])
@app.route('/api/face_detect_fast', methods=['GET'])
def face_detect_fast():
    """
    Fast face detection using Haar Cascade (no recognition)
    For real-time tracking
    """
    try:
        ip = get_esp_ip()
        
        # Capture image
        img_bytes = fetch_esp32_image(ip, timeout=6)
        if not img_bytes:
            return error_response('Cannot capture image from ESP32', 502)
        
        # Save to temp file
        tmp_file = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
        tmp_file.write(img_bytes)
        tmp_file.close()
        
        # Run fast detection script
        python_bin = APP_CONFIG['python_bin']
        script_path = os.path.join(APP_CONFIG['tools_dir'], 'face_detect_only.py')
        
        cmd = [python_bin, script_path, '--image', tmp_file.name]
        
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=5
        )
        
        os.unlink(tmp_file.name)
        
        if result.returncode != 0:
            return error_response(f'Detection error: {result.stderr}', 500)
        
        return app.response_class(
            response=result.stdout,
            status=200,
            mimetype='application/json'
        )
        
    except Exception as e:
        return error_response(f'Exception: {str(e)}', 500)

@app.route('/api/esp32-capture', methods=['GET'])
@app.route('/api/esp32_capture', methods=['GET'])
def esp32_capture_raw():
    """Return raw ESP32 camera image"""
    try:
        ip = get_esp_ip()
        img_bytes = fetch_esp32_image(ip, timeout=6)
        
        if not img_bytes:
            return error_response('Cannot capture image', 502)
        
        return send_file(
            io.BytesIO(img_bytes),
            mimetype='image/jpeg'
        )
        
    except Exception as e:
        return error_response(str(e), 500)

@app.route('/api/draw-overlay', methods=['POST'])
@app.route('/api/draw_overlay', methods=['POST'])
def draw_overlay():
    """
    Draw detection boxes on image
    Expects 'boxes' parameter with JSON data and image in body
    """
    try:
        import json
        from PIL import ImageDraw, ImageFont
        
        # Get boxes data from query param
        boxes_json = request.args.get('boxes')
        if not boxes_json:
            return error_response('Missing boxes parameter', 400)
        
        boxes_data = json.loads(boxes_json)
        faces = boxes_data.get('faces', [])
        
        # Get image from request body
        img_bytes = request.get_data()
        img = Image.open(io.BytesIO(img_bytes))
        draw = ImageDraw.Draw(img)
        
        # Draw boxes and labels
        for face in faces:
            box = face.get('box', [])
            if len(box) != 4:
                continue
            
            x1, y1, x2, y2 = box
            matched = face.get('matched', False)
            name = face.get('name', 'unknown')
            confidence = face.get('confidence', 0)
            
            # Choose color
            color = '#10b981' if matched else '#ef4444'
            
            # Draw rectangle
            draw.rectangle([x1, y1, x2, y2], outline=color, width=3)
            
            # Draw label
            label = f"{'✓' if matched else '✗'} {name}"
            if matched:
                label += f" ({confidence}%)"
            
            # Draw text background
            text_bbox = draw.textbbox((x1, y1 - 25), label)
            draw.rectangle(text_bbox, fill=color)
            draw.text((x1 + 5, y1 - 20), label, fill='white')
        
        # Return modified image
        output = io.BytesIO()
        img.save(output, format='JPEG')
        output.seek(0)
        
        return send_file(output, mimetype='image/jpeg')
        
    except Exception as e:
        return error_response(f'Draw error: {str(e)}', 500)

@app.route('/api/logs', methods=['GET'])
def get_logs():
    """Get access logs - Public endpoint for frontend polling"""
    limit = int(request.args.get('limit', 100))
    
    logs = execute_query(
        'SELECT * FROM access_logs ORDER BY timestamp DESC LIMIT %s',
        (limit,),
        fetch_all=True
    )
    
    return json_response({'ok': True, 'data': logs})

@app.route('/api/logs', methods=['POST'])
def create_log():
    """Create log entry from ESP32 - Public endpoint"""
    data = request.get_json() or request.form.to_dict()
    
    status = data.get('status', 'unknown')
    recognized_name = data.get('recognized_name', 'Unknown')
    confidence = float(data.get('confidence', 0))
    source = data.get('source', 'esp32_auto')
    device_id = data.get('device_id', 1)
    
    try:
        execute_query(
            '''INSERT INTO access_logs 
               (device_id, status, recognized_name, confidence, source, timestamp) 
               VALUES (%s, %s, %s, %s, %s, NOW())''',
            (device_id, status, recognized_name, confidence, source)
        )
        
        return success_response({'message': 'Log saved', 'status': status})
    except Exception as e:
        return error_response(f'Failed to save log: {str(e)}', 500)

@app.route('/api/sensor/config', methods=['GET'])
@require_api_key
def get_sensor_config():
    """Get LM393 sensor configuration"""
    config = {
        'lm393_enabled': APP_CONFIG.get('lm393_enabled', True),
        'lm393_cooldown_ms': APP_CONFIG.get('lm393_cooldown_ms', 5000),
        'save_unlock_photos': APP_CONFIG.get('save_unlock_photos', True),
        'tolerance': APP_CONFIG.get('tolerance', 0.6)
    }
    
    return json_response({'config': config})

@app.route('/api/sensor/stats', methods=['GET'])
@require_api_key
def get_sensor_stats():
    """Get sensor statistics from database"""
    # Statistics for last 24 hours
    stats = execute_query('''
        SELECT 
            COUNT(*) as total_detections,
            SUM(CASE WHEN status = 'granted' THEN 1 ELSE 0 END) as granted,
            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied,
            AVG(confidence) as avg_confidence
        FROM access_logs 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ''', fetch_one=True)
    
    # Recent detections
    recent = execute_query('''
        SELECT id, device_id, recognized_name, confidence, status, photo_url, timestamp
        FROM access_logs 
        ORDER BY timestamp DESC 
        LIMIT 10
    ''', fetch_all=True)
    
    return json_response({
        'stats': stats,
        'recent_detections': recent
    })

@app.route('/api/sensor-status', methods=['GET'])
def get_sensor_status_realtime():
    """Get realtime sensor status from ESP32"""
    try:
        ip = get_esp_ip()
        
        # Try to get sensor status from ESP32
        try:
            url = f"http://{ip}/sensor"
            response = requests.get(url, timeout=1)
            
            if response.status_code == 200:
                # Parse JSON response from ESP32
                data = response.json()
                return success_response({
                    'detected': data.get('detected', False),
                    'value': data.get('value', 0),
                    'timestamp': data.get('timestamp', '')
                })
        except:
            # ESP32 endpoint not available, return default
            pass
        
        # Default response when ESP32 doesn't have /sensor endpoint
        return success_response({
            'detected': False,
            'value': 0,
            'note': 'ESP32 sensor endpoint not available'
        })
        
    except Exception as e:
        return error_response(f'Sensor check error: {str(e)}', 500)

@app.route('/api/access-log', methods=['POST'])
def create_access_log():
    """Create access log entry - for manual face check from web"""
    data = request.get_json() or request.form.to_dict()
    
    device_id = data.get('device_id', 1)
    status = data.get('status', 'unknown')
    photo_url = data.get('photo_url')
    recognized_name = data.get('recognized_name')
    confidence = data.get('confidence', 0)
    # Force web_manual for this endpoint (manual checks from web interface)
    source = 'web_manual'
    
    execute_query(
        '''INSERT INTO access_logs 
           (device_id, status, photo_url, recognized_name, confidence, source, timestamp) 
           VALUES (%s, %s, %s, %s, %s, %s, NOW())''',
        (device_id, status, photo_url, recognized_name, confidence, source)
    )
    
    return success_response({'message': 'Log created'})

@app.route('/api/face-unlock', methods=['POST'])
@app.route('/api/face_unlock', methods=['POST'])
def face_unlock_endpoint():
    """
    Face unlock API - Nhận diện khuôn mặt tự động từ ESP32
    Được gọi khi cảm biến LM393 phát hiện chuyển động
    """
    try:
        # Get image from request body (raw JPEG from ESP32)
        img_data = request.get_data()
        
        if not img_data or len(img_data) < 100:
            return error_response('No image data received', 400)
        
        # Check JPEG magic number
        if img_data[:2] != b'\xFF\xD8':
            return error_response('Invalid JPEG data', 400)
        
        # Save to temp file
        tmp_file = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
        tmp_file.write(img_data)
        tmp_file.close()
        
        try:
            # Run face recognition
            python_bin = APP_CONFIG['python_bin']
            script_path = os.path.join(APP_CONFIG['tools_dir'], 'face_check.py')
            
            cmd = [
                python_bin,
                script_path,
                '--image', tmp_file.name,
                '--db', APP_CONFIG['faces_db_dir'],
                '--tolerance', str(APP_CONFIG['tolerance'])
            ]
            
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=APP_CONFIG['python_timeout']
            )
            
            if result.returncode != 0:
                return error_response(f'Recognition error: {result.stderr}', 500)
            
            # Parse result
            import json
            face_result = json.loads(result.stdout)
            
            # Check if face is recognized
            recognized = False
            name = None
            confidence = 0
            
            if face_result.get('ok') and face_result.get('faces'):
                for face in face_result['faces']:
                    if face.get('matched'):
                        recognized = True
                        name = face.get('name', 'Unknown')
                        confidence = face.get('confidence', 0)
                        break
            
            # Save photo if configured
            photo_url = None
            if APP_CONFIG.get('save_unlock_photos', True):
                import shutil
                day, day_path = ensure_upload_dir()
                timestamp = int(time.time())
                random_hex = binascii.hexlify(os.urandom(4)).decode()
                filename = f"unlock_{timestamp}_{random_hex}.jpg"
                
                dest_path = os.path.join(day_path, filename)
                shutil.copy(tmp_file.name, dest_path)
                
                base = APP_CONFIG.get('upload_base', '/uploads').rstrip('/')
                photo_url = f"{base}/{day}/{filename}"
            
            # Log access attempt
            device_id = request.args.get('device_id', 1)
            status = 'granted' if recognized else 'denied'
            
            try:
                execute_query(
                    '''INSERT INTO access_logs 
                       (device_id, status, photo_url, recognized_name, confidence, source, timestamp) 
                       VALUES (%s, %s, %s, %s, %s, 'esp32_auto', NOW())''',
                    (device_id, status, photo_url, name, confidence)
                )
            except Exception as log_err:
                print(f"[WARN] Cannot save log: {log_err}")
            
            # Return result for ESP32
            return success_response({
                'recognized': recognized,
                'name': name or '',
                'confidence': confidence,
                'faces_detected': len(face_result.get('faces', [])),
                'photo_url': photo_url,
                'timestamp': int(time.time())
            })
            
        finally:
            # Clean up temp file
            if os.path.exists(tmp_file.name):
                os.unlink(tmp_file.name)
                
    except subprocess.TimeoutExpired:
        return error_response('Recognition timeout', 500)
    except Exception as e:
        return error_response(f'Server error: {str(e)}', 500)

# ==================== MAIN ====================

if __name__ == '__main__':
    print("🚀 Starting Project_Q Python Backend...")
    print(f"📁 Upload dir: {APP_CONFIG['upload_dir']}")
    print(f"🐍 Python: {APP_CONFIG['python_bin']}")
    print(f"🎯 Face DB: {APP_CONFIG['faces_db_dir']}")
    print(f"🌐 Starting Flask server on http://localhost:5000")
    
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=True
    )
