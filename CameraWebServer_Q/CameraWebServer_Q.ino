// CameraWebServer_Final_Full_NoLCD.ino
// Face Unlock (LM393 trigger + auto WiFi + IR state + Servo)
// Core ESP32 3.3.x

#include <Arduino.h>
#include <WiFi.h>
#include "esp_camera.h"
#include "board_config.h"
#include "camera_pins.h"
#include "esp_http_client.h"
#include <HTTPClient.h>

// === THƯ VIỆN SERVO (GIỮ NGUYÊN) ===
#include <ESP32Servo.h>

// ================== WiFi ==================
static const char* WIFI_SSID   = "Q";
static const char* WIFI_PASS   = "1709200004";
static const char* BACKEND_URL = "http://10.80.115.224:5000/api/face-unlock";  // Flask Python API

// === NEW (IR STATE) ===
// Endpoint để báo trạng thái cảm biến cho backend / web.
static const char* IR_STATE_URL = "http://10.80.115.224:5000/api/ir-state";

// ================== CẤU HÌNH CHÂN MỚI ==================
// ĐÃ BỎ LCD, nên không cần SDA/SCL
// Servo nối chân GPIO 2
#define PIN_SERVO 2

// CẢM BIẾN HỒNG NGOẠI (LM393) - Giữ nguyên
const int PIN_LM393 = 14; 

// KHỞI TẠO ĐỐI TƯỢNG
Servo myDoorServo;

// Cấu hình góc Servo
const int POS_CLOSE = 0;   // Góc đóng
const int POS_OPEN  = 90;  // Góc mở

// QUAN TRỌNG: Kiểm tra module của bạn
const int MOTION_ACTIVE_STATE = LOW;  // Thay HIGH nếu cần

// Trạng thái xử lý nhận diện
bool isChecking = false;

// Chống spam cảm biến theo style IOT (PIR)
bool gateLocked = false;             // true = đã xử lý cho "đợt người này", chờ người rời đi
unsigned long lastTrigger = 0;
const unsigned long COOLDOWN_MS = 5000;   // 5s cooldown giữa các lần nhận diện

// Auto WiFi reconnect (giống IOT)
unsigned long lastWifiTry = 0;
const unsigned long WIFI_RETRY_EVERY = 10000; // 10s thử reconnect 1 lần

// === NEW (IR STATE) ===
enum IrStateEnum {
  IR_STATE_UNKNOWN = 0,
  IR_STATE_WAITING,
  IR_STATE_DETECTING
};

IrStateEnum g_lastIrState = IR_STATE_UNKNOWN;

// ================== prototype ==================
void startCameraServer();
static void send_ir_state(const char* state);
static void set_ir_state(IrStateEnum s);

// ================== WiFi ==================
static void wifi_connect() {
  Serial.printf("[WiFi] Connecting to SSID: %s\n", WIFI_SSID);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) { // tối đa 20s
    delay(250);
    Serial.print(".");
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("[WiFi] Connected! IP: %s\n", WiFi.localIP().toString().c_str());
    delay(1500);
  } else {
    Serial.println("[WiFi] Connect FAILED.");
  }
}

// Gọi ở mỗi vòng loop để tự reconnect nếu mất WiFi
static void ensureWifi() {
  if (WiFi.status() == WL_CONNECTED) return;

  unsigned long now = millis();
  if (now - lastWifiTry < WIFI_RETRY_EVERY) return; // tránh spam connect liên tục

  lastWifiTry = now;
  Serial.println("[WiFi] Disconnected → retry wifi_connect()...");
  wifi_connect();
}

// === NEW (IR STATE) - GIỮ NGUYÊN LOGIC CŨ ===
static void send_ir_state(const char* state) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.printf("[IR] Skip send '%s' (WiFi down)\n", state);
    return;
  }

  HTTPClient http;
  // GIỮ NGUYÊN URL NHƯ CODE CỦA BẠN
  String url = "http://10.80.115.224:5000/api/face-unlock?device_id=DOOR-01";

  http.begin(url);
  int code = http.GET();

  if (code > 0) {
    Serial.printf("[IR] Sent state=%s, http=%d\n", state, code);
  } else {
    Serial.printf("[IR] Send failed: %s\n", http.errorToString(code).c_str());
  }

  http.end();
}

static void set_ir_state(IrStateEnum s) {
  if (s == g_lastIrState) return;
  g_lastIrState = s;

  const char* text = "unknown";
  switch (s) {
    case IR_STATE_WAITING:   text = "waiting";   break;
    case IR_STATE_DETECTING: text = "detecting"; break;
    default:                 text = "unknown";   break;
  }

  Serial.printf("[IR] Change state → %s\n", text);
  send_ir_state(text);
}

// ================== Camera Init ==================
static bool camera_init_qvga() {
  camera_config_t cfg = {};
  cfg.ledc_channel = LEDC_CHANNEL_0;
  cfg.ledc_timer   = LEDC_TIMER_0;
  cfg.pin_d0 = Y2_GPIO_NUM;  cfg.pin_d1 = Y3_GPIO_NUM;
  cfg.pin_d2 = Y4_GPIO_NUM;  cfg.pin_d3 = Y5_GPIO_NUM;
  cfg.pin_d4 = Y6_GPIO_NUM;  cfg.pin_d5 = Y7_GPIO_NUM;
  cfg.pin_d6 = Y8_GPIO_NUM;  cfg.pin_d7 = Y9_GPIO_NUM;
  cfg.pin_xclk = XCLK_GPIO_NUM;
  cfg.pin_pclk = PCLK_GPIO_NUM;
  cfg.pin_vsync = VSYNC_GPIO_NUM;
  cfg.pin_href  = HREF_GPIO_NUM;
  cfg.pin_sscb_sda = SIOD_GPIO_NUM;
  cfg.pin_sscb_scl = SIOC_GPIO_NUM;
  cfg.pin_pwdn  = PWDN_GPIO_NUM;
  cfg.pin_reset = RESET_GPIO_NUM;
  
  // --- THAY ĐỔI Ở ĐÂY ---
  cfg.xclk_freq_hz = 20000000; // Tăng lên 20MHz chuẩn
  cfg.pixel_format = PIXFORMAT_JPEG;

  cfg.frame_size = FRAMESIZE_QVGA; // Giữ nguyên QVGA để nhận diện nhanh
  cfg.jpeg_quality = 12;           // Giảm số này xuống (10-12) để ảnh NÉT hơn
  cfg.fb_count = 2;

  if (esp_camera_init(&cfg) != ESP_OK) return false;
  if (sensor_t* s = esp_camera_sensor_get()) {
    s->set_brightness(s, 0);
    s->set_saturation(s, 0);
    s->set_contrast(s, 0);
  }
  return true;
}

// ================== LƯU LOG VÀO DATABASE (GIỮ NGUYÊN) ==================
static void save_log_to_db(bool recognized, const String &who, int confidence) {
  HTTPClient http;
  String logUrl = "http://10.80.115.224:5000/api/logs";
  
  http.begin(logUrl);
  http.addHeader("Content-Type", "application/json");
  
  // Tạo JSON payload
  String status = recognized ? "granted" : "denied";
  String name = recognized ? who : "Unknown";
  String payload = "{";
  payload += "\"status\":\"" + status + "\",";
  payload += "\"recognized_name\":\"" + name + "\",";
  payload += "\"confidence\":" + String(confidence) + ",";
  payload += "\"source\":\"esp32_auto\"";
  payload += "}";
  
  Serial.printf("[LOG] Saving to DB: %s\n", payload.c_str());
  
  int httpCode = http.POST(payload);
  
  if (httpCode > 0) {
    Serial.printf("[LOG] Saved! HTTP %d\n", httpCode);
    if (httpCode == 200 || httpCode == 201) {
      String response = http.getString();
      Serial.printf("[LOG] Response: %s\n", response.c_str());
    }
  } else {
    Serial.printf("[LOG] Save failed: %s\n", http.errorToString(httpCode).c_str());
  }
  
  http.end();
}

// ================== GỬI ẢNH LÊN BACKEND (GIỮ NGUYÊN) ==================
static bool post_frame_to_backend(bool &recognized, String &who, int &confidence) {
  recognized = false;
  who = "";
  confidence = 0;

  camera_fb_t *fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("[CAM] Capture failed (fb == NULL)");
    return false;
  }

  HTTPClient http;
  http.begin(BACKEND_URL);
  http.addHeader("Content-Type", "image/jpeg");
  http.setTimeout(10000); // 10s timeout

  Serial.printf("[HTTP] POST %s (len=%u)\n", BACKEND_URL, fb->len);
  
  int status = http.POST((uint8_t*)fb->buf, fb->len);
  
  Serial.printf("[HTTP] Status code: %d\n", status);

  String body;
  if (status == 200) {
    body = http.getString();
    Serial.printf("[HTTP] Response length: %d bytes\n", body.length());
  } else {
    Serial.printf("[HTTP] Request FAILED. status=%d\n", status);
    String error = http.getString();
    if (error.length() > 0) {
      Serial.printf("[HTTP] Error response: %s\n", error.c_str());
    }
  }

  http.end();
  esp_camera_fb_return(fb);

  if (status != 200) {
    Serial.printf("[ERR] HTTP status %d != 200\n", status);
    return false;
  }

  if (body.length() == 0) {
    Serial.println("[ERR] Response body is empty!");
    return false;
  }

  Serial.println("========== RESPONSE BODY ==========");
  Serial.println(body);
  Serial.println("===================================");

  // Phân tích JSON response
  String low = body;
  low.toLowerCase();

  // Parse "recognized": true/false
  int recognizedIdx = low.indexOf("\"recognized\"");
  if (recognizedIdx >= 0) {
    int colonIdx = low.indexOf(':', recognizedIdx);
    int commaIdx = low.indexOf(',', colonIdx);
    if (commaIdx < 0) commaIdx = low.indexOf('}', colonIdx);
    
    String recognizedVal = low.substring(colonIdx + 1, commaIdx);
    recognizedVal.trim();
    recognized = (recognizedVal == "true");
  } else {
    Serial.println("[WARN] Cannot find 'recognized' field in JSON");
  }

  // Parse "name": "..."
  int nameIdx = low.indexOf("\"name\"");
  if (nameIdx >= 0) {
    int q1 = body.indexOf('"', nameIdx + 6);
    int q2 = body.indexOf('"', q1 + 1);
    if (q1 > 0 && q2 > q1) {
      who = body.substring(q1 + 1, q2);
    }
  }

  // Parse "confidence": 95
  int confIdx = low.indexOf("\"confidence\"");
  if (confIdx >= 0) {
    int colonIdx = low.indexOf(':', confIdx);
    int commaIdx = low.indexOf(',', colonIdx);
    if (commaIdx < 0) commaIdx = low.indexOf('}', colonIdx);
    
    String confVal = low.substring(colonIdx + 1, commaIdx);
    confVal.trim();
    confidence = confVal.toInt();
  }

  Serial.printf("[PARSE] Recognized=%s, Name='%s', Confidence=%d%%\n", 
    recognized ? "YES" : "NO", who.c_str(), confidence);

  return true;
}

// ================== SETUP ==================
void setup() {
  Serial.begin(115200);
  Serial.println();
  Serial.println("[BOOT] Face-Unlock + Servo (No LCD)");

  // 1. Khởi tạo Servo
  myDoorServo.setPeriodHertz(50);    // chuẩn 50Hz
  myDoorServo.attach(PIN_SERVO, 500, 2400); 
  myDoorServo.write(POS_CLOSE);      // Đóng cửa ngay khi bật

  // 2. Cảm biến IR
  pinMode(PIN_LM393, INPUT); 

  // 3. Init Camera
  if (!camera_init_qvga()) {
    Serial.println("[ERR] Camera init FAIL → restart");
    delay(2000);
    ESP.restart();
  }

  // Kết nối WiFi lần đầu
  wifi_connect();

  Serial.printf("[WiFi] %s - IP: %s\n",
    WiFi.isConnected() ? "Connected" : "FAILED",
    WiFi.localIP().toString().c_str()
  );

  startCameraServer();
  Serial.println("[HTTPD] Camera server started");

  delay(100);
  int initialState = digitalRead(PIN_LM393);
  Serial.printf("[LM393] Trạng thái ban đầu: %s (pin=%d)\n",
    initialState == HIGH ? "HIGH" : "LOW", initialState);
  
  // === NEW (IR STATE) ===
  bool initialMotion = (initialState == MOTION_ACTIVE_STATE);
  set_ir_state(initialMotion ? IR_STATE_DETECTING : IR_STATE_WAITING);

  Serial.println("[SYSTEM] Ready - Waiting IR...");
}

// ================== LOOP (trigger bằng LM393, style IOT + auto WiFi) ==================
void loop() {
  // 1) Luôn kiểm tra & tự reconnect WiFi
  ensureWifi();

  // 2) Đọc cảm biến
  unsigned long now   = millis();
  int sensor          = digitalRead(PIN_LM393);
  bool motionDetected = (sensor == MOTION_ACTIVE_STATE);

  // === NEW (IR STATE) ===
  // Cập nhật trạng thái cho web: ĐANG CHỜ / ĐANG NHẬN DIỆN
  set_ir_state(motionDetected ? IR_STATE_DETECTING : IR_STATE_WAITING);

  // Debug trạng thái định kỳ
  static unsigned long lastDebugPrint = 0;
  if (now - lastDebugPrint > 2000) {
    Serial.printf("[DEBUG] Sensor=%s, Motion=%s, gateLocked=%s, Checking=%s, WiFi=%s\n",
      sensor == HIGH ? "HIGH" : "LOW",
      motionDetected ? "YES" : "NO",
      gateLocked ? "YES" : "NO",
      isChecking ? "YES" : "NO",
      (WiFi.status() == WL_CONNECTED ? "OK" : "DOWN")
    );
    lastDebugPrint = now;
  }

  // 3) Nếu có chuyển động + chưa khóa + không bận + qua cooldown → xử lý
  if (motionDetected &&
      !gateLocked &&
      !isChecking &&
      (now - lastTrigger > COOLDOWN_MS)) {

    gateLocked  = true;       // khóa lại cho đến khi người rời khỏi vùng cảm biến
    lastTrigger = now;
    isChecking  = true;

    Serial.println();
    Serial.println("========================================");
    Serial.println("[LM393] PHÁT HIỆN CHUYỂN ĐỘNG!");
    
    // Nếu chưa có WiFi thì bỏ qua lần này
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("[WiFi] NOT CONNECTED → bỏ qua lần nhận diện này.");
      delay(2000);
      isChecking = false;
    } else {
      bool ok = false, recognized = false;
      String who;
      int confidence = 0;

      ok = post_frame_to_backend(recognized, who, confidence);

      if (!ok) {
        Serial.println("========================================");
        Serial.println("[ERR] Backend lỗi hoặc không phản hồi");
        delay(2000);
      }
      else if (recognized) {
        Serial.println("========================================");
        Serial.println("✅ NHẬN DIỆN THÀNH CÔNG!");
        Serial.printf("👤 Tên: %s\n", who.c_str());
        
        // LƯU LOG VÀO DATABASE
        save_log_to_db(true, who, confidence);

        // Mở Servo
        myDoorServo.write(POS_OPEN);
        delay(3000); // Giữ cửa mở 3s
        
        // Đóng cửa
        myDoorServo.write(POS_CLOSE);
        delay(1000);
      }
      else {
        Serial.println("========================================");
        Serial.println("❌ TỪ CHỐI!");
        
        // LƯU LOG VÀO DATABASE
        save_log_to_db(false, who, confidence);

        delay(2000); // Giữ thông báo từ chối (chỉ còn trên Serial)
      }

      Serial.printf("[DONE] Hoàn thành. Cooldown %lu giây\n\n", COOLDOWN_MS / 1000);
      isChecking = false;
    }
  }

  // 4) Khi cảm biến KHÔNG còn ở trạng thái ACTIVE → mở khóa gateLocked cho lượt tiếp theo
  if (!motionDetected) {
    gateLocked = false;
  }

  // 5) Delay nhỏ
  delay(40);
}
