<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>CAMERA ESP32-CAM</title>
</head>

<body>
 <div class="container">
  <div class="header">
   <div class="header-left">
    <div class="logo">📷</div>
    <div class="title-section">
     <h1>CAMERA ESP32-CAM</h1>
     <p>Advanced Web Server Panel</p>
    </div>
   </div>
   <div class="header-right">
    <div class="status-badge pir-sensor">🎛️ Cảm biến </div>
   </div>
  </div>

  <div class="main-content">
   <div class="card esp32cam">
    <div class="row">
     <div class="ipaddress">
      <span>📡 IP Address</span>
      <input id="esp_ip" value="10.80.115.74" placeholder="ESP32 IP...">
     </div>

     <label class="chk">
      <input id="auto_on" type="checkbox">
      <span>Tự chụp khi có người (motion)</span>
     </label>

     <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
      <label class="inline">
       Thr %
       <input id="auto_thr" type="number" step="0.5" value="5" title="Ngưỡng % khác biệt">
      </label>

      <label class="inline">
       Chu kỳ (ms)
       <input id="auto_ms" type="number" step="100" value="500" title="Chu kỳ ms">
      </label>
     </div>

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

     <div class="row">
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 90px;">
       <button id="btn_reload" class="btn">Tải lại</button>
       <button id="btn_capture" class="btn">📸 Chụp ảnh</button>
       <button id="btn_recognize" class="btn btn-recognize" style="background:#10b981">🔍 Nhận diện khuôn mặt</button>
      </div>
     </div>

     <!-- Panel kết quả nhận diện -->
     <div id="face_result" class="face-result" style="display:none;">
      <div class="face-result-header">
       <span>🎯 Kết quả nhận diện</span>
       <button class="face-result-close"
        onclick="document.getElementById('face_result').style.display='none'">✕</button>
      </div>
      <div id="face_result_content"></div>
     </div>
    </div>
   </div>

   <div class="right-panel">
    <div class="card">
     <h2 class="card-title">💡 Điều khiển LED</h2>
     <div class="control-section">
      <div class="toggle-group">
       <button class="toggle-btn" data-led="255">💡 Bật LED</button>
       <button class="toggle-btn active" data-led="0">💡 Tắt LED</button>
      </div>
     </div>
    </div>

    <div class="card">
     <h2 class="card-title">👁️ Phát hiện khuôn mặt</h2>
     <div class="control-section">
      <div class="toggle-group">
       <button class="action-btn on" data-ctrl="face_detect" data-val="1">✓ ON</button>
       <button class="action-btn off" data-ctrl="face_detect" data-val="0">✕ OFF</button>
      </div>
     </div>
    </div>

    <div class="card">
     <h2 class="card-title">👤 Nhận diện khuôn mặt</h2>
     <div class="control-section">
      <div class="toggle-group">
       <button class="action-btn on" data-ctrl="face_recognize" data-val="1">✓ ON</button>
       <button class="action-btn off">✕ OFF</button>
      </div>
      <button class="action-btn enroll-btn" id="btn_enroll data-ctrl=" face_recognize" data-val="0">👥 Enroll Face
       x5</button>
     </div>
    </div>


    <div class="card">
     <h2 class="card-title">ℹ️ Thông tin hệ thống</h2>
     <div class="info-card">
      <div class="info-item">
       <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
       </svg>
       <div class="info-content">
        <div class="info-label">Ghi chú:</div>
        <div class="info-text"> Enroll thường cần nhấn vài lần (mỗi lần chụp thêm mẫu - 5-10 lần)</div>
       </div>
      </div>
      <div class="info-item">
       <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
       </svg>
       <div class="info-content">
        <div class="info-label">Stream MJPEG:</div>
        <div class="info-text"><code>http://IP:81/stream</code>. Ảnh lưu tại <code>public/uploads/</code></div>
       </div>
      </div>
      <div class="info-item">
       <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
       </svg>
       <div class="info-content">
        <div class="info-label">Nhận diện:</div>
        <div class="info-text">Nhấn nút "Nhận diện khuôn mặt" để AI phát hiện và nhận diện</div>
       </div>
      </div>
      <div class="info-item">
       <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
       </svg>
       <div class="info-content">
        <div class="info-label">Kết quả:</div>
        <div class="info-text">🟢 Khung XANH + Tên + % = Đúng | 🔴 Khung ĐỎ + Unknown = Sai hoặc chưa Enroll</div>
       </div>
      </div>
      <div class="info-item">
       <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
       </svg>
       <div class="info-content">
        <div class="info-label">Tips:</div>
        <div class="info-text">Ánh sáng đều, mặt chính diện, khoảng cách 0.5-2m. Tolerance: 0.8 (dễ nhận diện)</div>
       </div>
      </div>
      <div class="info-item">
       <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
       </svg>
       <div class="info-content">
        <div class="info-label">📡 Trạng thái:</div>
        <div class="info-text">Frontend hiển thị kết quả realtime từ database (cập nhật mỗi giây)</div>
       </div>
      </div>
      <div class="info-item alert">
       <svg class="info-icon alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
       </svg>
       <div class="info-content">
        <div class="info-label">Cảm biến LM393:</div>
        <div class="info-text">TỰ ĐỘNG hoạt động! ESP32 tự chụp và nhận diện khi phát hiện chuyển động.</div>
       </div>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
</body>

<style>
* {
 margin: 0;
 padding: 0;
 box-sizing: border-box;
}

body {
 font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
 background: linear-gradient(135deg, #9dbaf4ff 0%, #d1a5fcff 100%);
 min-height: 100vh;
 padding: 20px;
}

.container {
 max-width: 1400px;
 margin: 0 auto;
}

.header {
 background: white;
 border-radius: 16px;
 padding: 8px 32px;
 box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
 margin-bottom: 24px;
 display: flex;
 justify-content: space-between;
 align-items: center;
}

.header-left {
 display: flex;
 align-items: center;
 gap: 16px;
}

.logo {
 width: 55px;
 height: 55px;
 background: linear-gradient(135deg, #5eead4, #3b82f6);
 border-radius: 12px;
 display: flex;
 align-items: center;
 justify-content: center;
 font-size: 32px;
}

.title-section h1 {
 font-size: 20px;
 color: #1f2937;
 margin-bottom: 4px;
}

.title-section p {
 color: #6b7280;
 font-size: 14px;
}

.header-right {
 display: flex;
 gap: 12px;
}

.status-badge {
 padding: 10px 24px;
 border-radius: 24px;
 font-weight: 600;
 font-size: 14px;
 display: flex;
 align-items: center;
 gap: 8px;
}

.offline {
 background: #fee2e2;
 color: #dc2626;
}

.offline::before {
 content: '';
 width: 8px;
 height: 8px;
 background: #dc2626;
 border-radius: 50%;
}

.pir-sensor {
 background: #10b981;
 color: white;
}

.main-content {
 display: grid;
 grid-template-columns: 1fr 420px;
 gap: 24px;
 align-items: start;
}

.card {
 background: white;
 border-radius: 16px;
 padding: 24px;
 box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
 height: fit-content;
}

.card-title {
 font-size: 20px;
 font-weight: 700;
 color: #1f2937;
 margin-bottom: 20px;
 display: flex;
 align-items: center;
 gap: 8px;
}

.row {
 display: flex;
 flex-direction: column;
 gap: 12px;
 margin-bottom: 20px;
}

.title {
 font-size: 20px;
 font-weight: 700;
 color: #1f2937;
 display: flex;
 align-items: center;
 gap: 8px;
}

input[type="text"],
input[type="number"] {
 padding: 3px 12px;
 border: 2px solid #e5e7eb;
 border-radius: 8px;
 font-size: 14px;
 width: 100%;
}

input:focus {
 outline: none;
 border-color: #3b82f6;
}

.inline {
 display: flex;
 align-items: center;
 gap: 8px;
 font-size: 14px;
 font-weight: 500;
 color: #374151;
}

.inline input {
 width: 100px;
}

.ipaddress {
 display: flex;
 align-items: center;
 gap: 8px;
 font-size: 14px;
 color: #374151;
}

.ipaddress input {
 width: 128px;
 height: 27px;
 border-radius: 8px;
 border: 2px solid #e5e7eb;
}

.chk {
 display: flex;
 align-items: center;
 gap: 8px;
 font-size: 14px;
 color: #374151;
 cursor: pointer;
}

.chk input {
 width: 16px;
 height: 16px;
 cursor: pointer;
}

.badge {
 display: inline-block;
 padding: 6px 16px;
 border-radius: 20px;
 font-size: 13px;
 font-weight: 600;
 background: #f3f4f6;
 color: #6b7280;
}

.sensor-status {
 display: flex;
 align-items: center;
 gap: 12px;
 padding: 12px;
 background: #f0fdf4;
 border-radius: 10px;
 border: 2px solid #10b981;
}

.sensor-icon {
 font-size: 24px;
}

.sensor-info {
 display: flex;
 flex-direction: column;
 gap: 4px;
}

.sensor-label {
 font-size: 14px;
 font-weight: 600;
 color: #065f46;
}

.sensor-badge {
 background: #10b981;
 color: white;
 padding: 4px 12px;
 font-size: 11px;
}

.content {
 display: flex;
 flex-direction: column;
 gap: 16px;
}

.stream-wrapper {
 position: relative;
 background: #1f2937;
 border-radius: 12px;
 aspect-ratio: 16/9;
 max-height: 350px;
 display: flex;
 align-items: center;
 justify-content: center;
 overflow: hidden;
}

#cam {
 width: 100%;
 height: 100%;
 object-fit: contain;
}

#cam_canvas {
 position: absolute;
 top: 0;
 left: 0;
 width: 100%;
 height: 100%;
}

.face-result {
 background: #f8fafc;
 border-radius: 12px;
 padding: 16px;
 border: 2px solid #e2e8f0;
}

.face-result-header {
 display: flex;
 justify-content: space-between;
 align-items: center;
 margin-bottom: 12px;
 font-weight: 600;
 color: #1e293b;
}

.face-result-close {
 background: none;
 border: none;
 font-size: 18px;
 cursor: pointer;
 color: #64748b;
}

.controls {
 display: flex;
 flex-direction: column;
 gap: 16px;
}

.grid {
 display: grid;
 grid-template-columns: repeat(2, 1fr);
 gap: 10px;
}

.btn {
 padding: 10px 16px;
 border: none;
 border-radius: 12px;
 font-weight: 600;
 font-size: 13px;
 cursor: pointer;
 transition: all 0.3s;
 display: flex;
 align-items: center;
 justify-content: center;
 gap: 6px;
 background: #3b82f6;
 color: white;
}

.btn:hover {
 transform: translateY(-2px);
 box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}


#btn_enroll {
 background: #10b981;
 grid-column: 1 / -1;
}

#btn_reload {
 background: #8b5cf6;
}

#btn_capture {
 background: #3b82f6;
}


.note {
 background: #f8fafc;
 padding: 16px;
 border-radius: 10px;
 font-size: 13px;
 line-height: 1.8;
 color: #475569;
}

.note b {
 color: #1e293b;
}

.note code {
 background: #e2e8f0;
 padding: 2px 6px;
 border-radius: 4px;
 font-size: 12px;
 color: #3b82f6;
}

.note strong {
 color: #dc2626;
}

.right-panel {
 display: flex;
 flex-direction: column;
 gap: 24px;
}

.control-section {
 display: flex;
 flex-direction: column;
 gap: 12px;
}

.toggle-group {
 display: grid;
 grid-template-columns: 1fr 1fr;
 gap: 12px;
}

.toggle-btn {
 padding: 12px 20px;
 border: 2px solid #e5e7eb;
 border-radius: 10px;
 background: white;
 font-weight: 600;
 cursor: pointer;
 transition: all 0.3s;
}

.toggle-btn.active {
 background: #334155;
 color: white;
 border-color: #334155;
}

.toggle-btn:hover:not(.active) {
 border-color: #cbd5e1;
 background: #f8fafc;
}

.action-btn {
 width: 100%;
 padding: 14px;
 border: 2px solid #e5e7eb;
 border-radius: 10px;
 background: white;
 font-weight: 600;
 cursor: pointer;
 transition: all 0.3s;
 display: flex;
 align-items: center;
 justify-content: center;
 gap: 8px;
}

.action-btn.on {
 background: white;
 color: #10b981;
 border-color: #10b981;
}

.action-btn.off {
 background: #ef4444;
 color: white;
 border-color: #ef4444;
}

.enroll-btn {
 background: #10b981;
 color: white;
 border: none;
}

.info-card {
 background: #f8fafc;
 border-radius: 12px;
 padding: 16px;
}

.info-item {
 display: flex;
 align-items: flex-start;
 gap: 12px;
 margin-bottom: 16px;
 padding: 12px;
 background: white;
 border-radius: 8px;
}

.info-item:last-child {
 margin-bottom: 0;
}

.info-item.alert {
 background: #fef2f2;
 border-left: 4px solid #ef4444;
}

.info-icon {
 width: 20px;
 height: 20px;
 color: #3b82f6;
 flex-shrink: 0;
}

.alert-icon {
 color: #ef4444;
}

.info-content {
 flex: 1;
}

.info-label {
 font-weight: 600;
 color: #1f2937;
 margin-bottom: 4px;
}

.info-text {
 font-size: 14px;
 color: #6b7280;
 line-height: 1.5;
}

.info-link {
 color: #3b82f6;
 text-decoration: none;
 word-break: break-all;
}

@media (max-width: 1200px) {
 .main-content {
  grid-template-columns: 1fr;
 }
}
</style>

<script>
(() => {
 // ====== CONFIG ======
 const API_BASE = 'http://localhost:5000/api';
 const SEL = s => document.querySelector(s);

 // DOM Elements
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

 // State variables
 let sensorTimer = null;
 let lastLogId = null;
 let autoBusy = false;
 let autoTimer = null;
 let sensorPopupTimer = null;


 // ====== UTILITY FUNCTIONS ======
 const sleep = ms => new Promise(r => setTimeout(r, ms));

 function toast(msg, ms = 2500) {
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), ms);
 }

 function setStatus(text, cls = '') {
  statusEl.textContent = text;
  statusEl.className = `badge ${cls}`;
 }

 function setSensorStatus(text, badgeCls = '', containerCls = '') {
  sensorStatusEl.textContent = text;
  sensorStatusEl.className = `badge sensor-badge ${badgeCls}`;
  sensorContainer.className = `sensor-status ${containerCls}`;
 }

 function getIP() {
  const ip = ipEl.value.trim();
  if (!ip || !/^[0-9.]+$/.test(ip)) {
   toast('IP không hợp lệ');
   throw new Error('Invalid IP');
  }
  return ip;
 }

 function disableDuring(el, promise) {
  el.disabled = true;
  return promise.finally(() => el.disabled = false);
 }

 async function fetchJsonSafe(url, opts = {}) {
  const res = await fetch(url, {
   cache: 'no-store',
   ...opts
  });
  if (!res.ok) {
   const raw = await res.text().catch(() => '');
   throw new Error(`HTTP ${res.status} @ ${url}\n${raw.slice(0, 200)}`);
  }
  const ct = res.headers.get('content-type') || '';
  if (ct.includes('application/json')) return res.json();
  const txt = await res.text();
  try {
   return JSON.parse(txt);
  } catch {
   throw new Error(`Expected JSON but got: ${txt.slice(0, 200)}`);
  }
 }

 // ====== SENSOR (LM393) FUNCTIONS ======
 async function checkIrState() {
  try {
   const res = await fetchJsonSafe(`${API_BASE}/ir-state`);
   const state = (res.state || '').toLowerCase();

   if (state === 'detecting') {
    // LM393 đang sáng → ESP32 đang chụp / gửi ảnh
    setSensorStatus('🔍 ĐANG NHẬN DIỆN...', 'recognizing', 'detecting');
    sensorIcon.textContent = '🔍';
   } else {
    // Mặc định: waiting
    setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
    sensorIcon.textContent = '📡';
   }
  } catch (e) {
   console.warn('IR state error:', e);
   setSensorStatus('⚠️ OFFLINE', 'err', '');
   sensorIcon.textContent = '📡';
  }
 }

 // 2) Đọc log mới nhất để HIỂN THỊ KẾT QUẢ (toast + popup) – KHÔNG đụng vào badge
 // Đọc log mới nhất để HIỂN THỊ KẾT QUẢ (toast + popup) – KHÔNG đụng vào <img id="cam">
 // Đọc log mới nhất để hiện popup giống nút "Nhận diện khuôn mặt"
 async function checkSensorLog() {
  try {
   const res = await fetchJsonSafe(`${API_BASE}/logs?limit=1`);

   // Không có dữ liệu
   if (!res.data || res.data.length === 0) return;

   const latest = res.data[0];

   // Lần đầu load trang: chỉ ghi nhớ id, KHÔNG hiển thị log cũ
   if (lastLogId === null) {
    lastLogId = latest.id;
    return;
   }

   // Không có log mới
   if (latest.id <= lastLogId) return;
   lastLogId = latest.id;

   // Chỉ quan tâm log tự động từ ESP32 (LM393)
   if ((latest.source || '') !== 'esp32_auto') return;

   const ok = latest.status === 'granted';
   const name = latest.recognized_name || 'Unknown';
   const conf = Math.round(latest.confidence || 0);

   // 👉 Tạo fake result giống như /face-check trả về
   const fakeResult = {
    faces: [{
     name: ok ? name : 'Unknown',
     matched: ok,
     confidence: conf,
     box: [0, 0, 100, 100] // box giả cho UI, không ảnh hưởng gì
    }],
    latency_ms: 'ESP32 Auto'
   };

   // Hiện panel kết quả
   showFaceResult(fakeResult);

   // Cập nhật badge cảm biến
   if (ok) {
    setSensorStatus(`✅ ${name} (${conf}%)`, 'detected', 'active');
    sensorIcon.textContent = '✅';
    toast(`✅ Cho phép: ${name} (${conf}%)`, 3000);
   } else {
    setSensorStatus('❌ Unknown', 'err', 'detecting');
    sensorIcon.textContent = '❌';
    toast('❌ Không nhận diện được', 3000);
   }

   // Nếu trước đó đã có timer thì huỷ
   if (sensorPopupTimer) {
    clearTimeout(sensorPopupTimer);
    sensorPopupTimer = null;
   }

   // Sau 4 giây tự ẩn popup + reset trạng thái cảm biến
   sensorPopupTimer = setTimeout(() => {
    hideFaceResultPanel();
   }, 4000);

  } catch (e) {
   console.warn('Sensor log error:', e);
  }
 }


 function hideFaceResultPanel() {
  const panel = document.getElementById('face_result');
  panel.style.display = 'none';
  // Xoá bất kỳ vẽ vời nào trên canvas
  ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
  // Cho sensor về trạng thái chờ
  setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
  sensorIcon.textContent = '📡';
 }

 // 3) Polling chung cho LM393: IR state + log
 function startSensorPolling() {
  if (sensorTimer) return;

  console.log('🚀 Start LM393 sensor polling (IR state + logs)…');
  // Khi bắt đầu: hiển thị đang kết nối
  setSensorStatus('🔄 ĐANG KẾT NỐI...', '', '');
  sensorIcon.textContent = '📡';

  // Gộp 2 việc vào một interval
  const loop = () => {
   checkIrState(); // trạng thái ĐANG CHỜ / ĐANG NHẬN DIỆN / OFFLINE
   checkSensorLog(); // nếu có log mới từ ESP32 → popup/toast
  };

  sensorTimer = setInterval(loop, 800);
  loop(); // chạy lần đầu ngay lập tức
 }

 function stopSensorPolling() {
  if (!sensorTimer) return;
  clearInterval(sensorTimer);
  sensorTimer = null;
  console.log('⏸️ Stop LM393 sensor polling');
  setSensorStatus('⏸️ TẠM DỪNG', 'idle', '');
  sensorIcon.textContent = '📡';
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

 // ====== CORE CAMERA FUNCTIONS ======
 function reloadCam() {
  try {
   const ip = getIP();
   imgEl.src = `http://${ip}:81/stream`;
   setStatus('STREAM', 'ok');
   imgEl.onload = () => {
    canvasEl.width = imgEl.width;
    canvasEl.height = imgEl.height;
   };
  } catch (e) {
   console.warn('Reload cam failed:', e.message);
  }
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
 }

 // Vẽ viền + text khi auto từ LM393, KHÔNG đổi src của <img>
 function drawAutoOverlayOnCanvas(matched, name, confidence) {
  // Đồng bộ kích thước canvas với ảnh hiện tại
  canvasEl.width = imgEl.clientWidth || imgEl.naturalWidth || canvasEl.width;
  canvasEl.height = imgEl.clientHeight || imgEl.naturalHeight || canvasEl.height;

  ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

  const color = matched ? '#10b981' : '#ef4444';
  const label = matched ?
   `✅ ${name} (${confidence}%)` :
   '❌ Unknown';

  ctx.save();
  ctx.strokeStyle = color;
  ctx.lineWidth = 6;

  // Vẽ khung quanh toàn bộ khung hình cho dễ nhìn
  ctx.strokeRect(3, 3, canvasEl.width - 6, canvasEl.height - 6);

  // Vẽ text ở góc trên
  ctx.font = 'bold 20px Arial';
  ctx.fillStyle = color;
  ctx.fillText(label, 15, 35);
  ctx.restore();
 }


 // Hiển thị popup + viền ảnh cho log tự động từ ESP32 (LM393)
 function showAutoDetectionFromLog(latest) {
  const name = latest.recognized_name || 'Unknown';
  const conf = Math.round(latest.confidence || 0);
  const matched = latest.status === 'granted';
  const photoUrl = latest.photo_url;

  if (!photoUrl) return; // không có ảnh thì thôi

  // Ảnh do Flask lưu khi ESP32 gửi lên
  const imageUrl = `http://localhost:5000${photoUrl}`;
  imgEl.src = imageUrl;

  // Dùng chung panel "Kết quả nhận diện" như nút bấm
  const fakeResult = {
   ok: true,
   latency_ms: 'ESP32 Auto',
   faces: [{
    name: name,
    matched: matched,
    confidence: conf,
    box: [0, 0, 100, 100] // box giả, chỉ để hiện text trong panel
   }]
  };
  showFaceResult(fakeResult);

  // Vẽ viền quanh ảnh để biết đúng / sai
  imgEl.onload = () => {
   canvasEl.width = imgEl.width;
   canvasEl.height = imgEl.height;
   ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

   ctx.strokeStyle = matched ? '#10b981' : '#ef4444'; // xanh = đúng, đỏ = sai
   ctx.lineWidth = 6;
   ctx.strokeRect(3, 3, canvasEl.width - 6, canvasEl.height - 6);

   ctx.font = 'bold 20px Arial';
   ctx.fillStyle = matched ? '#10b981' : '#ef4444';
   const text = matched ? `✅ ${name} (${conf}%)` : '❌ Unknown';
   ctx.fillText(text, 15, 35);
  };

  // 5s sau quay về stream
  setTimeout(reloadCam, 5000);
 }

 async function capture() {
  try {
   const ip = getIP();
   imgEl.src = `http://${ip}/capture`;
   setStatus('CAPTURING…', 'warn');

   const res = await fetchJsonSafe(`${API_BASE}/esp32/capture?ip=${encodeURIComponent(ip)}`);
   if (res.ok && res.url) {
    imgEl.src = res.url;
    setTimeout(reloadCam, 1200);
    setStatus('SAVED', 'ok');
   } else {
    setTimeout(reloadCam, 800);
    setStatus(res.error || 'CAPTURE FAIL', 'err');
   }
  } catch (e) {
   toast('Lỗi chụp ảnh: ' + e.message);
   setTimeout(reloadCam, 800);
   setStatus('ERROR', 'err');
  }
 }

 async function ctrl(v, val) {
  try {
   const ip = getIP();
   await fetch(
    `${API_BASE}/esp32/ctrl?ip=${encodeURIComponent(ip)}&var=${encodeURIComponent(v)}&val=${encodeURIComponent(val)}`
   ).catch(() => {});
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
  try {
   const ip = getIP();
   autoBusy = true;
   const thr = Math.max(0, Math.min(100, parseFloat(autoThr.value || '5')));
   const url = `${API_BASE}/esp32/auto-capture?ip=${encodeURIComponent(ip)}&thr=${thr}&delay=300&full=1`;

   const res = await fetchJsonSafe(url);
   if (res.ok) {
    setStatus(`AUTO: ${res.score ?? 'N/A'}%`, 'ok');
    if (res.captured && res.url) {
     imgEl.src = res.url;
     setTimeout(reloadCam, 1000);
    }
   } else {
    console.warn('auto_capture error:', res.error);
    setStatus('AUTO ERR', 'err');
   }
  } catch (e) {
   console.warn('auto_capture failed:', e.message);
   setStatus('AUTO ERR', 'err');
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