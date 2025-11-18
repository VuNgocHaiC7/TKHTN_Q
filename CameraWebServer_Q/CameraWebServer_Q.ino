// CameraWebServer_Q.ino — Face Unlock (LM393 trigger + auto WiFi reconnect)
// Core ESP32 3.3.x

#include <Arduino.h>
#include <WiFi.h>
#include "esp_camera.h"
#include "board_config.h"
#include "camera_pins.h"
#include "esp_http_client.h"
#include <HTTPClient.h>

// ================== WiFi ==================
static const char* WIFI_SSID   = "Q";
static const char* WIFI_PASS   = "1709200004";
static const char* BACKEND_URL = "http://10.80.115.224:5000/api/face-unlock";  // Flask Python API

// ================== Relay + LED ==================
#define PIN_RELAY  2
#define PIN_LED_OK 12
#define PIN_LED_NG 13

// ================== CẢM BIẾN HỒNG NGOẠI (LM393) ==================
const int PIN_LM393 = 14;   // chọn chân không trùng LED/Relay (14 là an toàn)

// QUAN TRỌNG: Kiểm tra module của bạn
// - Nếu LED module SÁNG khi có người → đổi thành HIGH
// - Nếu LED module TẮT khi có người → để LOW
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

// ================== prototype ==================
void startCameraServer();

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
  cfg.xclk_freq_hz = 20000000;
  cfg.pixel_format = PIXFORMAT_JPEG;
  cfg.frame_size = FRAMESIZE_QVGA;
  cfg.jpeg_quality = 12;
  cfg.fb_count = 2;

  if (esp_camera_init(&cfg) != ESP_OK) return false;
  if (sensor_t* s = esp_camera_sensor_get()) {
    s->set_brightness(s, 0);
    s->set_saturation(s, 0);
  }
  return true;
}

// ================== LƯU LOG VÀO DATABASE ==================
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

// ================== GỬI ẢNH LÊN BACKEND ==================
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
  Serial.println("[BOOT] Face-Unlock + LM393 (IOT-style trigger + auto WiFi)");

  pinMode(PIN_RELAY,  OUTPUT);
  pinMode(PIN_LED_OK, OUTPUT);
  pinMode(PIN_LED_NG, OUTPUT);
  pinMode(PIN_LM393,  INPUT); // hoặc INPUT_PULLUP tùy wiring thực tế

  digitalWrite(PIN_RELAY, LOW);
  digitalWrite(PIN_LED_OK, LOW);
  digitalWrite(PIN_LED_NG, LOW);

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
  Serial.printf("[LM393] Kích hoạt khi: %s\n",
    MOTION_ACTIVE_STATE == HIGH ? "HIGH (module sáng khi có người)"
                                : "LOW (module tắt khi có người)");
  Serial.println("[LM393] Sẵn sàng phát hiện chuyển động...");
}

// ================== LOOP (trigger bằng LM393, style IOT + auto WiFi) ==================
void loop() {
  // 1) Luôn kiểm tra & tự reconnect WiFi
  ensureWifi();

  // 2) Đọc cảm biến
  unsigned long now   = millis();
  int sensor          = digitalRead(PIN_LM393);
  bool motionDetected = (sensor == MOTION_ACTIVE_STATE);

  // Debug trạng thái định kỳ
  static unsigned long lastDebugPrint = 0;
  if (now - lastDebugPrint > 2000) {
    Serial.printf("[DEBUG] Sensor=%s, Motion=%s, gateLocked=%s, Checking=%s, "
                  "Cooldown=%lums, WiFi=%s\n",
      sensor == HIGH ? "HIGH" : "LOW",
      motionDetected ? "YES" : "NO",
      gateLocked ? "YES" : "NO",
      isChecking ? "YES" : "NO",
      (now > lastTrigger) ? (now - lastTrigger) : 0,
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
    Serial.println("[LM393] → Bắt đầu nhận diện khuôn mặt...");
    Serial.println("========================================");

    // Nếu chưa có WiFi thì bỏ qua lần này
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("[WiFi] NOT CONNECTED → bỏ qua lần nhận diện này.");
      isChecking = false;
    } else {
      bool ok = false, recognized = false;
      String who;
      int confidence = 0;

      ok = post_frame_to_backend(recognized, who, confidence);

      if (!ok) {
        Serial.println("========================================");
        Serial.println("[ERR] Backend lỗi hoặc không phản hồi");
        Serial.println("[ACTION] Chớp đỏ 1 lần");
        Serial.println("========================================");
        
        digitalWrite(PIN_LED_NG, HIGH);
        delay(200);
        digitalWrite(PIN_LED_NG, LOW);
      }
      else if (recognized) {
        Serial.println("========================================");
        Serial.println("✅ NHẬN DIỆN THÀNH CÔNG!");
        Serial.printf("👤 Tên: %s\n", who.c_str());
        Serial.printf("📊 Độ chính xác: %d%%\n", confidence);
        Serial.println("🚪 Mở cửa 2.5 giây...");
        Serial.println("========================================");

        // LƯU LOG VÀO DATABASE
        save_log_to_db(true, who, confidence);

        // Bật LED xanh và relay
        digitalWrite(PIN_LED_OK, HIGH);
        digitalWrite(PIN_RELAY, HIGH);
        delay(2500);
        digitalWrite(PIN_RELAY, LOW);
        digitalWrite(PIN_LED_OK, LOW);
      }
      else {
        Serial.println("========================================");
        Serial.println("❌ TỪNG CHỐI!");
        Serial.println("⚠️ Không nhận diện được khuôn mặt");
        if (who.length() > 0) {
          Serial.printf("ℹ️ Phát hiện: %s (độ chính xác thấp)\n", who.c_str());
        }
        Serial.println("🚫 Chớp đỏ 2 lần");
        Serial.println("========================================");

        // LƯU LOG VÀO DATABASE
        save_log_to_db(false, who, confidence);

        // Chớp đỏ 2 lần
        for (int i = 0; i < 2; i++) {
          digitalWrite(PIN_LED_NG, HIGH); delay(120);
          digitalWrite(PIN_LED_NG, LOW);  delay(120);
        }
      }

      Serial.printf("[DONE] Hoàn thành. Cooldown %lu giây\n\n", COOLDOWN_MS / 1000);
      isChecking = false;
    }
  }

  // 4) Khi cảm biến KHÔNG còn ở trạng thái ACTIVE → mở khóa gateLocked cho lượt tiếp theo
  if (!motionDetected) {
    gateLocked = false;
  }

  // 5) Delay nhỏ để tránh đọc cảm biến quá dày, đỡ nhiễu
  delay(40);
}
