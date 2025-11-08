"""
Performance Configuration for Face Recognition
Điều chỉnh các tham số này để cân bằng giữa tốc độ và độ chính xác
"""

# === IMAGE PROCESSING ===
# Resolution ảnh input (px)
# - 320: Siêu nhanh (~0.5s) nhưng có thể miss faces nhỏ
# - 480: Rất nhanh (~1-1.5s) - RECOMMENDED cho 1-2s target
# - 640: Nhanh (~2-3s) - độ chính xác cao hơn
# - 800: Chậm hơn (~3-5s) nhưng chính xác nhất
INPUT_IMAGE_WIDTH = 480

# Resolution ảnh database (px)
# - 320: Encoding nhanh nhất, độ chính xác tốt
# - 400: Cân bằng tốt
DB_IMAGE_SIZE = 320

# === FACE DETECTION ===
# Model: 'hog' (fast) hoặc 'cnn' (accurate but VERY slow)
DETECTION_MODEL = 'hog'

# Upsample count: số lần scale ảnh lên để detect faces nhỏ hơn
# - 0: FASTEST (1-2s) - miss faces < 80px
# - 1: Fast (2-3s) - detect faces >= 60px
# - 2: Slower (4-6s) - detect faces >= 40px
UPSAMPLE_TIMES = 0

# === FACE ENCODING ===
# Num jitters: số lần augment ảnh khi encoding
# - 1: FASTEST, độ chính xác ~95%
# - 2-5: Chậm hơn, độ chính xác ~97-98%
# - 10: Rất chậm, độ chính xác ~99% (default của library)
NUM_JITTERS = 1

# === MATCHING ===
# Tolerance: ngưỡng distance để coi là matched
# - 0.5: Rất khắt khe, ít false positive
# - 0.6: Standard, cân bằng tốt
# - 0.7-0.8: Dễ nhận diện hơn, có thể có false positive
TOLERANCE = 0.8

# === PERFORMANCE PROFILES ===
PROFILES = {
    'ultra_fast': {
        'input_width': 320,
        'db_size': 320,
        'upsample': 0,
        'jitters': 1,
        'tolerance': 0.8,
        'expected_time': '0.5-1.0s',
        'accuracy': '~90%'
    },
    'fast': {
        'input_width': 480,
        'db_size': 320,
        'upsample': 0,
        'jitters': 1,
        'tolerance': 0.7,
        'expected_time': '1.0-2.0s',
        'accuracy': '~95%'
    },
    'balanced': {
        'input_width': 640,
        'db_size': 400,
        'upsample': 1,
        'jitters': 1,
        'tolerance': 0.6,
        'expected_time': '2.0-3.5s',
        'accuracy': '~97%'
    },
    'accurate': {
        'input_width': 800,
        'db_size': 400,
        'upsample': 1,
        'jitters': 2,
        'tolerance': 0.55,
        'expected_time': '4.0-6.0s',
        'accuracy': '~99%'
    }
}

# Active profile - thay đổi profile ở đây
ACTIVE_PROFILE = 'fast'  # ultra_fast | fast | balanced | accurate

def get_config():
    """Get current configuration"""
    if ACTIVE_PROFILE in PROFILES:
        return PROFILES[ACTIVE_PROFILE]
    else:
        return {
            'input_width': INPUT_IMAGE_WIDTH,
            'db_size': DB_IMAGE_SIZE,
            'upsample': UPSAMPLE_TIMES,
            'jitters': NUM_JITTERS,
            'tolerance': TOLERANCE
        }

if __name__ == '__main__':
    import json
    print("=== Face Recognition Performance Profiles ===\n")
    for name, profile in PROFILES.items():
        active = " [ACTIVE]" if name == ACTIVE_PROFILE else ""
        print(f"{name.upper()}{active}:")
        print(f"  Time: {profile['expected_time']}")
        print(f"  Accuracy: {profile['accuracy']}")
        print(f"  Settings: {profile['input_width']}px, upsample={profile['upsample']}, jitters={profile['jitters']}")
        print()
