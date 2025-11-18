<!-- === ESP32-CAM CARD (REWRITTEN) === -->
<div class="card esp32cam">
  <div class="row">
    <span class="title">📷 Camera ESP32-CAM</span>

    <input id="esp_ip" value="10.80.115.74" placeholder="ESP32 IP...">

    <button id="btn_reload" class="btn">Tải lại</button>
    <button id="btn_capture" class="btn">📸 Chụp ảnh</button>
    <button id="btn_recognize" class="btn btn-recognize" style="background:#10b981">🔍 Nhận diện khuôn mặt</button>

    <label class="chk">
      <input id="auto_on" type="checkbox">
      <span>Tự chụp khi có người (motion)</span>
    </label>

    <label class="inline">
      Thr %
      <input id="auto_thr" type="number" step="0.5" value="5" title="Ngưỡng % khác biệt">
    </label>

    <label class="inline">
      Chu kỳ (ms)
      <input id="auto_ms" type="number" step="100" value="500" title="Chu kỳ ms">
    </label>

    <span id="cam_status" class="badge">IDLE</span>

    <!-- Trạng thái cảm biến LM393 -->
    <div class="sensor-status">
      <span class="sensor-icon" id="sensor_icon">📡</span>
      <div class="sensor-info">
        <span class="sensor-label">Cảm biến Hồng Ngoại</span>
        <span id="sensor_status" class="badge sensor-badge">CHECKING...</span>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="stream-wrapper">
      <img id="cam" alt="ESP32 stream">
      <canvas id="cam_canvas"></canvas>
    </div>

    <!-- Panel kết quả nhận diện -->
    <div id="face_result" class="face-result" style="display:none;">
      <div class="face-result-header">
        <span>🎯 Kết quả nhận diện</span>
        <button class="face-result-close" onclick="document.getElementById('face_result').style.display='none'">✕</button>
      </div>
      <div id="face_result_content"></div>
    </div>

    <div class="controls">
      <div class="grid">
        <button class="btn" data-led="255">Bật LED</button>
        <button class="btn" data-led="0">Tắt LED</button>

        <button class="btn" data-ctrl="face_detect" data-val="1">Face Detect ON</button>
        <button class="btn" data-ctrl="face_detect" data-val="0">Face Detect OFF</button>

        <button class="btn" data-ctrl="face_recognize" data-val="1">Face Recognize ON</button>
        <button class="btn" data-ctrl="face_recognize" data-val="0">Face Recognize OFF</button>

        <button class="btn" id="btn_enroll">Enroll Face ×5</button>
      </div>

      <p class="note">
        <b>Ghi chú:</b> Enroll thường cần nhấn vài lần (mỗi lần chụp thêm mẫu - 5-10 lần).
        Stream MJPEG: <code>http://IP:81/stream</code>. Ảnh lưu tại <code>public/uploads/</code>.<br>
        <b>🔍 Nhận diện:</b> Nhấn nút "Nhận diện khuôn mặt" để AI phát hiện và nhận diện.<br>
        <b>📊 Kết quả:</b> 🟢 Khung XANH + Tên + % = Đúng | 🔴 Khung ĐỎ + Unknown = Sai hoặc chưa Enroll.<br>
        <b>💡 Tips:</b> Ánh sáng đều, mặt chính diện, khoảng cách 0.5-2m. Tolerance: 0.8 (dễ nhận diện).<br>
        <b>🚨 Cảm biến LM393:</b> <strong>TỰ ĐỘNG hoạt động!</strong> ESP32 tự chụp và nhận diện khi phát hiện chuyển động.<br>
        <b>📡 Trạng thái:</b> Frontend hiển thị kết quả realtime từ database (cập nhật mỗi giây).
      </p>
    </div>
  </div>
</div>

<style>
  .card.esp32cam {
    background: #151a21;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    color: #cfd3dc
  }

  .esp32cam .row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px
  }

  .esp32cam .title {
    font-weight: 700;
    font-size: 20px
  }

  .esp32cam input {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #2b3240;
    background: #0f141a;
    color: #cfd3dc
  }

  #esp_ip {
    min-width: 170px
  }

  .inline {
    display: flex;
    align-items: center;
    gap: 6px
  }

  .chk {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px
  }

  .btn {
    background: #2563eb;
    border: 0;
    color: #fff;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer
  }

  .btn:hover {
    opacity: .9
  }

  .btn-face {
    background: #fbbf24
  }

  .btn-face:hover {
    background: #f59e0b
  }

  .btn-recognize {
    background: #2563eb
  }

  .btn-recognize:hover {
    background: #1d4ed8
  }

  .btn[disabled] {
    opacity: .55;
    cursor: not-allowed
  }

  .badge {
    background: #0b1220;
    border: 1px solid #2b3240;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 12px
  }

  .badge.ok {
    border-color: #16a34a
  }

  .badge.warn {
    border-color: #eab308
  }

  .badge.err {
    border-color: #ef4444
  }

  /* Sensor status display */
  .sensor-status {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #0f141a;
    border: 2px solid #2b3240;
    border-radius: 10px;
    padding: 8px 14px;
    margin-left: auto;
    transition: all 0.3s ease;
  }

  .sensor-status.active {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    animation: pulse-green 2s infinite;
  }

  .sensor-status.detecting {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
    animation: pulse-orange 1s infinite;
  }

  @keyframes pulse-green {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }

    50% {
      box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
  }

  @keyframes pulse-orange {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
    }

    50% {
      box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
    }
  }

  .sensor-icon {
    font-size: 24px;
    animation: rotate 3s linear infinite;
  }

  .sensor-status.active .sensor-icon {
    animation: none;
  }

  .sensor-status.detecting .sensor-icon {
    animation: shake 0.5s infinite;
  }

  @keyframes rotate {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }

  @keyframes shake {

    0%,
    100% {
      transform: translateX(0);
    }

    25% {
      transform: translateX(-3px);
    }

    75% {
      transform: translateX(3px);
    }
  }

  .sensor-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .sensor-label {
    font-size: 11px;
    color: #9aa4b2;
    font-weight: 500;
  }

  .sensor-badge {
    font-size: 11px;
    padding: 2px 8px;
  }

  .sensor-badge.detected {
    background: #10b981;
    border-color: #10b981;
    color: white;
  }

  .sensor-badge.idle {
    background: #6b7280;
    border-color: #6b7280;
  }

  .sensor-badge.recognizing {
    background: #f59e0b;
    border-color: #f59e0b;
    color: white;
  }

  .esp32cam .content {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    align-items: flex-start;
    position: relative
  }

  #cam {
    width: 432px;
    max-width: 95vw;
    height: auto;
    border-radius: 10px;
    background: #0b0f14;
    border: 1px solid #2b3240
  }

  .stream-wrapper {
    position: relative;
    display: inline-block;
    width: 432px;
    max-width: 95vw;
  }

  #cam_canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    border-radius: 10px;
  }

  .controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-width: 440px
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(140px, 1fr));
    gap: 8px
  }

  .note {
    opacity: .85;
    font-size: 13px;
    line-height: 1.5;
    color: #9aa4b2
  }

  /* Face detection result panel */
  .face-result {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #1a1f26;
    border: 2px solid #2563eb;
    border-radius: 12px;
    padding: 0;
    min-width: 300px;
    max-width: 90vw;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    z-index: 1000;
  }

  .face-result-header {
    background: #2563eb;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 10px 10px 0 0;
    font-weight: 600;
  }

  .face-result-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    line-height: 1;
  }

  .face-result-close:hover {
    opacity: 0.7;
  }

  #face_result_content {
    padding: 16px;
  }

  .face-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    margin: 6px 0;
    background: #0f141a;
    border-radius: 8px;
    border-left: 4px solid;
  }

  .face-item.matched {
    border-left-color: #10b981;
  }

  .face-item.unknown {
    border-left-color: #ef4444;
  }

  .face-item-icon {
    font-size: 24px;
  }

  .face-item-info {
    flex: 1;
  }

  .face-item-name {
    font-weight: 600;
    font-size: 15px;
  }

  .face-item-box {
    font-size: 11px;
    color: #9aa4b2;
  }

  /* toast */
  .toast {
    position: fixed;
    right: 16px;
    bottom: 16px;
    background: #111827;
    border: 1px solid #374151;
    color: #e5e7eb;
    padding: 10px 14px;
    border-radius: 8px;
    max-width: 60vw;
    z-index: 9999
  }
</style>

<script>
  (() => {
    // ====== CONFIG ======
    const API_BASE = 'http://localhost:5000/api'; // Python Flask API
    const SEL = s => document.querySelector(s);
    const ipEl = SEL('#esp_ip');
    const imgEl = SEL('#cam');
    const statusEl = SEL('#cam_status');

    const btnReload = SEL('#btn_reload');
    const btnCapture = SEL('#btn_capture');
    const btnEnroll = SEL('#btn_enroll');
    const btnRecognize = SEL('#btn_recognize');

    const canvasEl = SEL('#cam_canvas');
    const ctx = canvasEl.getContext('2d');

    const autoOn = SEL('#auto_on');
    const autoThr = SEL('#auto_thr');
    const autoMs = SEL('#auto_ms');

    const sensorStatusEl = SEL('#sensor_status');
    const sensorIcon = SEL('#sensor_icon');
    const sensorContainer = SEL('.sensor-status');

    let autoTimer = null;
    let autoBusy = false; // chống chồng lệnh
    let useSimple = false; // fallback khi server báo thiếu GD
    let sensorTimer = null; // Timer cho polling cảm biến
    let isRecognizing = false; // Đang trong quá trình nhận diện
    let lastSensorState = false; // Trạng thái cảm biến trước đó
    let lastDisplayedLogId = null; // ID của log đã hiển thị (tránh hiển thị log cũ)

    // ====== UTILS ======
    const sleep = ms => new Promise(r => setTimeout(r, ms));

    function toast(msg, ms = 2500) {
      const t = document.createElement('div');
      t.className = 'toast';
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), ms);
    }

    function setStatus(text, cls = '') {
      statusEl.textContent = text;
      statusEl.className = `badge ${cls}`;
    }

    function streamUrl(ip) {
      return `http://${ip}:81/stream`;
    }

    function capUrl(ip) {
      return `http://${ip}/capture`;
    }

    function validIP(v) {
      return /^[0-9.]+$/.test(v.trim());
    }

    async function fetchJsonSafe(url, opts = {}) {
      const res = await fetch(url, {
        cache: 'no-store',
        ...opts
      });
      // Nếu HTTP lỗi → ném lỗi kèm body text để debug
      if (!res.ok) {
        const raw = await res.text().catch(() => '');
        throw new Error(`HTTP ${res.status} @ ${url}\n${raw.slice(0,200)}`);
      }
      // Chỉ parse JSON khi chắc chắn
      const ct = res.headers.get('content-type') || '';
      if (ct.includes('application/json')) return res.json();
      const txt = await res.text();
      try {
        return JSON.parse(txt);
      } catch {
        throw new Error(`Expected JSON but got: ${txt.slice(0,200)}`);
      }
    }

    function getIP() {
      const ip = ipEl.value.trim();
      if (!ip || !validIP(ip)) {
        toast('IP không hợp lệ');
        throw new Error('Invalid IP');
      }
      return ip;
    }

    function disableDuring(el, promise) {
      el.disabled = true;
      return promise.finally(() => el.disabled = false);
    }

    // ====== SENSOR (LM393) ======
    function setSensorStatus(text, cls = '', containerCls = '') {
      sensorStatusEl.textContent = text;
      sensorStatusEl.className = `badge sensor-badge ${cls}`;
      sensorContainer.className = `sensor-status ${containerCls}`;
    }

    async function checkSensor() {
      try {
        // Lấy 1 log mới nhất từ database
        const res = await fetchJsonSafe(`${API_BASE}/logs?limit=1`);

        if (res.ok && res.data && res.data.length > 0) {
          const latestLog = res.data[0];
          const logId = latestLog.id;
          const logTime = new Date(latestLog.timestamp).getTime();
          const now = Date.now();
          const ageSeconds = (now - logTime) / 1000;
          const status = latestLog.status;
          const name = latestLog.recognized_name || 'Unknown';
          const confidence = latestLog.confidence || 0;
          const photoUrl = latestLog.photo_url;

          // Nếu lần đầu tiên, khởi tạo lastDisplayedLogId
          if (lastDisplayedLogId === null) {
            lastDisplayedLogId = logId - 1; // Đánh dấu để log hiện tại được coi là "mới"
          }

          // Kiểm tra xem có phải log mới chưa xử lý không
          const isNewLog = (logId > lastDisplayedLogId);

          // ===== LOGIC THEO THỜI GIAN =====

          if (ageSeconds < 3) {
            // Log MỚI (< 3 giây) - ĐANG XỬ LÝ hoặc VỪA XONG

            if (status === 'granted') {
              setSensorStatus(`✅ ${name} (${Math.round(confidence)}%)`, 'detected', 'active');
              sensorIcon.textContent = '✅';

              // Chỉ hiển thị toast + ảnh lần đầu với log mới
              if (isNewLog && !lastSensorState) {
                toast(`✅ Cho phép: ${name} (${Math.round(confidence)}%)`, 3000);
                if (photoUrl) {
                  showAutoDetectionImage(photoUrl, name, confidence, true);
                }
                lastDisplayedLogId = logId;
              }
              lastSensorState = true;

            } else if (status === 'denied') {
              setSensorStatus(`❌ Không nhận diện`, 'err', 'detecting');
              sensorIcon.textContent = '❌';

              // Chỉ hiển thị toast + ảnh lần đầu với log mới
              if (isNewLog && !lastSensorState) {
                toast(`❌ Không nhận diện được`, 3000);
                if (photoUrl) {
                  showAutoDetectionImage(photoUrl, name, confidence, false);
                }
                lastDisplayedLogId = logId;
              }
              lastSensorState = true;
            }

          } else {
            // Log > 3 giây - CŨ → Về trạng thái ĐANG CHỜ
            setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
            sensorIcon.textContent = '📡';
            lastSensorState = false;
          }
        } else {
          // Không có log nào - Hệ thống mới khởi động
          setSensorStatus('🟢 SẴN SÀNG', 'ok', 'active');
          sensorIcon.textContent = '📡';
          lastSensorState = false;
        }
      } catch (e) {
        console.warn('Sensor check error:', e);
        setSensorStatus('⚠️ OFFLINE', 'err', '');
        sensorIcon.textContent = '📡';
      }
    }

    function startSensorPolling() {
      if (sensorTimer) return;

      console.log('🚀 Starting LM393 sensor polling (via database logs)...');
      console.log('📌 Auto face detection: ENABLED (like button click)');
      setSensorStatus('🔄 ĐANG KẾT NỐI...', '', '');

      // Kiểm tra log mới để phát hiện khi ESP32 bắt đầu nhận diện
      let lastLogId = null;

      const enhancedCheckSensor = async () => {
        try {
          const res = await fetchJsonSafe(`${API_BASE}/logs?limit=1`);

          if (res.ok && res.data && res.data.length > 0) {
            const latestLog = res.data[0];
            const logId = latestLog.id;
            const logTime = new Date(latestLog.timestamp).getTime();
            const now = Date.now();
            const ageSeconds = (now - logTime) / 1000;
            const logSource = latestLog.source || 'unknown';

            // CHỈ trigger khi log từ ESP32 (auto) và là log MỚI (< 2 giây)
            if (lastLogId !== null &&
              logId > lastLogId &&
              ageSeconds < 2 &&
              logSource === 'esp32_auto') {
              console.log(`🔔 ESP32 AUTO DETECTION! ID: ${logId}, Age: ${ageSeconds}s`);
              triggerAutoRecognition();
            } else if (lastLogId !== null && logId > lastLogId && ageSeconds < 2) {
              console.log(`ℹ️ New log detected but source=${logSource}, skipping auto trigger`);
            }

            lastLogId = logId;
          }
        } catch (e) {
          console.warn('Enhanced sensor check error:', e);
        }

        // Gọi hàm check sensor bình thường
        await checkSensor();
      };

      // Poll mỗi 800ms (nhanh hơn để phát hiện kịp thời)
      sensorTimer = setInterval(enhancedCheckSensor, 800);
      enhancedCheckSensor(); // Check ngay lập tức
    }

    function stopSensorPolling() {
      if (sensorTimer) {
        clearInterval(sensorTimer);
        sensorTimer = null;
        setSensorStatus('⏸️ TẠM DỪNG', 'idle', '');
        console.log('⏸️ Stopped LM393 sensor polling');
      }
    }

    // Hiển thị ảnh khi ESP32 tự động nhận diện
    async function showAutoDetectionImage(photoUrl, name, confidence, matched) {
      console.log('📷 Displaying auto detection result:', {
        photoUrl,
        name,
        confidence,
        matched
      });

      try {
        // photoUrl có dạng: /uploads/20251118/unlock_123456_abc.jpg
        const imageUrl = `http://localhost:5000${photoUrl}`;

        // Hiển thị ảnh gốc trước
        imgEl.src = imageUrl;

        // Tạo fake face result để hiển thị panel
        const faceResult = {
          ok: true,
          faces: [{
            name: name,
            confidence: Math.round(confidence),
            matched: matched,
            box: [0, 0, 100, 100] // Box giả vì không có tọa độ thật
          }],
          latency_ms: 'ESP32 Auto'
        };

        // Hiển thị panel kết quả
        showFaceResult(faceResult);

        // Vẽ viền lên ảnh (không có box chính xác nên chỉ hiển thị status)
        const canvas = document.getElementById('cam_canvas');
        const ctx = canvas.getContext('2d');

        imgEl.onload = () => {
          canvas.width = imgEl.width;
          canvas.height = imgEl.height;

          ctx.clearRect(0, 0, canvas.width, canvas.height);

          // Vẽ viền xung quanh ảnh
          ctx.strokeStyle = matched ? '#10b981' : '#ef4444';
          ctx.lineWidth = 6;
          ctx.strokeRect(3, 3, canvas.width - 6, canvas.height - 6);

          // Vẽ text ở góc trên
          ctx.font = 'bold 20px Arial';
          ctx.fillStyle = matched ? '#10b981' : '#ef4444';
          const text = matched ? `✅ ${name} (${Math.round(confidence)}%)` : `❌ Unknown`;
          ctx.fillText(text, 15, 35);
        };

        // Quay lại stream sau 5 giây
        setTimeout(reloadCam, 5000);

      } catch (e) {
        console.error('Error displaying auto detection image:', e);
      }
    }

    async function performAutoFaceDetection() {
      // Tự động nhận diện giống y hệt button "Nhận diện khuôn mặt"
      console.log('🤖 Auto face detection triggered by LM393 sensor');

      try {
        const ip = getIP();

        // Hiển thị trạng thái đang nhận diện
        setSensorStatus('🔍 ĐANG NHẬN DIỆN...', 'recognizing', 'detecting');
        setStatus('AUTO DETECTING…', 'warn');

        // Bước 1: Gọi API nhận diện (GIỐNG Y HỆT BUTTON)
        const faceRes = await fetchJsonSafe(`${API_BASE}/face-check?ip=${encodeURIComponent(ip)}`);

        if (!faceRes.ok) {
          console.warn('Auto detection failed:', faceRes.error);
          return;
        }

        const faceCount = faceRes.faces ? faceRes.faces.length : 0;
        const matchedCount = faceRes.faces ? faceRes.faces.filter(f => f.matched).length : 0;

        if (faceCount === 0) {
          console.log('No face detected in auto mode');
          return;
        }

        // Bước 2: Lấy ảnh gốc
        const imgRes = await fetch(`${API_BASE}/esp32-capture?ip=${encodeURIComponent(ip)}`);
        const imgBlob = await imgRes.blob();

        // Bước 3: Vẽ khung lên ảnh (GIỐNG Y HỆT BUTTON)
        const boxesParam = encodeURIComponent(JSON.stringify(faceRes));
        const overlayRes = await fetch(`${API_BASE}/draw-overlay?boxes=${boxesParam}`, {
          method: 'POST',
          body: imgBlob,
          headers: {
            'Content-Type': 'image/jpeg'
          }
        });

        if (overlayRes.ok) {
          const overlayBlob = await overlayRes.blob();
          imgEl.src = URL.createObjectURL(overlayBlob);

          // Hiển thị panel kết quả (GIỐNG Y HỆT BUTTON)
          showFaceResult(faceRes);

          // Toast notification
          if (matchedCount > 0) {
            const names = faceRes.faces
              .filter(f => f.matched)
              .map(f => f.name)
              .join(', ');
            toast(`🎯 LM393: Phát hiện ${names}`, 3000);
          } else {
            toast(`⚠️ LM393: Phát hiện ${faceCount} mặt nhưng không nhận diện được`, 3000);
          }

          // Quay lại stream sau 5 giây
          setTimeout(reloadCam, 5000);
        }

      } catch (e) {
        console.error('Auto face detection error:', e);
      }
    }

    async function triggerAutoRecognition() {
      // Mark đang trong quá trình nhận diện
      isRecognizing = true;
      setSensorStatus('🔍 ĐANG NHẬN DIỆN...', 'recognizing', 'detecting');
      sensorIcon.textContent = '🔍';
      console.log('⏭️ Waiting for ESP32 recognition result...');
    }

    // ====== CORE ======
    function reloadCam() {
      try {
        const ip = getIP();
        imgEl.src = streamUrl(ip);
        setStatus('STREAM', 'ok');

        // Sync canvas size với image
        imgEl.onload = () => {
          canvasEl.width = imgEl.width;
          canvasEl.height = imgEl.height;
        };
      } catch {}
    }

    // Vẽ khung nhận diện đơn giản
    function drawFaceBoxes(faces) {
      ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

      if (!faces || faces.length === 0) return;

      // Tính tỷ lệ scale
      const scaleX = canvasEl.width / imgEl.naturalWidth;
      const scaleY = canvasEl.height / imgEl.naturalHeight;

      ctx.save();
      ctx.lineWidth = 3;
      ctx.font = 'bold 16px Arial';

      faces.forEach((face) => {
        const [x1, y1, x2, y2] = face.box;
        const matched = face.matched;
        const name = face.name || 'unknown';
        const confidence = face.confidence || 0;

        // Scale tọa độ
        const sx1 = x1 * scaleX;
        const sy1 = y1 * scaleY;
        const sx2 = x2 * scaleX;
        const sy2 = y2 * scaleY;
        const w = sx2 - sx1;
        const h = sy2 - sy1;

        // Màu sắc
        const color = matched ? '#10b981' : '#ef4444';
        const bgColor = matched ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)';
        const label = matched ? `✓ ${name} (${confidence}%)` : `✗ Unknown`;

        // Vẽ khung
        ctx.strokeStyle = color;
        ctx.strokeRect(sx1, sy1, w, h);

        // Vẽ label
        const textWidth = ctx.measureText(label).width;
        const padding = 8;

        ctx.fillStyle = color;
        ctx.fillRect(sx1, sy1 - 30, textWidth + padding * 2, 30);

        ctx.fillStyle = '#ffffff';
        ctx.fillText(label, sx1 + padding, sy1 - 8);

        // Overlay
        ctx.fillStyle = bgColor;
        ctx.fillRect(sx1, sy1, w, h);
      });

      ctx.restore();
    } // Bật/tắt chế độ real-time face detection
    async function toggleStreamFaceDetect() {
      if (isStreamingFaceDetect) {
        stopStreamFaceDetect();
      } else {
        startStreamFaceDetect();
      }
    }

    function startStreamFaceDetect() {
      if (isStreamingFaceDetect) return;

      console.log('🚀 Starting ULTRA-FAST stream face TRACKING...');
      isStreamingFaceDetect = true;
      trackingMode = 'yellow';
      btnFaceDetect.textContent = '⏹️ Tắt tracking';
      btnFaceDetect.style.background = '#ef4444'; // Đỏ khi đang tracking

      // Gọi API tracking cực nhanh
      async function detectLoop() {
        if (!isStreamingFaceDetect) return;

        // Bỏ qua nếu request trước chưa xong
        if (detectInProgress) {
          if (isStreamingFaceDetect) {
            faceDetectInterval = setTimeout(detectLoop, 30); // Retry nhanh
          }
          return;
        }

        detectInProgress = true;

        try {
          const ip = getIP();

          // Chọn API dựa theo mode
          let apiUrl;
          if (trackingMode === 'yellow') {
            // API SIÊU NHANH - Haar Cascade
            apiUrl = `${API_BASE}/face-detect-fast?ip=${encodeURIComponent(ip)}`;
          } else {
            // API đầy đủ với recognition
            apiUrl = `${API_BASE}/face-check?ip=${encodeURIComponent(ip)}`;
          }

          const faceRes = await fetchJsonSafe(apiUrl);

          if (faceRes.ok && faceRes.faces) {
            lastFaceData = faceRes;
            drawFaceBoxes(faceRes.faces, trackingMode);

            const faceCount = faceRes.faces.length;
            if (trackingMode === 'yellow') {
              setStatus(`🎯 ${faceCount} faces [${faceRes.latency_ms}ms]`, 'warn');
            } else {
              const matchedCount = faceRes.faces.filter(f => f.matched).length;
              const avgConf = faceRes.faces.length > 0 ?
                Math.round(faceRes.faces.reduce((sum, f) => sum + (f.confidence || 0), 0) / faceRes.faces.length) :
                0;
              setStatus(`✅ ${matchedCount}/${faceCount} (${avgConf}%) [${faceRes.latency_ms}ms]`, 'ok');
            }

            // Auto-switch to recognition nếu phát hiện khuôn mặt mới trong tracking mode
            if (trackingMode === 'yellow' && faceCount > 0 && !recognitionCooldown) {
              // Trigger auto-recognition sau 2 giây tracking ổn định
              if (!recognitionTimeout) {
                recognitionTimeout = setTimeout(() => {
                  if (isStreamingFaceDetect && trackingMode === 'yellow') {
                    performAutoRecognition();
                  }
                }, 2000);
              }
            }
          } else {
            drawFaceBoxes([]);
            setStatus('No face', 'warn');
            // Clear auto-recognition timeout nếu mất mặt
            if (recognitionTimeout) {
              clearTimeout(recognitionTimeout);
              recognitionTimeout = null;
            }
          }
        } catch (e) {
          console.warn('Face detect error:', e);
        } finally {
          detectInProgress = false;
        }

        // SIÊU NHANH: 50ms cho tracking (20 FPS), 300ms cho recognition
        const interval = trackingMode === 'yellow' ? 50 : 300;
        if (isStreamingFaceDetect) {
          faceDetectInterval = setTimeout(detectLoop, interval);
        }
      }

      detectLoop();
    }

    function stopStreamFaceDetect() {
      isStreamingFaceDetect = false;
      trackingMode = 'yellow';
      btnFaceDetect.textContent = '🎯 Bật tracking';
      btnFaceDetect.style.background = '#16a34a';

      if (faceDetectInterval) {
        clearTimeout(faceDetectInterval);
        faceDetectInterval = null;
      }

      if (recognitionTimeout) {
        clearTimeout(recognitionTimeout);
        recognitionTimeout = null;
      }

      ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
      lastFaceData = null;
      setStatus('STREAM', 'ok');
    }

    // Hàm nhận diện (chuyển sang chế độ recognition)
    async function performRecognition() {
      if (!isStreamingFaceDetect) {
        // Nếu chưa bật tracking, bật luôn
        startStreamFaceDetect();
        await sleep(500); // Đợi 1 frame
      }

      console.log('🔍 Manual recognition triggered...');

      // Chuyển sang chế độ recognition
      trackingMode = 'recognition';
      btnRecognize.textContent = '⏳ Đang nhận diện...';
      btnRecognize.disabled = true;

      // Clear timeout cũ nếu có
      if (recognitionTimeout) {
        clearTimeout(recognitionTimeout);
      }

      // Sau 5 giây, quay lại chế độ tracking
      recognitionTimeout = setTimeout(() => {
        if (isStreamingFaceDetect) {
          console.log('⏱️ Back to tracking mode');
          trackingMode = 'yellow';
          btnRecognize.textContent = '🔍 Nhận diện';
          btnRecognize.disabled = false;
          recognitionCooldown = false;
        }
      }, 5000);

      recognitionCooldown = true;
      toast('Đang nhận diện khuôn mặt... (5s)');
    }

    // Auto-recognition khi tracking ổn định
    async function performAutoRecognition() {
      if (recognitionCooldown) return;

      console.log('🤖 Auto-recognition triggered...');
      trackingMode = 'recognition';
      recognitionCooldown = true;

      // Sau 3 giây, quay lại tracking
      setTimeout(() => {
        if (isStreamingFaceDetect) {
          trackingMode = 'yellow';
          recognitionCooldown = false;
        }
      }, 3000);
    }

    async function capture() {
      try {
        const ip = getIP();
        // Hiển thị khung từ ESP32 ngay lập tức
        imgEl.src = capUrl(ip);
        setStatus('CAPTURING…', 'warn');

        const j = await fetchJsonSafe(`${API_BASE}/esp32/capture?ip=${encodeURIComponent(ip)}`);
        if (j.ok && j.url) {
          imgEl.src = j.url; // xem ảnh đã lưu
          setTimeout(reloadCam, 1200); // quay lại stream sau 1.2s
          setStatus('SAVED', 'ok');
        } else {
          setTimeout(reloadCam, 800);
          setStatus(j.error || 'CAPTURE FAIL', 'err');
        }
      } catch (e) {
        console.error(e);
        toast('Lỗi chụp ảnh: ' + e.message);
        setTimeout(reloadCam, 800);
        setStatus('ERROR', 'err');
      }
    }

    async function ctrl(v, val) {
      try {
        const ip = getIP();
        await fetch(`${API_BASE}/esp32/ctrl?ip=${encodeURIComponent(ip)}&var=${encodeURIComponent(v)}&val=${encodeURIComponent(val)}`).catch(() => {});
        setStatus(`${v}=${val}`, 'ok');
      } catch {
        /* ignore UI toast đã có nơi khác */
      }
    }

    async function setLed(level) {
      return ctrl('led_intensity', level);
    }

    async function enroll() {
      try {
        const ip = getIP();
        setStatus('ENROLLING…', 'warn');
        for (let i = 0; i < 5; i++) {
          await ctrl('face_enroll', 1);
          await sleep(600);
        }
        toast('Đã gửi lệnh enroll (5 mẫu). Giữ mặt cố định khi đèn nháy.');
        setStatus('ENROLLED?', 'ok');
      } catch (e) {
        toast('Enroll lỗi: ' + e.message);
        setStatus('ERROR', 'err');
      }
    }

    async function detectFace() {
      try {
        const ip = getIP();
        setStatus('DETECTING…', 'warn');
        setSensorStatus('🔍 ĐANG NHẬN DIỆN', 'recognizing', 'detecting');

        // Bước 1: Gọi API nhận diện
        const faceRes = await fetchJsonSafe(`${API_BASE}/face-check?ip=${encodeURIComponent(ip)}`);

        if (!faceRes.ok) {
          toast('Lỗi nhận diện: ' + (faceRes.error || 'Unknown'));
          setStatus('DETECT ERR', 'err');
          return;
        }

        const faceCount = faceRes.faces ? faceRes.faces.length : 0;
        const matchedCount = faceRes.faces ? faceRes.faces.filter(f => f.matched).length : 0;

        if (faceCount === 0) {
          toast('Không phát hiện khuôn mặt nào');
          setStatus('NO FACE', 'warn');
          setTimeout(reloadCam, 1500);
          return;
        }

        // Bước 2: Lấy ảnh gốc
        const imgRes = await fetch(`${API_BASE}/esp32-capture?ip=${encodeURIComponent(ip)}`);
        const imgBlob = await imgRes.blob();

        // Bước 3: Vẽ khung lên ảnh
        const boxesParam = encodeURIComponent(JSON.stringify(faceRes));
        const overlayRes = await fetch(`${API_BASE}/draw-overlay?boxes=${boxesParam}`, {
          method: 'POST',
          body: imgBlob,
          headers: {
            'Content-Type': 'image/jpeg'
          }
        });

        if (overlayRes.ok) {
          const overlayBlob = await overlayRes.blob();
          imgEl.src = URL.createObjectURL(overlayBlob);

          // Hiển thị panel kết quả
          showFaceResult(faceRes);

          // Hiển thị chi tiết trong status
          const names = faceRes.faces.map(f =>
            `${f.name}${f.matched ? '✅' : '❌'}`
          ).join(', ');
          setStatus(`DETECTED: ${names}`, 'ok');

          // Lưu log vào database (mark as web_manual)
          try {
            const matchedFace = faceRes.faces.find(f => f.matched);
            const status = matchedCount > 0 ? 'granted' : 'denied';
            const recognizedName = matchedFace ? matchedFace.name : null;
            const confidence = matchedFace ? matchedFace.confidence : null;

            await fetch(`${API_BASE}/access-log`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                device_id: 'DOOR-01',
                status: status,
                recognized_name: recognizedName,
                confidence: confidence,
                photo_url: null,
                source: 'web_manual' // CRITICAL: Mark as manual detection
              })
            }).catch(e => console.warn('Log save failed:', e));
          } catch (e) {
            console.warn('Failed to save log:', e);
          }

          // Hiển thị kết quả trên sensor status
          if (matchedCount > 0) {
            setSensorStatus(`✅ ${matchedCount} NGƯỜI`, 'detected', 'active');
            toast(`✅ Phát hiện ${faceCount} mặt, nhận diện ${matchedCount} người`, 3000);
          } else {
            setSensorStatus('❌ KHÔNG RÕ', 'err', '');
            toast(`⚠️ Phát hiện ${faceCount} mặt nhưng không nhận diện được`, 3000);
          }

          setTimeout(reloadCam, 5000); // Quay lại stream sau 5s
        } else {
          toast('Lỗi vẽ khung');
          setStatus('DRAW ERR', 'err');
          setTimeout(reloadCam, 1500);
        }

      } catch (e) {
        console.error(e);
        toast('Lỗi nhận diện: ' + e.message);
        setStatus('ERROR', 'err');
        setTimeout(reloadCam, 1500);
      }
    }

    function showFaceResult(data) {
      const panel = document.getElementById('face_result');
      const content = document.getElementById('face_result_content');

      let html = `<div style="margin-bottom: 12px;">
        <strong>Tổng số khuôn mặt:</strong> ${data.faces.length}<br>
        <strong>Thời gian xử lý:</strong> ${data.latency_ms || 'N/A'} ms
      </div>`;

      data.faces.forEach((f, i) => {
        const cls = f.matched ? 'matched' : 'unknown';
        const icon = f.matched ? '✅' : '❌';
        const label = f.matched ? 'Nhận diện' : 'Không rõ';

        html += `
          <div class="face-item ${cls}">
            <div class="face-item-icon">${icon}</div>
            <div class="face-item-info">
              <div class="face-item-name">${f.name} - ${label}</div>
              <div class="face-item-box">Box: [${f.box.join(', ')}]</div>
            </div>
          </div>
        `;
      });

      content.innerHTML = html;
      panel.style.display = 'block';
    }

    async function autoTick() {
      if (autoBusy) return;
      let ip;
      try {
        ip = getIP();
      } catch {
        return;
      } // invalid ip → bỏ qua lần này

      autoBusy = true;
      const thr = Math.max(0, Math.min(100, parseFloat(autoThr.value || '7.5')));
      const url = `${API_BASE}/esp32/auto-capture?ip=${encodeURIComponent(ip)}&thr=${thr}&delay=300&full=1`;

      try {
        const j = await fetchJsonSafe(url);

        if (!j.ok) {
          console.warn('auto_capture error:', j.error);
          setStatus('AUTO ERR', 'err');

          // Thiếu GD → chuyển sang simple mode 1 lần
          if (j.error && j.error.includes('GD')) {
            useSimple = true;
            toast('Máy chủ thiếu GD → chuyển simple mode');
          }
          return;
        }

        setStatus(`AUTO: ${j.score ?? 'N/A'}%`, 'ok');

        if (j.captured && j.url) {
          const prev = imgEl.src;
          imgEl.src = j.url;
          setTimeout(reloadCam, 1000);
        }
      } catch (e) {
        console.warn('auto_capture fetch fail:', e);
        setStatus('AUTO HTTP ERR', 'err');
      } finally {
        autoBusy = false;
      }
    }

    function startAuto() {
      if (autoTimer) return;
      const period = Math.max(300, parseInt(autoMs.value || '1000'));
      autoTimer = setInterval(autoTick, period);
      setStatus('AUTO ON', 'warn');
    }

    function stopAuto() {
      if (autoTimer) {
        clearInterval(autoTimer);
        autoTimer = null;
        setStatus('AUTO OFF', '');
      }
    }

    // ====== WIRE UI ======
    btnReload.addEventListener('click', reloadCam);
    btnCapture.addEventListener('click', () => disableDuring(btnCapture, capture()));
    btnEnroll.addEventListener('click', () => disableDuring(btnEnroll, enroll()));
    btnRecognize.addEventListener('click', () => disableDuring(btnRecognize, detectFace()));

    document.addEventListener('click', e => {
      const el = e.target;
      if (el.matches('[data-led]')) setLed(el.dataset.led | 0);
      if (el.matches('[data-ctrl]')) ctrl(el.dataset.ctrl, el.dataset.val);
    });

    autoOn.addEventListener('change', () => autoOn.checked ? startAuto() : stopAuto());
    autoMs.addEventListener('change', () => {
      if (autoOn.checked) {
        stopAuto();
        startAuto();
      }
    });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopAuto();
        stopSensorPolling();
      } else {
        if (autoOn.checked) startAuto();
        startSensorPolling();
      }
    });

    // ====== BOOT ======
    reloadCam();
    startSensorPolling(); // Bật sensor polling khi tải trang
  })();
</script>