"""
ULTRA FAST Face Recognition - Target: 0.8-1.5 seconds
Optimizations:
- Aggressive image resizing (480px)
- HOG model only
- Zero upsampling for speed
- Cached encodings
- Vectorized operations
- TRADEOFF: Speed over accuracy (use for tracking/preview)
"""
import argparse, json, time, os, glob, pickle
import numpy as np
from PIL import Image
import face_recognition

CACHE_FILE = 'faces_db/.encodings_cache_v2.pkl'

def load_known_encodings(db_dir):
    """Load and cache face encodings - FAST MODE (lower quality for speed)"""
    cache_path = os.path.join(db_dir, '.encodings_cache_ultra_fast.pkl')  # Separate cache!
    
    # Check cache validity
    if os.path.exists(cache_path):
        try:
            cache_mtime = os.path.getmtime(cache_path)
            db_files = [f for f in glob.glob(os.path.join(db_dir, '*.*')) if not f.endswith('.pkl')]
            if db_files:
                db_mtime = max([os.path.getmtime(f) for f in db_files])
                if cache_mtime > db_mtime:
                    with open(cache_path, 'rb') as f:
                        data = pickle.load(f)
                        return data['encodings'], data['names'], data['metadata']
        except:
            pass
    
    # Build cache - MINIMAL processing
    encs, names, metadata = [], [], {}
    for path in glob.glob(os.path.join(db_dir, '*.*')):
        if path.endswith('.pkl'):
            continue
        name = os.path.splitext(os.path.basename(path))[0]
        label = name.split('_')[0]
        
        try:
            pil_img = Image.open(path)
            if pil_img.mode != 'RGB':
                pil_img = pil_img.convert('RGB')
            
            # AGGRESSIVE resize - 320x320 for database images
            pil_img.thumbnail((320, 320), Image.LANCZOS)
            img = np.array(pil_img, dtype=np.uint8)
            
            # HOG + no upsample = FASTEST
            locs = face_recognition.face_locations(img, model='hog', number_of_times_to_upsample=0)
            if not locs:
                continue
                
            # Single jitter = FAST
            enc = face_recognition.face_encodings(img, locs, num_jitters=1)[0]
            encs.append(enc)
            names.append(label)
        except Exception as e:
            continue
    
    # Convert to numpy for faster distance calculation
    if encs:
        encs = np.array(encs)
    
    metadata = {
        'count': len(names),
        'created': time.time()
    }
    
    # Save cache
    try:
        with open(cache_path, 'wb') as f:
            pickle.dump({
                'encodings': encs,
                'names': names,
                'metadata': metadata
            }, f, protocol=4)
    except:
        pass
    
    return encs, names, metadata

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--image', required=True)
    ap.add_argument('--db', required=True)
    ap.add_argument('--tolerance', type=float, default=0.8)
    args = ap.parse_args()

    t0 = time.time()
    t_steps = {}
    
    # === STEP 1: Load database ===
    t1 = time.time()
    known_encs, known_names, db_meta = load_known_encodings(args.db)
    t_steps['db_load'] = int((time.time() - t1) * 1000)
    
    if len(known_encs) == 0:
        print(json.dumps({
            'ok': False,
            'error': 'No faces in database',
            'latency_ms': int((time.time() - t0) * 1000)
        }))
        return

    # === STEP 2: Load and resize image AGGRESSIVELY ===
    t1 = time.time()
    pil_img = Image.open(args.image)
    if pil_img.mode != 'RGB':
        pil_img = pil_img.convert('RGB')
    
    # Target 480px width - BALANCE speed/accuracy
    width, height = pil_img.size
    target_width = 480
    
    if width > target_width:
        new_width = target_width
        new_height = int(height * (target_width / width))
        pil_img = pil_img.resize((new_width, new_height), Image.LANCZOS)
        scale = width / new_width
    else:
        scale = 1
    
    img = np.array(pil_img, dtype=np.uint8)
    t_steps['image_load'] = int((time.time() - t1) * 1000)
    
    # === STEP 3: Detect faces - FASTEST settings ===
    t1 = time.time()
    # HOG + NO upsample = maximum speed
    locs = face_recognition.face_locations(
        img, 
        model='hog',
        number_of_times_to_upsample=0  # KEY: Skip upsampling!
    )
    t_steps['face_detect'] = int((time.time() - t1) * 1000)
    
    if not locs:
        print(json.dumps({
            'ok': True,
            'faces': [],
            'count': 0,
            'latency_ms': int((time.time() - t0) * 1000),
            'timing': t_steps
        }))
        return
    
    # === STEP 4: Encode faces ===
    t1 = time.time()
    encs = face_recognition.face_encodings(
        img, 
        locs, 
        num_jitters=1  # Single jitter = fast
    )
    t_steps['face_encode'] = int((time.time() - t1) * 1000)
    
    # === STEP 5: Match faces - VECTORIZED ===
    t1 = time.time()
    faces_out = []
    
    for (top, right, bottom, left), enc in zip(locs, encs):
        matched = False
        name = 'unknown'
        confidence = 0.0
        min_dist = 1.0
        
        # Vectorized distance calculation (FAST)
        dists = face_recognition.face_distance(known_encs, enc)
        j = int(np.argmin(dists))
        min_dist = float(dists[j])
        
        # Convert distance to confidence
        confidence = max(0, min(100, (1 - min_dist) * 100))
        
        # Relaxed tolerance for speed
        if min_dist <= args.tolerance:
            matched = True
            name = known_names[j]
        
        # Scale coordinates back
        faces_out.append({
            'box': [
                int(left * scale), 
                int(top * scale), 
                int(right * scale), 
                int(bottom * scale)
            ],
            'name': name,
            'matched': bool(matched),
            'confidence': round(confidence, 1),
            'distance': round(min_dist, 3)
        })
    
    t_steps['face_match'] = int((time.time() - t1) * 1000)
    
    # === OUTPUT ===
    total_ms = int((time.time() - t0) * 1000)
    
    out = {
        'ok': True,
        'faces': faces_out,
        'count': len(faces_out),
        'latency_ms': total_ms,
        'timing': t_steps,
        'db_stats': {
            'known_faces': len(known_names),
            'cached': db_meta.get('created', 0) > 0
        },
        'optimizations': [
            'Image resized to 480px',
            'HOG model (no CNN)',
            'Zero upsampling (fastest)',
            'Single jitter encoding',
            'Vectorized matching',
            'Cached database encodings',
            'TRADEOFF: Speed prioritized over accuracy'
        ]
    }
    
    print(json.dumps(out, ensure_ascii=False))

if __name__ == '__main__':
    main()
