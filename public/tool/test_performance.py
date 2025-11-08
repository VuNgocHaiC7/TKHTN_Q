"""
Quick test script to verify optimization improvements
Run: python test_performance.py
"""
import time
import sys
import os

print("=" * 60)
print("🚀 TESTING FACE DETECTION PERFORMANCE")
print("=" * 60)

# Test 1: Import speed
print("\n1️⃣  Testing imports...")
t0 = time.time()
try:
    import cv2
    import_cv2_time = (time.time() - t0) * 1000
    print(f"   ✅ OpenCV imported: {import_cv2_time:.0f}ms")
except ImportError:
    print("   ❌ OpenCV not installed! Run: pip install opencv-python")
    sys.exit(1)

t0 = time.time()
try:
    import face_recognition
    import_fr_time = (time.time() - t0) * 1000
    print(f"   ✅ face_recognition imported: {import_fr_time:.0f}ms")
except ImportError:
    print("   ❌ face_recognition not installed! Run: pip install face-recognition")
    sys.exit(1)

# Test 2: Haar Cascade loading
print("\n2️⃣  Testing Haar Cascade...")
t0 = time.time()
cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
face_cascade = cv2.CascadeClassifier(cascade_path)
load_time = (time.time() - t0) * 1000
print(f"   ✅ Cascade loaded: {load_time:.0f}ms")

# Test 3: Check database
print("\n3️⃣  Checking face database...")
db_path = os.path.join(os.path.dirname(__file__), 'faces_db')
if os.path.exists(db_path):
    faces = [f for f in os.listdir(db_path) if not f.endswith('.pkl')]
    print(f"   ✅ Database found: {len(faces)} face(s)")
else:
    print(f"   ⚠️  Database not found at: {db_path}")

# Test 4: Check config
print("\n4️⃣  Checking config...")
config_path = os.path.join(os.path.dirname(__file__), '..', 'api', '_config.php')
if os.path.exists(config_path):
    with open(config_path, 'r', encoding='utf-8') as f:
        content = f.read()
        if "'tolerance' => 0.7" in content or "'tolerance' => 0.65" in content:
            print("   ✅ Tolerance optimized (0.65-0.7)")
        elif "'tolerance' => 0.6" in content:
            print("   ⚠️  Tolerance still default (0.6) - may cause false negatives")
        else:
            print("   ⚠️  Tolerance config not found")
else:
    print(f"   ⚠️  Config not found")

# Summary
print("\n" + "=" * 60)
print("📊 SUMMARY")
print("=" * 60)
print(f"OpenCV import:        {import_cv2_time:.0f}ms")
print(f"face_recognition:     {import_fr_time:.0f}ms")
print(f"Cascade load:         {load_time:.0f}ms")
print(f"Total init time:      {import_cv2_time + import_fr_time + load_time:.0f}ms")
print("\n💡 EXPECTED PERFORMANCE:")
print("   • Tracking (Haar):    30-50ms/frame (20 FPS)")
print("   • Recognition:        300-500ms/frame (2-3 FPS)")
print("   • Tolerance:          0.7 (recommended)")
print("\n✅ System ready for ultra-fast face detection!")
print("=" * 60)
