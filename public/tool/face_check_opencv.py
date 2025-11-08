import argparse, json, time
import cv2
import numpy as np

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--image', required=True)
    args = ap.parse_args()

    t0 = time.time()
    
    # Load ảnh
    img = cv2.imread(args.image)
    if img is None:
        print(json.dumps({'ok': False, 'error': 'Cannot read image'}))
        return
    
    # Load Haar Cascade để phát hiện mặt
    face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
    
    # Chuyển sang grayscale
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # Phát hiện khuôn mặt
    faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))
    
    faces_out = []
    for (x, y, w, h) in faces:
        # OpenCV trả về (x,y,w,h) nhưng ta cần (x1,y1,x2,y2)
        faces_out.append({
            'box': [int(x), int(y), int(x+w), int(y+h)],
            'name': 'unknown',  # OpenCV không nhận diện được tên
            'matched': False    # Luôn false vì không có database
        })
    
    out = {
        'ok': True,
        'faces': faces_out,
        'latency_ms': int((time.time()-t0)*1000),
        'note': 'Using OpenCV (face detection only, no recognition)'
    }
    print(json.dumps(out))

if __name__ == '__main__':
    main()
