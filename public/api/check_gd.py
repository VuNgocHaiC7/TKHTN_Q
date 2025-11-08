"""
Check GD API - Python Version
Chuyển đổi từ check_gd.php
"""
from flask import jsonify

def check_gd():
    """Kiểm tra GD extension (trong Python không cần, dùng Pillow)"""
    try:
        from PIL import Image
        has_pil = True
    except:
        has_pil = False
    
    return jsonify({
        'gd_available': has_pil,
        'gd_info': {'PIL/Pillow': 'Available' if has_pil else 'Not installed'},
        'imagecreatefromstring': has_pil,
        'imagecreatetruecolor': has_pil
    })
