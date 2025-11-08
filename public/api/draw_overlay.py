"""
Draw Overlay API - Python Version
Chuyển đổi từ draw_overlay.php
"""
import json
import io
from flask import request, Response
from PIL import Image, ImageDraw, ImageFont
from _util import bad

def draw_overlay():
    """Vẽ khung và tên lên ảnh"""
    # Lấy boxes JSON
    boxes_json = request.args.get('boxes') or request.form.get('boxes')
    if not boxes_json:
        return bad('Thiếu boxes JSON (dùng ?boxes=... hoặc POST boxes=...)')
    
    try:
        boxes = json.loads(boxes_json)
    except:
        return bad('boxes JSON sai format')
    
    if 'faces' not in boxes:
        return bad('boxes JSON sai format')
    
    # Lấy ảnh: ưu tiên từ file upload
    if 'image' in request.files:
        img_bytes = request.files['image'].read()
    else:
        img_bytes = request.get_data()
    
    if not img_bytes:
        return bad('Thiếu ảnh JPG')
    
    try:
        img = Image.open(io.BytesIO(img_bytes))
    except:
        return bad('Ảnh không hợp lệ')
    
    # Vẽ khung
    draw = ImageDraw.Draw(img)
    thickness = 3
    
    for face in boxes['faces']:
        box = face['box']
        x1, y1, x2, y2 = box
        
        color = (0, 255, 0) if face.get('matched') else (255, 0, 0)
        
        # Vẽ khung dày
        for i in range(thickness):
            draw.rectangle([x1-i, y1-i, x2+i, y2+i], outline=color)
        
        # Vẽ tên nếu có
        if face.get('name'):
            text = face['name']
            # Background cho text
            draw.rectangle([x1, max(0, y1-20), x1 + len(text)*8, y1], fill=color)
            draw.text((x1+2, max(0, y1-18)), text, fill=(255, 255, 255))
    
    # Xuất JPEG
    output = io.BytesIO()
    img.save(output, format='JPEG', quality=90)
    output.seek(0)
    
    return Response(output.getvalue(), mimetype='image/jpeg', headers={'Cache-Control': 'no-store'})
