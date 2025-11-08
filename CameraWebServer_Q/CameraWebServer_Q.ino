#include "esp_camera.h"
#include <WiFi.h>
#include <HTTPClient.h>

// ===========================
// Server & Device Identity
// ===========================
const char* SERVER_BASE = "http://192.168.0.103/Project_Q/public";
const char* DEVICE_ID   = "DOOR-01";
const char* DEVICE_KEY  = "DEV_SECRET_123";

// ===========================
// Select camera model
// ===========================
#include "board_config.h"
#include "camera_pins.h"

// ===========================
// WiFi credentials
// ===========================
const char *ssid     = "TP-Link_F6D2";
const char *password = "1223334444";

// Forward declarations
void startCameraServer();
void setupLedFlash();

// ---------------------------
// API: Heartbeat
// ---------------------------
void postHeartbeat() {
  HTTPClient http;
  String url  = String(SERVER_BASE) + "/api/device/heartbeat.php";
  String body = "deviceId=" + String(DEVICE_ID) + "&token=" + String(DEVICE_KEY);

  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  int httpCode = http.POST(body);
  Serial.printf("[HB] Heartbeat sent, code: %d\n", httpCode);
  http.end();
}

// ---------------------------
// API: Gửi ảnh + log truy cập
// ---------------------------
void postAccessLog(const char* result, const char* note = "from esp32cam") {
  camera_fb_t *fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("[POST] Camera capture failed");
    return;
  }

  HTTPClient http;
  String url = String(SERVER_BASE) + "/api/access-log.php";
  http.begin(url);

  const char* B = "----ESP32BOUNDARY";
  String head =
    String("--") + B + "\r\n"
    "Content-Disposition: form-data; name=\"deviceId\"\r\n\r\n" + DEVICE_ID + "\r\n"
    "--" + B + "\r\n"
    "Content-Disposition: form-data; name=\"token\"\r\n\r\n" + DEVICE_KEY + "\r\n"
    "--" + B + "\r\n"
    "Content-Disposition: form-data; name=\"result\"\r\n\r\n" + result + "\r\n"
    "--" + B + "\r\n"
    "Content-Disposition: form-data; name=\"note\"\r\n\r\n" + note + "\r\n"
    "--" + B + "\r\n"
    "Content-Disposition: form-data; name=\"image\"; filename=\"snap.jpg\"\r\n"
    "Content-Type: image/jpeg\r\n\r\n";

  String tail = String("\r\n--") + B + "--\r\n";

  http.addHeader("Content-Type", String("multipart/form-data; boundary=") + B);

  size_t totalLen = head.length() + fb->len + tail.length();
  uint8_t *buf = (uint8_t*) malloc(totalLen);
  if (!buf) {
    Serial.println("[POST] malloc failed");
    http.end();
    esp_camera_fb_return(fb);
    return;
  }

  memcpy(buf, head.c_str(), head.length());
  memcpy(buf + head.length(), fb->buf, fb->len);
  memcpy(buf + head.length() + fb->len, tail.c_str(), tail.length());

  int code = http.POST(buf, totalLen);
  Serial.printf("[POST] AccessLog code: %d (img %u bytes)\n", code, (unsigned)fb->len);

  free(buf);
  http.end();
  esp_camera_fb_return(fb);
}

// ---------------------------
// Bật/tắt Face Detection qua /control
// ---------------------------
void setFaceDetect(bool enabled) {
  String url = "http://" + WiFi.localIP().toString() + "/control?face_detect=" + (enabled ? "1" : "0");
  HTTPClient http;
  http.begin(url);
  int code = http.GET();
  Serial.printf("[CTRL] face_detect=%d -> HTTP %d\n", enabled ? 1 : 0, code);
  http.end();
}

// =====================================================
// CALLBACK: được app_httpd.cpp gọi khi có/không có mặt
// =====================================================
extern "C" void esp32cam_on_face_presence(bool present) {
  // Callback cho face detection (tùy chỉnh thêm nếu cần)
}

// ===========================
// setup & loop
// ===========================
void setup() {
  Serial.begin(115200);
  Serial.setDebugOutput(true);
  Serial.println();

  // ----- Camera config tối ưu tốc độ -----
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer   = LEDC_TIMER_0;
  config.pin_d0       = Y2_GPIO_NUM;
  config.pin_d1       = Y3_GPIO_NUM;
  config.pin_d2       = Y4_GPIO_NUM;
  config.pin_d3       = Y5_GPIO_NUM;
  config.pin_d4       = Y6_GPIO_NUM;
  config.pin_d5       = Y7_GPIO_NUM;
  config.pin_d6       = Y8_GPIO_NUM;
  config.pin_d7       = Y9_GPIO_NUM;
  config.pin_xclk     = XCLK_GPIO_NUM;
  config.pin_pclk     = PCLK_GPIO_NUM;
  config.pin_vsync    = VSYNC_GPIO_NUM;
  config.pin_href     = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn     = PWDN_GPIO_NUM;
  config.pin_reset    = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;           // 20MHz tốc độ tối ưu cho ESP32-CAM

  // ---- Cấu hình hình ảnh tối ưu cho AI ----
  config.pixel_format = PIXFORMAT_JPEG;
  config.frame_size   = FRAMESIZE_VGA;      // 640x480: CÂN BẰNG TỐT cho face detection (accuracy + speed)
  config.grab_mode    = CAMERA_GRAB_LATEST; // luôn lấy khung hình mới nhất, bỏ qua frame cũ
  config.jpeg_quality = 10;                 // 10: chất lượng CAO cho face recognition (detail matters!)

  // ---- Framebuffer & PSRAM - tối ưu hiệu suất ----
  if (psramFound()) {
    Serial.println("[PSRAM] Found -> using PSRAM (2 framebuffers for max speed)");
    config.fb_location = CAMERA_FB_IN_PSRAM;
    config.fb_count    = 2;                 // 2 buffers: tốc độ tối đa, ít lag
  } else {
    Serial.println("[PSRAM] Not found -> using DRAM (1 buffer)");
    config.fb_location = CAMERA_FB_IN_DRAM;
    config.fb_count    = 1;
  }

  // ---- Khởi tạo camera ----
  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("[CAM] init failed 0x%x\n", err);
    return;
  }

  // ---- Cấu hình sensor tối ưu cho Face Detection ----
  sensor_t *s = esp_camera_sensor_get();
  if (s) {
    // Cài đặt cơ bản - OPTIMIZED cho face recognition
    s->set_brightness(s, 0);      // 0: cân bằng, tốt cho face detection
    s->set_contrast(s, 1);        // +1: tăng độ tương phản nhẹ (tốt cho face features)
    s->set_saturation(s, -1);     // -1: giảm saturation (tốt cho grayscale processing)
    s->set_sharpness(s, 1);       // +1: tăng độ sắc nét (CHI TIẾT KHUÔN MẶT rõ hơn)
    s->set_denoise(s, 0);         // Tắt denoise để giữ detail
    
    // Auto Exposure & Gain - quan trọng cho face detection
    s->set_gainceiling(s, (gainceiling_t)0);  // Giới hạn gain tự động
    s->set_awb_gain(s, 1);        // Bật Auto White Balance Gain
    s->set_wb_mode(s, 0);         // Auto White Balance
    s->set_exposure_ctrl(s, 1);   // Bật Auto Exposure
    s->set_aec2(s, 0);            // Tắt AEC DSP
    s->set_ae_level(s, 0);        // AE level cân bằng
    s->set_aec_value(s, 300);     // Exposure value tối ưu
    s->set_gain_ctrl(s, 1);       // Bật Auto Gain
    s->set_agc_gain(s, 0);        // AGC gain tự động
    s->set_bpc(s, 0);             // Black Pixel Correction
    s->set_wpc(s, 1);             // White Pixel Correction
    s->set_raw_gma(s, 1);         // Raw Gamma
    s->set_lenc(s, 1);            // Lens Correction
    s->set_hmirror(s, 0);         // Horizontal Mirror
    s->set_vflip(s, 0);           // Vertical Flip
    s->set_dcw(s, 1);             // Downsize Enable
    s->set_colorbar(s, 0);        // Tắt colorbar test pattern
    
    // Đặc biệt cho OV3660
    if (s->id.PID == OV3660_PID) {
      s->set_vflip(s, 1);
      s->set_brightness(s, 1);
      s->set_saturation(s, 0);
    }
    
    Serial.println("[SENSOR] Optimized for Face Detection");
  }

#if defined(LED_GPIO_NUM)
  setupLedFlash();
#endif

  // ----- WiFi -----
  WiFi.begin(ssid, password);
  WiFi.setSleep(false);
  Serial.print("[WiFi] connecting");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.printf("\n[WiFi] connected, IP: %s\n", WiFi.localIP().toString().c_str());

  // ----- Heartbeat -----
  postHeartbeat();

  // ----- Start webserver -----
  startCameraServer();

  // ----- Tắt AI onboard (tăng tốc độ capture) -----
  setFaceDetect(false);

  Serial.print("[OK] Camera Ready! Open http://");
  Serial.print(WiFi.localIP());
  Serial.println("/");
}

void loop() {
  // Web server chạy ở task riêng – không cần làm gì ở loop
  delay(10);
}
