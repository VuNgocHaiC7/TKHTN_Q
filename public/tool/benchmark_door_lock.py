"""
Benchmark cho hệ thống khóa cửa thông minh
Test tốc độ & độ chính xác
"""
import subprocess
import json
import time
import statistics

print("=" * 60)
print("🔐 BENCHMARK HỆ THỐNG KHÓA CỬA BẰNG KHUÔN MẶT")
print("=" * 60)

# Test với 5 lần để lấy trung bình
test_image = "faces_db/Hai_1.jpg"
num_tests = 5

print(f"\n📊 Đang test {num_tests} lần với ảnh: {test_image}\n")

latencies = []
confidences = []
results = []

for i in range(num_tests):
    print(f"Test {i+1}/{num_tests}...", end=" ")
    
    start = time.time()
    result = subprocess.run(
        ['python', 'face_check.py', '--image', test_image, '--db', 'faces_db', '--tolerance', '0.8'],
        capture_output=True,
        text=True
    )
    elapsed = (time.time() - start) * 1000
    
    if result.returncode == 0:
        data = json.loads(result.stdout)
        latency = data['latency_ms']
        
        if data['faces']:
            face = data['faces'][0]
            matched = face['matched']
            confidence = face['confidence']
            name = face['name']
            
            latencies.append(latency)
            confidences.append(confidence)
            results.append({
                'matched': matched,
                'name': name,
                'confidence': confidence,
                'latency': latency
            })
            
            status = "✅" if matched else "❌"
            print(f"{status} {name} ({confidence}%) - {latency}ms")
        else:
            print("⚠️  Không phát hiện mặt")
    else:
        print(f"❌ Lỗi: {result.stderr}")

print("\n" + "=" * 60)
print("📈 KẾT QUẢ BENCHMARK")
print("=" * 60)

if latencies:
    print(f"\n⏱️  TỐC ĐỘ:")
    print(f"   Trung bình:  {statistics.mean(latencies):.0f}ms")
    print(f"   Nhanh nhất:  {min(latencies):.0f}ms")
    print(f"   Chậm nhất:   {max(latencies):.0f}ms")
    print(f"   Độ lệch:     ±{statistics.stdev(latencies):.0f}ms")
    
    print(f"\n🎯 ĐỘ CHÍNH XÁC:")
    print(f"   Confidence TB: {statistics.mean(confidences):.1f}%")
    print(f"   Thấp nhất:     {min(confidences):.1f}%")
    print(f"   Cao nhất:      {max(confidences):.1f}%")
    
    success_rate = sum(1 for r in results if r['matched']) / len(results) * 100
    print(f"\n✅ TỶ LỆ THÀNH CÔNG: {success_rate:.0f}%")

print("\n" + "=" * 60)
print("🔐 ĐÁNH GIÁ CHO HỆ THỐNG KHÓA CỬA")
print("=" * 60)

avg_latency = statistics.mean(latencies) if latencies else 0

if avg_latency < 500:
    speed_rating = "⭐⭐⭐⭐⭐ XUẤT SẮC (< 0.5s)"
elif avg_latency < 1000:
    speed_rating = "⭐⭐⭐⭐ RẤT TỐT (< 1s)"
elif avg_latency < 2000:
    speed_rating = "⭐⭐⭐ TỐT (< 2s)"
else:
    speed_rating = "⭐⭐ CẦN CẢI THIỆN (> 2s)"

print(f"\n⚡ Tốc độ: {speed_rating}")

avg_conf = statistics.mean(confidences) if confidences else 0
if avg_conf >= 90:
    acc_rating = "⭐⭐⭐⭐⭐ XUẤT SẮC (>= 90%)"
elif avg_conf >= 80:
    acc_rating = "⭐⭐⭐⭐ TỐT (>= 80%)"
elif avg_conf >= 70:
    acc_rating = "⭐⭐⭐ CHẤP NHẬN (>= 70%)"
else:
    acc_rating = "⭐⭐ CẦN ENROLL THÊM (< 70%)"

print(f"🎯 Độ chính xác: {acc_rating}")

print("\n💡 KHUYẾN NGHỊ:")
if avg_latency < 1000 and avg_conf >= 85:
    print("   ✅ Hệ thống SẴN SÀNG cho khóa cửa thương mại!")
    print("   ✅ Tốc độ nhanh, độ chính xác cao")
    print("   ✅ Trải nghiệm người dùng tốt")
elif avg_latency < 2000:
    print("   ⚠️  Tốc độ ổn nhưng có thể cải thiện thêm")
    print("   💡 Giảm resolution camera nếu cần nhanh hơn")
else:
    print("   ❌ Cần tối ưu thêm cho khóa cửa")
    print("   💡 Kiểm tra CPU, giảm resolution, dùng HOG")

print("\n" + "=" * 60)
