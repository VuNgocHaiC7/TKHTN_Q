<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CAMERA ESP32-CAM</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <div class="container">
    <div class="header">
      <div class="header-left">
        <img src="logo.jpg" alt="Logo" class="logo-img">
      </div>
      <div class="header-center">
        <div class="title-section">
          <h1>Hệ Thống Khóa Cửa Bằng Nhận Diện Khuôn Mặt</h1>
          <p>Nhóm 1 - Thiết Kế Hệ Thống Nhúng Lớp L01</p>
        </div>
      </div>
      <div class="header-right">
        <div class="team-members">
          <div class="member">Nguyễn Tiến Đạt - CT070112</div>
          <div class="member">Hoàng Thị Như Quỳnh - CT070344</div>
          <div class="member">Vũ Ngọc Hải - CT070318</div>
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
              <button id="btn_recognize" class="btn" style="background: linear-gradient(135deg, #10b981, #059669);">🔍 Test Nhận diện</button>
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

  <!-- Modal Hướng dẫn -->
  <div id="tutorial_modal" class="modal" style="display:none;">
    <div class="modal-content tutorial-modal">
      <div class="modal-header">
        <h2>📖 Hướng Dẫn Sử Dụng</h2>
        <button class="modal-close" onclick="closeTutorial()">✕</button>
      </div>
      <div class="modal-body tutorial-body">
        <div class="tutorial-section">
          <h3>🎯 1. Kết nối ESP32-CAM</h3>
          <p>• Nhập địa chỉ IP của ESP32-CAM vào ô "IP Address"</p>
          <p>• Nhấn "🔄 Tải lại" để kết nối camera</p>
          <p>• Đợi cho đến khi hình ảnh từ camera hiển thị</p>
        </div>

        <div class="tutorial-section">
          <h3>👤 2. Thêm Khuôn Mặt Mới</h3>
          <p>• Nhập tên người dùng vào ô "Nhập tên của bạn..."</p>
          <p>• Nhấn "📸 Chụp và Lưu Khuôn Mặt"</p>
          <p>• Đảm bảo khuôn mặt hiện rõ trên camera</p>
          <p>• Hệ thống sẽ tự động lưu và huấn luyện</p>
        </div>

        <div class="tutorial-section">
          <h3>🔍 3. Test Nhận Diện Khuôn Mặt</h3>
          <p>• Nhấn nút "🔍 Test Nhận diện" để kiểm tra khả năng nhận diện</p>
          <p>• Chế độ này CHỈ hiển thị kết quả, KHÔNG mở khóa cửa</p>
          <p>• Dùng để đánh giá và kiểm tra hệ thống nhận diện</p>
          <p>• Kết quả hiển thị độ chính xác và tên người được nhận diện</p>
        </div>

        <div class="tutorial-section">
          <h3>🚪 4. Nhận Diện Tự Động (Cảm Biến)</h3>
          <p>• Khi có người đứng trước cửa, cảm biến sẽ kích hoạt</p>
          <p>• Hệ thống tự động nhận diện khuôn mặt</p>
          <p>• Nếu khớp, cửa sẽ TỰ ĐỘNG mở</p>
          <p>• Lịch sử nhận diện được lưu lại tự động</p>
        </div>

        <div class="tutorial-section">
          <h3>� 4. Quản Lý Khuôn Mặt</h3>
          <p>• Xem danh sách tất cả khuôn mặt đã lưu</p>
          <p>• Nhấn "📸" để quản lý ảnh của từng người</p>
          <p>• Nhấn "✏️" để đổi tên</p>
          <p>• Nhấn "🗑️" để xóa khuôn mặt</p>
        </div>

        <div class="tutorial-section">
          <h3>🚨 6. Mở Khóa Khẩn Cấp</h3>
          <p>• Sử dụng nút "🚨 MỞ KHÓA NGAY" khi cần thiết</p>
          <p>• Cửa sẽ mở ngay lập tức không cần nhận diện</p>
          <p>• Hữu ích trong trường hợp khẩn cấp hoặc camera bị lỗi</p>
          <p>• Hệ thống sẽ ghi lại lịch sử mở khóa khẩn cấp</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating Help Button -->
  <button id="help_button" class="floating-help-btn" onclick="openTutorial()" title="Hướng dẫn sử dụng">
    ❓
  </button>

  <!-- Notification Popup Container -->
  <div id="notification_container" class="notification-container"></div>

  </div>
</body>

<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 30px 20px;
    position: relative;
    overflow-x: hidden;
    color: #1f2937;
  }

  body::before {
    display: none;
  }



  .container {
    max-width: 1400px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  .header {
    background: white;
    border-radius: 16px;
    padding: 20px 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(255, 255, 255, 0.3);
  }

  .header-left {
    display: flex;
    align-items: center;
    width: 200px;
  }

  .logo-img {
    height: 70px;
    width: auto;
    object-fit: contain;
  }

  .header-center {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .title-section {
    text-align: center;
  }

  .title-section h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
    letter-spacing: -0.3px;
  }

  .title-section p {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    margin: 0;
  }

  .team-members {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end;
  }

  .member {
    background: #f0f4f8;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    color: #667eea;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
  }

  .member:hover {
    background: #e0e7ff;
    border-color: #667eea;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
  }

  .header-right {
    display: flex;
    gap: 12px;
    width: 280px;
    justify-content: flex-end;
  }

  .status-badge {
    padding: 14px 28px;
    border-radius: 28px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    letter-spacing: 0.3px;
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



  .main-content {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 24px;
    align-items: start;
  }

  .card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    height: fit-content;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
  }

  .card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  }



  .card-title {
    font-size: 20px;
    font-weight: 700;
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
    width: 70px;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 3px;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
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
    width: 180px;
    height: 38px;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    font-size: 13px;
    padding: 0 12px;
    transition: all 0.3s ease;
  }

  .ipaddress input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
    padding: 14px 16px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-radius: 10px;
    border: 2px solid #10b981;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
    transition: all 0.3s ease;
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
    max-height: 450px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 2px solid #667eea;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
    padding: 14px 20px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
  }

  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
  }

  .btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
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
    padding: 16px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    outline: none;
    font-weight: 500;
  }

  .face-name-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
  }

  .add-face-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 16px 24px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .add-face-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
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
    font-size: 17px !important;
    font-weight: 700 !important;
    padding: 16px 28px !important;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4) !important;
    letter-spacing: 0.5px;
  }

  .btn-emergency:hover {
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5) !important;
    transform: translateY(-2px) !important;
  }

  /* Door Status */
  .door-closed {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
  }

  .door-open {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
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
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border-radius: 16px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
  }

  .face-list-item:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #ffffff, #f8fafc);
    transform: translateX(6px) scale(1.02);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
  }

  .face-list-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    transition: all 0.3s;
  }

  .face-list-item:hover .face-list-avatar {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
  }

  .face-list-info {
    flex: 1;
  }

  .face-list-name {
    font-weight: 700;
    font-size: 16px;
    color: #1f2937;
    margin-bottom: 6px;
  }

  .face-list-date {
    font-size: 13px;
    color: #6b7280;
  }

  .face-list-actions {
    display: flex;
    gap: 8px;
  }

  .btn-edit,
  .btn-delete {
    padding: 10px 16px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .btn-edit {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }

  .btn-edit:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.4);
  }

  .btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
  }

  .btn-delete:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(239, 68, 68, 0.4);
  }

  /* History List */
  .history-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 16px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 12px;
    cursor: pointer;
  }

  .history-item:hover {
    transform: translateX(6px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
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
    width: 70px;
    height: 70px;
    border-radius: 12px;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .history-info {
    flex: 1;
  }

  .history-name {
    font-weight: 800;
    font-size: 16px;
    color: #1f2937;
    margin-bottom: 6px;
  }

  .history-time {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
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
    border-radius: 28px;
    width: 90%;
    max-width: 900px;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
    animation: slideUp 0.3s ease;
  }

  .modal-header {
    padding: 28px 32px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  }

  .modal-header h2 {
    font-size: 24px;
    font-weight: 800;
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

  /* Floating Help Button */
  .floating-help-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d8e213ff, #44a64bff);
    color: white;
    border: none;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.5);
    transition: all 0.3s ease;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: float 3s ease-in-out infinite;
  }

  .floating-help-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 30px rgba(102, 126, 234, 0.7);
  }

  .floating-help-btn:active {
    transform: scale(0.95);
  }

  @keyframes float {

    0%,
    100% {
      transform: translateY(0);
    }

    50% {
      transform: translateY(-10px);
    }
  }

  /* Tutorial Modal Styles */
  .tutorial-modal {
    max-width: 700px;
  }

  .tutorial-body {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 10px;
  }

  .tutorial-body::-webkit-scrollbar {
    width: 8px;
  }

  .tutorial-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .tutorial-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
  }

  .tutorial-section {
    margin-bottom: 24px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
    border-radius: 12px;
    border-left: 4px solid #667eea;
  }

  .tutorial-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .tutorial-section p {
    font-size: 14px;
    color: #4b5563;
    margin-bottom: 8px;
    padding-left: 20px;
    line-height: 1.6;
  }

  .tutorial-section p:last-child {
    margin-bottom: 0;
  }

  /* Decorative shapes removed for cleaner design */

  /* Responsive Design */
  @media (max-width: 1200px) {
    .main-content {
      grid-template-columns: 1fr;
    }

    .right-panel {
      max-width: 100%;
    }
  }

  @media (max-width: 768px) {
    body {
      padding: 15px;
    }

    .header {
      padding: 16px 24px;
      border-radius: 20px;
      flex-direction: column;
      gap: 16px;
      text-align: center;
    }

    .header-left {
      flex-direction: column;
      text-align: center;
    }

    .logo {
      width: 55px;
      height: 55px;
      font-size: 28px;
    }

    .title-section h1 {
      font-size: 22px;
    }

    .title-section p {
      font-size: 14px;
    }

    .team-members {
      justify-content: center;
      gap: 8px;
    }

    .member {
      font-size: 12px;
      padding: 6px 12px;
    }

    .card {
      padding: 20px;
      border-radius: 20px;
    }

    .card-title {
      font-size: 20px;
    }

    .main-content {
      gap: 20px;
    }

    .grid {
      grid-template-columns: 1fr;
      gap: 12px;
    }

    .led-control {
      grid-template-columns: 1fr;
    }

    .stream-wrapper {
      max-height: 300px;
      border-radius: 16px;
    }

    .btn {
      padding: 14px 20px;
      font-size: 14px;
    }

    .image-gallery {
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 12px;
    }
  }

  @media (max-width: 480px) {
    .status-badge {
      padding: 10px 16px;
      font-size: 13px;
    }

    .sensor-status {
      padding: 14px 16px;
      gap: 10px;
    }

    .ipaddress {
      flex-direction: column;
      align-items: flex-start;
    }

    .ipaddress input {
      width: 100%;
    }
  }

  /* Smooth scrollbar */
  ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }

  ::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
  }

  ::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    transition: all 0.3s;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
  }

  /* ==================== NOTIFICATION POPUP ==================== */
  .notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
  }

  .notification-popup {
    min-width: 380px;
    max-width: 450px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    pointer-events: all;
    animation: slideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
    overflow: hidden;
    border: 3px solid;
  }

  .notification-popup.success {
    border-color: #10b981;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  }

  .notification-popup.error {
    border-color: #ef4444;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  }

  .notification-popup::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: linear-gradient(180deg, #10b981, #059669);
  }

  .notification-popup.error::before {
    background: linear-gradient(180deg, #ef4444, #dc2626);
  }

  .notification-icon {
    font-size: 48px;
    line-height: 1;
    animation: bounceIn 0.6s ease;
    flex-shrink: 0;
  }

  .notification-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .notification-title {
    font-size: 18px;
    font-weight: 800;
    color: #1f2937;
    letter-spacing: -0.3px;
  }

  .notification-popup.success .notification-title {
    color: #065f46;
  }

  .notification-popup.error .notification-title {
    color: #991b1b;
  }

  .notification-message {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
  }

  .notification-details {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
    font-weight: 600;
  }

  .notification-image {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
  }

  .notification-close {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.1);
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    color: #6b7280;
    transition: all 0.2s;
  }

  .notification-close:hover {
    background: rgba(0, 0, 0, 0.2);
    transform: rotate(90deg);
  }

  .notification-popup.hiding {
    animation: slideUp 0.3s ease forwards;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-100px) scale(0.8);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  @keyframes slideUp {
    from {
      opacity: 1;
      transform: translateY(0) scale(1);
    }

    to {
      opacity: 0;
      transform: translateY(-50px) scale(0.9);
    }
  }

  @keyframes bounceIn {
    0% {
      transform: scale(0);
    }

    50% {
      transform: scale(1.2);
    }

    100% {
      transform: scale(1);
    }
  }

  /* Progress bar */
  .notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981, #059669);
    width: 100%;
    animation: shrink 5s linear forwards;
  }

  .notification-popup.error .notification-progress {
    background: linear-gradient(90deg, #ef4444, #dc2626);
  }

  @keyframes shrink {
    from {
      width: 100%;
    }

    to {
      width: 0%;
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .notification-container {
      right: 10px;
      left: 10px;
      top: 10px;
    }

    .notification-popup {
      min-width: unset;
      max-width: unset;
      width: 100%;
    }

    .notification-icon {
      font-size: 36px;
    }

    .notification-image {
      width: 60px;
      height: 60px;
    }
  }
</style>

<script>
  (() => {
    // ====== CONFIG ======
    // Tự động lấy hostname từ URL hiện tại để hoạt động với cả localhost và IP
    const API_BASE = `http://${window.location.hostname}:5000/api`;
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


    // ====== UTILITY FUNCTIONS ======
    const sleep = ms => new Promise(r => setTimeout(r, ms));

    // ====== NOTIFICATION POPUP SYSTEM ======
    function showNotification(options) {
      const {
        type = 'success', // 'success' or 'error'
          title = '',
          message = '',
          details = '',
          imageUrl = null,
          duration = 5000
      } = options;

      const container = document.getElementById('notification_container');
      if (!container) return;

      // Tạo notification element
      const notification = document.createElement('div');
      notification.className = `notification-popup ${type}`;

      const icon = type === 'success' ? '✅' : '❌';

      notification.innerHTML = `
        <div class="notification-icon">${icon}</div>
        <div class="notification-content">
          <div class="notification-title">${title}</div>
          <div class="notification-message">${message}</div>
          ${details ? `<div class="notification-details">${details}</div>` : ''}
        </div>
        ${imageUrl ? `<img src="${imageUrl}" class="notification-image" alt="Photo">` : ''}
        <button class="notification-close" onclick="this.parentElement.remove()">✕</button>
        <div class="notification-progress"></div>
      `;

      container.appendChild(notification);

      // Tự động xóa sau duration
      setTimeout(() => {
        notification.classList.add('hiding');
        setTimeout(() => notification.remove(), 300);
      }, duration);
    }

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
    let lastIrState = 'waiting';
    let isProcessingDetection = false;

    async function checkIrState() {
      try {
        const res = await fetchJsonSafe(`${API_BASE}/ir-state`);
        const state = (res.state || '').toLowerCase();

        if (state === 'detecting' && lastIrState !== 'detecting' && !isProcessingDetection) {
          isProcessingDetection = true;
          setSensorStatus('🔍 ĐANG NHẬN DIỆN...', 'recognizing', 'detecting');
          sensorIcon.textContent = '🔍';

          performAutoFaceDetection().catch((err) => {
            console.error('Auto detection error:', err);
          }).finally(() => {
            isProcessingDetection = false;
          });

        } else if (state === 'detecting') {
          setSensorStatus('🔍 ĐANG NHẬN DIỆN...', 'recognizing', 'detecting');
          sensorIcon.textContent = '🔍';
        } else {
          setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
          sensorIcon.textContent = '📡';
        }

        lastIrState = state;

      } catch (e) {
        console.error('❌ IR state error:', e);
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

        // Tạo URL ảnh đầy đủ nếu có
        let photoUrl = null;
        if (latest.photo_url) {
          if (latest.photo_url.startsWith('/uploads')) {
            photoUrl = `http://${window.location.hostname}:5000${latest.photo_url}`;
          } else {
            photoUrl = latest.photo_url;
          }
        }

        // Hiển thị notification popup
        if (ok) {
          showNotification({
            type: 'success',
            title: '🎉 Nhận Diện Thành Công!',
            message: `Xin chào ${name}`,
            details: `Độ chính xác: ${conf}% • Cửa đã mở`,
            imageUrl: photoUrl,
            duration: 5000
          });

          setSensorStatus(`✅ ${name} (${conf}%)`, 'detected', 'active');
          sensorIcon.textContent = '✅';
        } else {
          showNotification({
            type: 'error',
            title: '⛔ Truy Cập Bị Từ Chối',
            message: 'Không nhận diện được khuôn mặt',
            details: `Độ chính xác: ${conf}% • Vui lòng thử lại`,
            imageUrl: photoUrl,
            duration: 5000
          });

          setSensorStatus('❌ Unknown', 'err', 'detecting');
          sensorIcon.textContent = '❌';
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

      setSensorStatus('🔄 ĐANG KẾT NỐI...', '', '');
      sensorIcon.textContent = '📡';

      // Tăng thời gian lên 2000ms để giảm tải cho camera
      const loop = () => {
        checkIrState();
        checkSensorLog();
      };

      sensorTimer = setInterval(loop, 2000);
      loop();
    }

    function stopSensorPolling() {
      if (!sensorTimer) return;
      clearInterval(sensorTimer);
      sensorTimer = null;
      setSensorStatus('⏸️ TẠM DỪNG', 'idle', '');
      sensorIcon.textContent = '📡';
    }

    async function performAutoFaceDetection() {
      try {
        const ip = getIP();
        setSensorStatus('🔍 ĐANG NHẬN DIỆN...', 'recognizing', 'detecting');
        setStatus('AUTO DETECTING…', 'warn');

        const faceRes = await fetchJsonSafe(`${API_BASE}/face-check?ip=${encodeURIComponent(ip)}`);

        if (!faceRes.ok) {
          setSensorStatus('❌ LỖI NHẬN DIỆN', 'err', '');
          return;
        }

        const faceCount = faceRes.faces ? faceRes.faces.length : 0;
        const matchedCount = faceRes.faces ? faceRes.faces.filter(f => f.matched).length : 0;

        if (faceCount === 0) {
          setSensorStatus('⚠️ KHÔNG THẤY KHUÔN MẶT', 'idle', '');
          return;
        }

        const imgRes = await fetch(`${API_BASE}/esp32-capture?ip=${encodeURIComponent(ip)}`);
        const imgBlob = await imgRes.blob();

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
          showFaceResult(faceRes);
          loadHistory();

          if (matchedCount > 0) {
            const names = faceRes.faces.filter(f => f.matched).map(f => f.name).join(', ');
            toast(`🎯 ${names}`, 2500);
            setSensorStatus(`✅ ${names}`, 'detected', 'active');
            sensorIcon.textContent = '✅';
          } else {
            setSensorStatus('❌ Unknown', 'err', 'detecting');
            sensorIcon.textContent = '❌';
          }

          setTimeout(() => {
            reloadCam();
            setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
            sensorIcon.textContent = '📡';
          }, 5000);
        }
      } catch (e) {
        console.error('Auto detection error:', e);
        setSensorStatus('❌ LỖI', 'err', '');
      }
    }

    // ====== CORE CAMERA FUNCTIONS ======
    function reloadCam() {
      try {
        const ip = getIP();
        // ESP32 chỉ hỗ trợ HTTP
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

      // Ảnh do Flask lưu khi ESP32 gửi lên - tự động dùng hostname hiện tại
      const imageUrl = `http://${window.location.hostname}:5000${photoUrl}`;
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

        // Hiển thị notification popup
        showNotification({
          type: 'success',
          title: '👤 Thêm Khuôn Mặt Thành Công!',
          message: `Đã lưu "${name}" vào hệ thống`,
          details: 'Bạn có thể sử dụng khuôn mặt này để mở khóa',
          duration: 5000
        });

        // Reload face list
        loadFaceList();

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

        // Hiển thị notification popup lỗi
        showNotification({
          type: 'error',
          title: '❌ Lỗi Thêm Khuôn Mặt',
          message: e.message,
          details: 'Vui lòng thử lại sau',
          duration: 5000
        });
      }
    }

    async function detectFace() {
      try {
        const ip = getIP();
        setStatus('DETECTING…', 'warn');
        setSensorStatus('🔍 ĐANG NHẬN DIỆN (TEST)', 'recognizing', 'detecting');

        // Bước 1: Gọi API nhận diện
        const faceRes = await fetchJsonSafe(`${API_BASE}/face-check?ip=${encodeURIComponent(ip)}`);

        if (!faceRes.ok) {
          toast('Lỗi nhận diện: ' + (faceRes.error || 'Unknown'));
          setStatus('DETECT ERR', 'err');
          setSensorStatus('❌ LỖI', 'err', '');
          setTimeout(() => {
            setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
            sensorIcon.textContent = '📡';
          }, 3000);
          return;
        }

        const faceCount = faceRes.faces ? faceRes.faces.length : 0;
        const matchedCount = faceRes.faces ? faceRes.faces.filter(f => f.matched).length : 0;

        if (faceCount === 0) {
          toast('⚠️ Không phát hiện khuôn mặt nào');
          setStatus('NO FACE', 'warn');
          setSensorStatus('⚠️ KHÔNG CÓ MẶT', 'idle', '');
          setTimeout(() => {
            reloadCam();
            setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
            sensorIcon.textContent = '📡';
          }, 3000);
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
          setStatus(`TEST: ${names}`, 'ok');

          // KHÔNG LƯU LOG vào database vì đây chỉ là chế độ test/đánh giá
          // Không gọi API mở cửa - chỉ hiển thị kết quả

          // Hiển thị kết quả trên sensor status
          if (matchedCount > 0) {
            const matchedFace = faceRes.faces.find(f => f.matched);
            const name = matchedFace ? matchedFace.name : 'Unknown';
            const conf = matchedFace ? Math.round(matchedFace.confidence) : 0;

            // Hiển thị notification popup cho chế độ TEST
            showNotification({
              type: 'success',
              title: '🔍 Kết Quả Nhận Diện (Chế độ Test)',
              message: `Phát hiện: ${name}`,
              details: `Độ chính xác: ${conf}% • Phát hiện ${faceCount} khuôn mặt • KHÔNG MỞ CỬA`,
              duration: 5000
            });

            setSensorStatus(`🔍 ${name} (${conf}%) - TEST`, 'detected', 'active');
            sensorIcon.textContent = '🔍';
          } else {
            // Hiển thị notification popup cho nhận diện thất bại
            showNotification({
              type: 'error',
              title: '🔍 Kết Quả Nhận Diện (Chế độ Test)',
              message: 'Khuôn mặt không có trong hệ thống',
              details: `Phát hiện ${faceCount} khuôn mặt • KHÔNG MỞ CỬA`,
              duration: 5000
            });

            setSensorStatus('🔍 KHÔNG RÕ - TEST', 'err', '');
            sensorIcon.textContent = '🔍';
          }

          // Quay lại stream sau 5s và reset trạng thái
          setTimeout(() => {
            reloadCam();
            setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
            sensorIcon.textContent = '📡';
          }, 5000);
        } else {
          toast('Lỗi vẽ khung');
          setStatus('DRAW ERR', 'err');
          setTimeout(() => {
            reloadCam();
            setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
            sensorIcon.textContent = '📡';
          }, 3000);
        }

      } catch (e) {
        console.error(e);
        toast('Lỗi nhận diện: ' + e.message);
        setStatus('ERROR', 'err');
        setSensorStatus('❌ LỖI', 'err', '');
        setTimeout(() => {
          reloadCam();
          setSensorStatus('⏹️ ĐANG CHỜ', 'idle', '');
          sensorIcon.textContent = '📡';
        }, 3000);
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
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            ip: ipEl.value
          })
        });

        if (res.ok) {
          // Hiển thị thông báo thành công với notification popup
          showNotification({
            type: 'success',
            title: '🚨 Mở Khóa Khẩn Cấp',
            message: 'Đã mở khóa cửa thành công!',
            details: `Thời gian: ${new Date().toLocaleTimeString('vi-VN')}`,
            duration: 4000
          });

          // Toast đơn giản
          toast('🚨 Đã mở khóa cửa!', 3000);

          // KHÔNG reload history vì không ghi log
        } else {
          throw new Error(res.error || 'Unlock failed');
        }
      } catch (e) {
        console.error('Emergency unlock error:', e);

        // Hiển thị thông báo lỗi
        showNotification({
          type: 'error',
          title: '❌ Lỗi Mở Khóa',
          message: 'Không thể mở khóa cửa',
          details: e.message,
          duration: 4000
        });

        toast('❌ Lỗi: ' + e.message, 3000);
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

        // Lọc bỏ log emergency unlock
        const filteredData = res.data.filter(log => {
          return log.recognized_name !== 'EMERGENCY_UNLOCK' && log.source !== 'web_manual';
        });

        if (filteredData.length === 0) {
          historyListEl.innerHTML = '<div class="loading">Chưa có lịch sử</div>';
          return;
        }

        let html = '';
        filteredData.forEach(log => {
          const statusClass = log.status === 'granted' ? 'success' : 'failed';
          const statusText = log.status === 'granted' ? '✅ Cho phép' : '❌ Từ chối';
          const name = log.recognized_name || 'Unknown';

          // Tạo đường dẫn ảnh đầy đủ từ photo_url
          let photoUrl = '';
          if (log.photo_url) {
            // Nếu photo_url bắt đầu bằng /uploads, thêm hostname và port
            if (log.photo_url.startsWith('/uploads')) {
              photoUrl = `http://${window.location.hostname}:5000${log.photo_url}`;
            } else {
              photoUrl = log.photo_url;
            }
          }

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

    // Tutorial functions
    function openTutorial() {
      const modal = document.getElementById('tutorial_modal');
      modal.style.display = 'flex';
    }

    function closeTutorial() {
      const modal = document.getElementById('tutorial_modal');
      modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const tutorialModal = document.getElementById('tutorial_modal');
      const imageModal = document.getElementById('image_modal');

      if (event.target === tutorialModal) {
        closeTutorial();
      }
      if (event.target === imageModal) {
        closeImageModal();
      }
    }

    // Make functions global for inline onclick
    window.editFace = editFace;
    window.deleteFace = deleteFace;
    window.manageFaceImages = manageFaceImages;
    window.deleteImage = deleteImage;
    window.closeImageModal = closeImageModal;
    window.openTutorial = openTutorial;
    window.closeTutorial = closeTutorial;

    // ====== WIRE UI ======
    if (btnReload) btnReload.addEventListener('click', reloadCam);
    if (btnCapture) btnCapture.addEventListener('click', () => disableDuring(btnCapture, capture()));
    if (btnRecognize) btnRecognize.addEventListener('click', () => disableDuring(btnRecognize, detectFace()));

    // Add face button
    const btnAddFace = document.getElementById('btn_add_face');
    if (btnAddFace) btnAddFace.addEventListener('click', () => disableDuring(btnAddFace, addFace()));

    // New feature buttons
    if (btnEmergencyUnlock) btnEmergencyUnlock.addEventListener('click', () => disableDuring(btnEmergencyUnlock, emergencyUnlock()));

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
    startSensorPolling();
    loadFaceList();
    loadHistory();

    // Refresh mỗi 15 giây để giảm tải cho camera
    setInterval(() => {
      loadFaceList();
      loadHistory();
    }, 15000);
  })();
</script>