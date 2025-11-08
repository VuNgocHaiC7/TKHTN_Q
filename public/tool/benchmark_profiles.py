"""
Quick Performance Test - So sánh tốc độ các profiles
Chạy: python benchmark_profiles.py --image <path_to_test_image>
"""
import argparse, json, time, sys, os
from PIL import Image
import face_recognition
import numpy as np

# Import profiles
sys.path.insert(0, os.path.dirname(__file__))
from performance_config import PROFILES

def test_profile(image_path, profile_config, db_dir='faces_db'):
    """Test một profile với ảnh cho trước"""
    print(f"Testing with: {profile_config}")
    
    t_start = time.time()
    
    # Load ảnh
    pil_img = Image.open(image_path)
    if pil_img.mode != 'RGB':
        pil_img = pil_img.convert('RGB')
    
    # Resize theo profile
    width, height = pil_img.size
    target_width = profile_config['input_width']
    
    if width > target_width:
        new_width = target_width
        new_height = int(height * (target_width / width))
        pil_img = pil_img.resize((new_width, new_height), Image.LANCZOS)
    
    img = np.array(pil_img, dtype=np.uint8)
    
    # Detect
    t1 = time.time()
    locs = face_recognition.face_locations(
        img,
        model='hog',
        number_of_times_to_upsample=profile_config['upsample']
    )
    t_detect = (time.time() - t1) * 1000
    
    # Encode
    t1 = time.time()
    if locs:
        encs = face_recognition.face_encodings(
            img,
            locs,
            num_jitters=profile_config['jitters']
        )
    else:
        encs = []
    t_encode = (time.time() - t1) * 1000
    
    t_total = (time.time() - t_start) * 1000
    
    return {
        'total_ms': int(t_total),
        'detect_ms': int(t_detect),
        'encode_ms': int(t_encode),
        'faces_found': len(locs)
    }

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--image', required=True, help='Path to test image')
    args = ap.parse_args()
    
    if not os.path.exists(args.image):
        print(f"Error: Image not found: {args.image}")
        return
    
    print("=" * 60)
    print("FACE RECOGNITION PERFORMANCE BENCHMARK")
    print("=" * 60)
    print(f"Test image: {args.image}\n")
    
    results = {}
    
    for profile_name in ['ultra_fast', 'fast', 'balanced', 'accurate']:
        print(f"\n[{profile_name.upper()}]")
        profile = PROFILES[profile_name]
        
        try:
            result = test_profile(args.image, profile)
            results[profile_name] = result
            
            print(f"  ✓ Total time: {result['total_ms']}ms")
            print(f"    - Detection: {result['detect_ms']}ms")
            print(f"    - Encoding: {result['encode_ms']}ms")
            print(f"    - Faces found: {result['faces_found']}")
            print(f"  Expected: {profile['expected_time']}")
            
            # Color code the result
            total_sec = result['total_ms'] / 1000
            if total_sec <= 1.5:
                print(f"  🟢 VERY FAST")
            elif total_sec <= 3:
                print(f"  🟡 FAST")
            else:
                print(f"  🔴 SLOW")
                
        except Exception as e:
            print(f"  ✗ Error: {e}")
            results[profile_name] = {'error': str(e)}
    
    print("\n" + "=" * 60)
    print("SUMMARY")
    print("=" * 60)
    
    # Find fastest
    valid_results = {k: v for k, v in results.items() if 'total_ms' in v}
    if valid_results:
        fastest = min(valid_results.items(), key=lambda x: x[1]['total_ms'])
        print(f"Fastest profile: {fastest[0].upper()} ({fastest[1]['total_ms']}ms)")
        
        # Recommendation
        if fastest[1]['total_ms'] <= 2000:
            print(f"\n✅ RECOMMENDED: Use '{fastest[0]}' profile for 1-2s target!")
        else:
            print(f"\n⚠️ Consider 'ultra_fast' or 'fast' profile for better performance")
    
    print()

if __name__ == '__main__':
    main()
