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
        <div id="door_status" class="status-badge door-closed">
          🚪 CỬA ĐÓNG
        </div>
      </div>
    </div>

    <div class="main-content">
      <div class="card esp32cam">
        <div class="row">
          <div class="ipaddress">
            <span>📡 IP Address</span>
            <input id="esp_ip" value="10.87.241.74" placeholder="ESP32 IP...">
          </div>

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

          <div class="controls">
            <div class="grid">
              <button id="btn_reload" class="btn">🔄 Tải lại</button>
              <button id="btn_recognize" class="btn" style="background: linear-gradient(135deg, #10b981, #059669);">🔍 Nhận diện</button>
            </div>
            <button id="btn_emergency_unlock" class="btn btn-emergency">
              🚨 MỞ KHÓA NGAY
            </button>
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
        <!-- Điều khiển LED -->
        <div class="card">
          <h2 class="card-title">💡 Điều khiển Đèn Flash</h2>
          <div class="control-section">
            <div class="led-control">
              <button id="btn_led_on" class="btn btn-led">💡 BẬT ĐÈN</button>
              <button id="btn_led_off" class="btn btn-led">🌙 TẮT ĐÈN</button>
            </div>
          </div>
        </div>

        <!-- Thêm khuôn mặt -->
        <div class="card">
          <h2 class="card-title">➕ Thêm khuôn mặt mới</h2>
          <div class="control-section">
            <div class="add-face-form">
              <input type="text" id="new_face_name" placeholder="Nhập tên của bạn..." class="face-name-input">
              <button class="action-btn add-face-btn" id="btn_add_face">📸 Chụp và Lưu Khuôn Mặt</button>
              <div id="add_face_status" class="add-face-status"></div>
            </div>
          </div>
        </div>

        <!-- Danh sách khuôn mặt -->
        <div class="card">
          <h2 class="card-title">👥 Quản lý Khuôn mặt</h2>
          <div id="face_list" class="face-list">
            <div class="loading">Đang tải...</div>
          </div>
        </div>

        <!-- Lịch sử nhận diện -->
        <div class="card">
          <h2 class="card-title">📜 Lịch sử Nhận diện</h2>
          <div id="history_list" class="history-list">
            <div class="loading">Đang tải...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal quản lý ảnh -->
  <div id="image_modal" class="modal" style="display:none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📸 Quản lý Ảnh - <span id="modal_face_name"></span></h2>
        <button class="modal-close" onclick="closeImageModal()">✕</button>
      </div>
      <div class="modal-body">
        <div id="image_gallery" class="image-gallery">
          <!-- Images will be loaded here -->
        </div>
        <div class="image-upload-section">
          <h3>➕ Thêm ảnh mới từ thiết bị</h3>
          <input type="file" id="upload_image" accept="image/*" multiple class="file-input">
          <button id="btn_upload_images" class="btn btn-upload">📤 Upload Ảnh</button>
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    min-height: 100vh;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
  }

  body::before {
    content: '';
    position: fixed;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: moveBackground 20s linear infinite;
    pointer-events: none;
    z-index: 0;
  }

  @keyframes gradientShift {

    0%,
    100% {
      background-position: 0% 50%;
    }

    50% {
      background-position: 100% 50%;
    }
  }

  @keyframes moveBackground {
    0% {
      transform: translate(0, 0);
    }

    100% {
      transform: translate(50px, 50px);
    }
  }

  .container {
    max-width: 1400px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  .header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 8px 32px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(255, 255, 255, 0.3);
    animation: slideDown 0.6s ease-out;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .logo {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    animation: float 3s ease-in-out infinite;
    position: relative;
  }

  .logo::after {
    content: '';
    position: absolute;
    inset: -2px;
    background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
    border-radius: 15px;
    opacity: 0;
    z-index: -1;
    transition: opacity 0.3s;
  }

  .logo:hover::after {
    opacity: 1;
  }

  @keyframes float {

    0%,
    100% {
      transform: translateY(0px);
    }

    50% {
      transform: translateY(-8px);
    }
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
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .status-badge::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
  }

  .status-badge:hover::before {
    width: 300px;
    height: 300px;
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
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    animation: pulse 2s ease-in-out infinite;
  }

  @keyframes pulse {

    0%,
    100% {
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    50% {
      box-shadow: 0 4px 25px rgba(16, 185, 129, 0.7);
    }
  }

  .main-content {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 24px;
    align-items: start;
  }

  .card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
    height: fit-content;
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
    animation: fadeInUp 0.8s ease-out;
  }

  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .card-title {
    font-size: 22px;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    padding-bottom: 12px;
  }

  .card-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
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
    border-radius: 10px;
    font-size: 14px;
    width: 100%;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
  }

  input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
    transform: translateY(-1px);
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
    padding: 14px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-radius: 12px;
    border: 2px solid #10b981;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
    transition: all 0.3s ease;
    animation: sensorGlow 2s ease-in-out infinite;
  }

  @keyframes sensorGlow {

    0%,
    100% {
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
    }

    50% {
      box-shadow: 0 4px 25px rgba(16, 185, 129, 0.4);
    }
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
    border-radius: 16px;
    aspect-ratio: 16/9;
    max-height: 350px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 3px solid transparent;
    background-image: linear-gradient(#1f2937, #1f2937), linear-gradient(135deg, #667eea, #764ba2, #f093fb);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
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

  .face-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
  }

  .face-item.matched {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border: 2px solid #10b981;
  }

  .face-item.unknown {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border: 2px solid #ef4444;
  }

  .face-item-icon {
    font-size: 28px;
    min-width: 40px;
    text-align: center;
  }

  .face-item-info {
    flex: 1;
  }

  .face-item-name {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .face-item-box {
    font-size: 12px;
    color: #6b7280;
    font-family: 'Courier New', monospace;
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
    padding: 12px 18px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  }

  .btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.5s, height 0.5s;
  }

  .btn:hover::before {
    width: 300px;
    height: 300px;
  }

  .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
  }

  .btn:active {
    transform: translateY(-1px);
  }


  #btn_enroll {
    background: linear-gradient(135deg, #10b981, #059669);
    grid-column: 1 / -1;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
  }

  #btn_enroll:hover {
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);
  }

  #btn_reload {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
  }

  #btn_reload:hover {
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.6);
  }

  #btn_capture {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
  }

  #btn_capture:hover {
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.6);
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
    border-radius: 12px;
    background: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .toggle-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s;
  }

  .toggle-btn:hover::before {
    left: 100%;
  }

  .toggle-btn.active {
    background: linear-gradient(135deg, #334155, #1e293b);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(51, 65, 85, 0.4);
    transform: scale(1.05);
  }

  .toggle-btn:hover:not(.active) {
    border-color: #667eea;
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

  .add-face-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .face-name-input {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    outline: none;
  }

  .face-name-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .add-face-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 14px 20px;
    font-weight: 600;
    cursor: pointer;
  }

  .add-face-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
  }

  .add-face-status {
    padding: 10px;
    border-radius: 8px;
    font-size: 13px;
    text-align: center;
    display: none;
  }

  .add-face-status.show {
    display: block;
  }

  .add-face-status.success {
    background: #d1fae5;
    color: #065f46;
  }

  .add-face-status.error {
    background: #fee2e2;
    color: #991b1b;
  }

  .add-face-status.info {
    background: #dbeafe;
    color: #1e40af;
  }

  /* Emergency Unlock Button */
  .btn-emergency {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    font-size: 18px !important;
    font-weight: 800 !important;
    padding: 18px 32px !important;
    box-shadow: 0 8px 30px rgba(239, 68, 68, 0.5) !important;
    animation: emergencyPulse 2s ease-in-out infinite;
    letter-spacing: 1px;
  }

  .btn-emergency:hover {
    box-shadow: 0 12px 40px rgba(239, 68, 68, 0.7) !important;
    transform: translateY(-4px) scale(1.02) !important;
  }

  @keyframes emergencyPulse {

    0%,
    100% {
      box-shadow: 0 8px 30px rgba(239, 68, 68, 0.5);
    }

    50% {
      box-shadow: 0 8px 40px rgba(239, 68, 68, 0.8);
    }
  }

  /* Door Status */
  .door-closed {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    animation: doorPulse 2s ease-in-out infinite;
  }

  .door-open {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
  }

  @keyframes doorPulse {

    0%,
    100% {
      opacity: 1;
    }

    50% {
      opacity: 0.8;
    }
  }

  /* LED Control */
  .led-control {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .btn-led {
    padding: 14px !important;
    font-size: 14px !important;
  }

  #btn_led_on {
    background: linear-gradient(135deg, #fbbf24, #f59e0b) !important;
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4) !important;
  }

  #btn_led_off {
    background: linear-gradient(135deg, #64748b, #475569) !important;
    box-shadow: 0 4px 15px rgba(100, 116, 139, 0.4) !important;
  }

  /* Face List */
  .face-list,
  .history-list {
    max-height: 400px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .face-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
  }

  .face-list-item:hover {
    border-color: #667eea;
    background: white;
    transform: translateX(4px);
  }

  .face-list-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #667eea;
  }

  .face-list-info {
    flex: 1;
  }

  .face-list-name {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .face-list-date {
    font-size: 12px;
    color: #6b7280;
  }

  .face-list-actions {
    display: flex;
    gap: 8px;
  }

  .btn-edit,
  .btn-delete {
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
  }

  .btn-edit {
    background: #3b82f6;
    color: white;
  }

  .btn-edit:hover {
    background: #2563eb;
    transform: translateY(-2px);
  }

  .btn-delete {
    background: #ef4444;
    color: white;
  }

  .btn-delete:hover {
    background: #dc2626;
    transform: translateY(-2px);
  }

  /* History List */
  .history-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
    margin-bottom: 8px;
  }

  .history-item.success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border-color: #10b981;
  }

  .history-item.failed {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-color: #ef4444;
  }

  .history-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #e5e7eb;
  }

  .history-info {
    flex: 1;
  }

  .history-name {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .history-time {
    font-size: 11px;
    color: #6b7280;
  }

  .history-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
  }

  .history-status.success {
    background: #10b981;
    color: white;
  }

  .history-status.failed {
    background: #ef4444;
    color: white;
  }

  .loading {
    text-align: center;
    padding: 20px;
    color: #6b7280;
  }

  /* Modal Styles */
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
  }

  .modal-content {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 800px;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
  }

  .modal-header {
    padding: 24px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-header h2 {
    font-size: 22px;
    margin: 0;
  }

  .modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
  }

  .modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
  }

  .modal-body {
    padding: 24px;
    max-height: calc(85vh - 80px);
    overflow-y: auto;
  }

  /* Image Gallery */
  .image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .image-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #e5e7eb;
    transition: all 0.3s;
    aspect-ratio: 1;
  }

  .image-item:hover {
    border-color: #667eea;
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  }

  .image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .image-delete {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    font-size: 16px;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .image-item:hover .image-delete {
    opacity: 1;
  }

  .image-delete:hover {
    background: #dc2626;
    transform: scale(1.1);
  }

  .image-count {
    position: absolute;
    bottom: 8px;
    left: 8px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
  }

  /* Upload Section */
  .image-upload-section {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    border: 2px dashed #cbd5e1;
  }

  .image-upload-section h3 {
    color: #1f2937;
    margin-bottom: 16px;
    font-size: 16px;
  }

  .file-input {
    display: block;
    width: 100%;
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    margin-bottom: 12px;
    font-size: 14px;
  }

  .file-input:hover {
    border-color: #667eea;
  }

  .btn-upload {
    width: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    color: white;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  @keyframes slideUp {
    from {
      transform: translateY(50px);
      opacity: 0;
    }

    to {
      transform: translateY(0);
      opacity: 1;
    }
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

  /* Decorative floating shapes */
  body::after {
    content: '';
    position: fixed;
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-radius: 50%;
    top: -200px;
    right: -200px;
    pointer-events: none;
    z-index: 0;
    animation: float 6s ease-in-out infinite;
  }

  .container::before {
    content: '';
    position: fixed;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(74, 172, 254, 0.1));
    border-radius: 50%;
    bottom: -150px;
    left: -150px;
    pointer-events: none;
    z-index: 0;
    animation: float 8s ease-in-out infinite reverse;
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
    const btnEmergencyUnlock = SEL('#btn_emergency_unlock');
    const btnLedOn = SEL('#btn_led_on');
    const btnLedOff = SEL('#btn_led_off');
    const doorStatusEl = SEL('#door_status');
    const faceListEl = SEL('#face_list');
    const historyListEl = SEL('#history_list');
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
    let doorStatusTimer = null;


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
      if (!statusEl) return;
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
        lastLogId = latest.id; // Chỉ quan tâm log tự động từ ESP32 (LM393)
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

    async function addFace() {
      try {
        const nameInput = document.getElementById('new_face_name');
        const statusDiv = document.getElementById('add_face_status');
        const name = nameInput.value.trim();

        // Validate name
        if (!name) {
          statusDiv.textContent = '⚠️ Vui lòng nhập tên!';
          statusDiv.className = 'add-face-status show error';
          return;
        }

        // Show processing status
        statusDiv.textContent = '📸 Đang chụp ảnh...';
        statusDiv.className = 'add-face-status show info';
        setStatus('CAPTURING…', 'warn');

        const ip = getIP();

        // Step 1: Capture image from ESP32
        const captureRes = await fetch(`${API_BASE}/esp32/capture?ip=${encodeURIComponent(ip)}`);
        if (!captureRes.ok) {
          throw new Error('Không thể chụp ảnh từ ESP32');
        }

        const captureData = await captureRes.json();
        if (!captureData.ok || !captureData.url) {
          throw new Error('Không nhận được URL ảnh');
        }

        const imageUrl = captureData.url;

        // Show uploading status
        statusDiv.textContent = '💾 Đang lưu vào cơ sở dữ liệu...';

        // Step 2: Send to add-face API
        const addRes = await fetch(`${API_BASE}/add-face`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            name: name,
            image_url: imageUrl
          })
        });

        const addData = await addRes.json();

        if (!addRes.ok || !addData.ok) {
          throw new Error(addData.error || 'Lỗi khi thêm khuôn mặt');
        }

        // Success
        statusDiv.textContent = '✅ Đã lưu khuôn mặt thành công!';
        statusDiv.className = 'add-face-status show success';
        setStatus('FACE ADDED', 'ok');

        toast(`✅ Đã thêm khuôn mặt "${name}" vào hệ thống!`, 3000);

        // Clear input after 2 seconds
        setTimeout(() => {
          nameInput.value = '';
          statusDiv.className = 'add-face-status';
        }, 2000);

      } catch (e) {
        console.error('Add face error:', e);
        const statusDiv = document.getElementById('add_face_status');
        statusDiv.textContent = '❌ Lỗi: ' + e.message;
        statusDiv.className = 'add-face-status show error';
        setStatus('ERROR', 'err');
        toast('Lỗi thêm khuôn mặt: ' + e.message);
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

    // ====== NEW FEATURES ======

    // Emergency Unlock
    async function emergencyUnlock() {
      try {
        const res = await fetchJsonSafe(`${API_BASE}/door/unlock`, {
          method: 'POST'
        });

        if (res.ok) {
          toast('🚨 Đã mở khóa cửa!', 3000);
          updateDoorStatus('open');
        } else {
          throw new Error(res.error || 'Unlock failed');
        }
      } catch (e) {
        console.error('Emergency unlock error:', e);
        toast('❌ Lỗi: ' + e.message, 3000);
      }
    }

    // Door Status
    async function checkDoorStatus() {
      try {
        const res = await fetchJsonSafe(`${API_BASE}/door/status`);
        updateDoorStatus(res.status);
      } catch (e) {
        console.warn('Door status error:', e);
      }
    }

    function updateDoorStatus(status) {
      if (!doorStatusEl) return;

      if (status === 'open') {
        doorStatusEl.className = 'status-badge door-open';
        doorStatusEl.innerHTML = '🚪 CỬA MỞ';
      } else {
        doorStatusEl.className = 'status-badge door-closed';
        doorStatusEl.innerHTML = '🚪 CỬA ĐÓNG';
      }
    }

    function startDoorStatusPolling() {
      if (doorStatusTimer) return;
      doorStatusTimer = setInterval(checkDoorStatus, 2000);
      checkDoorStatus();
    }

    // LED Control
    async function toggleLed(state) {
      try {
        const ip = getIP();
        const value = state ? 255 : 0;
        await ctrl('led_intensity', value);
        toast(state ? '💡 Đã bật đèn' : '🌙 Đã tắt đèn', 2000);
      } catch (e) {
        console.error('LED control error:', e);
        toast('❌ Lỗi điều khiển đèn', 2000);
      }
    }

    // Face Management
    async function loadFaceList() {
      try {
        const res = await fetchJsonSafe(`${API_BASE}/faces`);

        if (!res.faces || res.faces.length === 0) {
          faceListEl.innerHTML = '<div class="loading">Chưa có khuôn mặt nào</div>';
          return;
        }

        let html = '';
        res.faces.forEach(face => {
          const photoUrl = face.photo_url || `${API_BASE}/face-photo/${face.name}`;
          html += `
            <div class="face-list-item" data-face-name="${face.name}">
              <img src="${photoUrl}" class="face-list-avatar" alt="${face.name}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%23667eea%22 width=%2250%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2220%22%3E${face.name[0]?.toUpperCase() || '?'}%3C/text%3E%3C/svg%3E'">
              <div class="face-list-info">
                <div class="face-list-name">${face.name}</div>
                <div class="face-list-date">${face.date || 'N/A'}</div>
              </div>
              <div class="face-list-actions">
                <button class="btn-edit" onclick="editFace('${face.name}')">✏️ Sửa</button>
                <button class="btn-edit" onclick="manageFaceImages('${face.name}')">🖼️ Ảnh</button>
                <button class="btn-delete" onclick="deleteFace('${face.name}')">🗑️ Xóa</button>
              </div>
            </div>
          `;
        });

        faceListEl.innerHTML = html;
      } catch (e) {
        console.error('Load face list error:', e);
        faceListEl.innerHTML = '<div class="loading">❌ Lỗi tải danh sách</div>';
      }
    }

    async function editFace(oldName) {
      const newName = prompt('Nhập tên mới:', oldName);
      if (!newName || newName === oldName) return;

      try {
        const res = await fetchJsonSafe(`${API_BASE}/faces/${encodeURIComponent(oldName)}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            new_name: newName
          })
        });

        if (res.ok) {
          toast('✅ Đã cập nhật tên!', 2000);
          loadFaceList();
        } else {
          throw new Error(res.error || 'Update failed');
        }
      } catch (e) {
        console.error('Edit face error:', e);
        toast('❌ Lỗi: ' + e.message, 3000);
      }
    }

    async function deleteFace(name) {
      if (!confirm(`Bạn có chắc muốn xóa "${name}"?`)) return;

      try {
        const res = await fetchJsonSafe(`${API_BASE}/faces/${encodeURIComponent(name)}`, {
          method: 'DELETE'
        });

        if (res.ok) {
          toast('🗑️ Đã xóa khuôn mặt!', 2000);
          loadFaceList();
        } else {
          throw new Error(res.error || 'Delete failed');
        }
      } catch (e) {
        console.error('Delete face error:', e);
        toast('❌ Lỗi: ' + e.message, 3000);
      }
    }

    // History Management
    async function loadHistory() {
      try {
        const res = await fetchJsonSafe(`${API_BASE}/logs?limit=10`);

        if (!res.data || res.data.length === 0) {
          historyListEl.innerHTML = '<div class="loading">Chưa có lịch sử</div>';
          return;
        }

        let html = '';
        res.data.forEach(log => {
          const statusClass = log.status === 'granted' ? 'success' : 'failed';
          const statusText = log.status === 'granted' ? '✅ Cho phép' : '❌ Từ chối';
          const name = log.recognized_name || 'Unknown';
          const photoUrl = log.photo_url || '';
          const timestamp = new Date(log.timestamp).toLocaleString('vi-VN');

          html += `
            <div class="history-item ${statusClass}">
              ${photoUrl ? `<img src="${photoUrl}" class="history-thumb" alt="${name}">` : ''}
              <div class="history-info">
                <div class="history-name">${name}</div>
                <div class="history-time">${timestamp}</div>
              </div>
              <div class="history-status ${statusClass}">${statusText}</div>
            </div>
          `;
        });

        historyListEl.innerHTML = html;
      } catch (e) {
        console.error('Load history error:', e);
        historyListEl.innerHTML = '<div class="loading">❌ Lỗi tải lịch sử</div>';
      }
    }

    // Image Management Functions
    let currentFaceName = '';

    async function manageFaceImages(name) {
      currentFaceName = name;
      const modal = document.getElementById('image_modal');
      const modalTitle = document.getElementById('modal_face_name');
      const gallery = document.getElementById('image_gallery');

      modalTitle.textContent = name;
      gallery.innerHTML = '<div class="loading">Đang tải ảnh...</div>';
      modal.style.display = 'flex';

      try {
        const res = await fetchJsonSafe(`${API_BASE}/faces/${encodeURIComponent(name)}/images`);

        if (!res.images || res.images.length === 0) {
          gallery.innerHTML = '<div class="loading">Chưa có ảnh nào</div>';
          return;
        }

        let html = '';
        res.images.forEach((img, idx) => {
          const canDelete = res.images.length > 1; // Chỉ cho phép xóa nếu có > 1 ảnh
          html += `
            <div class="image-item">
              <img src="${img.url}" alt="${name}">
              ${canDelete ? `
                <button class="image-delete" onclick="deleteImage('${name}', '${img.filename}')">
                  🗑️
                </button>
              ` : ''}
              <div class="image-count">#${idx + 1}</div>
            </div>
          `;
        });

        gallery.innerHTML = html;
      } catch (e) {
        console.error('Load images error:', e);
        gallery.innerHTML = '<div class="loading">❌ Lỗi tải ảnh</div>';
      }
    }

    async function deleteImage(name, filename) {
      if (!confirm('Bạn có chắc muốn xóa ảnh này?')) return;

      try {
        const res = await fetchJsonSafe(
          `${API_BASE}/faces/${encodeURIComponent(name)}/images/${encodeURIComponent(filename)}`, {
            method: 'DELETE'
          }
        );

        if (res.ok) {
          toast('🗑️ Đã xóa ảnh!', 2000);
          manageFaceImages(name); // Reload gallery
        } else {
          throw new Error(res.error || 'Delete failed');
        }
      } catch (e) {
        console.error('Delete image error:', e);
        toast('❌ Lỗi: ' + e.message, 3000);
      }
    }

    async function uploadImages() {
      const fileInput = document.getElementById('upload_image');
      const files = fileInput.files;

      if (!files || files.length === 0) {
        toast('⚠️ Vui lòng chọn ảnh để upload', 2000);
        return;
      }

      try {
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
          formData.append('images', files[i]);
        }

        const res = await fetch(
          `${API_BASE}/faces/${encodeURIComponent(currentFaceName)}/images`, {
            method: 'POST',
            body: formData
          }
        );

        const data = await res.json();

        if (data.ok) {
          toast(`✅ Đã thêm ${files.length} ảnh!`, 2000);
          fileInput.value = ''; // Clear input
          manageFaceImages(currentFaceName); // Reload gallery
          loadFaceList(); // Reload face list
        } else {
          throw new Error(data.error || 'Upload failed');
        }
      } catch (e) {
        console.error('Upload error:', e);
        toast('❌ Lỗi upload: ' + e.message, 3000);
      }
    }

    function closeImageModal() {
      const modal = document.getElementById('image_modal');
      modal.style.display = 'none';
      currentFaceName = '';
    }

    // Make functions global for inline onclick
    window.editFace = editFace;
    window.deleteFace = deleteFace;
    window.manageFaceImages = manageFaceImages;
    window.deleteImage = deleteImage;
    window.closeImageModal = closeImageModal;

    // ====== WIRE UI ======
    if (btnReload) btnReload.addEventListener('click', reloadCam);
    if (btnCapture) btnCapture.addEventListener('click', () => disableDuring(btnCapture, capture()));
    if (btnRecognize) btnRecognize.addEventListener('click', () => disableDuring(btnRecognize, detectFace()));

    // Add face button
    const btnAddFace = document.getElementById('btn_add_face');
    if (btnAddFace) btnAddFace.addEventListener('click', () => disableDuring(btnAddFace, addFace()));

    // New feature buttons
    if (btnEmergencyUnlock) btnEmergencyUnlock.addEventListener('click', () => disableDuring(btnEmergencyUnlock, emergencyUnlock()));
    if (btnLedOn) btnLedOn.addEventListener('click', () => toggleLed(true));
    if (btnLedOff) btnLedOff.addEventListener('click', () => toggleLed(false));

    // Upload images button
    const btnUploadImages = document.getElementById('btn_upload_images');
    if (btnUploadImages) btnUploadImages.addEventListener('click', uploadImages);

    document.addEventListener('click', e => {
      const el = e.target;
      if (el.matches('[data-led]')) setLed(el.dataset.led | 0);
      if (el.matches('[data-ctrl]')) ctrl(el.dataset.ctrl, el.dataset.val);
    });

    // --- SỬA ĐOẠN NÀY (Comment lại hoặc thêm if) ---
    if (autoOn) {
      autoOn.addEventListener('change', () => autoOn.checked ? startAuto() : stopAuto());
    }

    if (autoMs) {
      autoMs.addEventListener('change', () => {
        if (autoOn.checked) {
          stopAuto();
          startAuto();
        }
      });
    }

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopAuto();
        stopSensorPolling();
      } else {
        if (autoOn && autoOn.checked) startAuto(); // Thêm kiểm tra autoOn tồn tại
        startSensorPolling();
      }
    });

    // ====== BOOT ======
    reloadCam();
    startSensorPolling(); // Bật sensor polling khi tải trang
    startDoorStatusPolling(); // Bật door status polling
    loadFaceList(); // Tải danh sách khuôn mặt
    loadHistory(); // Tải lịch sử

    // Refresh danh sách và lịch sử mỗi 5 giây
    setInterval(() => {
      loadFaceList();
      loadHistory();
    }, 5000);
  })();
</script>