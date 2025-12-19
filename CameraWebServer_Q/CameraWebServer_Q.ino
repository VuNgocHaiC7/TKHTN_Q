#include <Arduino.h>
#include <WiFi.h>
#include "esp_camera.h"
#include "board_config.h"
#include "camera_pins.h"
#include "esp_http_client.h"
#include <HTTPClient.h>

// === THƯ VIỆN SERVO ===
#include <ESP32Servo.h>

// ================== WiFi ==================
static const char* WIFI_SSID   = "Q";
static const char* WIFI_PASS   = "1709200004";
static const char* BACKEND_URL = "http://10.87.241.224:5000/api/face-unlock";  // Flask Python API
static const char* IR_STATE_URL = "http://10.87.241.224:5000/api/ir-state";

// ================== CẤU HÌNH CHÂN ==================
#define PIN_SERVO 2

// Vẫn giữ chân 13 cho IR như cũ (bạn không cần đổi lại dây)
const int PIN_LM393 = 13; 

// KHỞI TẠO ĐỐI TƯỢNG
Servo myDoorServo;

// Cấu hình góc Servo
const int POS_CLOSE = 0;    
const int POS_OPEN  = 180;  

// QUAN TRỌNG: Kiểm tra module của bạn (LOW hoặc HIGH tùy loại cảm biến)
const int MOTION_ACTIVE_STATE = LOW;  

// Trạng thái xử lý nhận diện
bool isChecking = false;

// Chống spam cảm biến
bool gateLocked = false;             
unsigned long lastTrigger = 0;
const unsigned long COOLDOWN_MS = 10000;   

// Auto WiFi reconnect
unsigned long lastWifiTry = 0;
const unsigned long WIFI_RETRY_EVERY = 10000; 

// === IR STATE ===
enum IrStateEnum {
  IR_STATE_UNKNOWN = 0,
  IR_STATE_WAITING,
  IR_STATE_DETECTING
};

IrStateEnum g_lastIrState = IR_STATE_UNKNOWN;

// ================== prototype ==================
void startCameraServer();
static void set_ir_state(IrStateEnum s);
void showMessage(String line1, String line2); // Hàm hiển thị (giờ chỉ in Serial)

// ================== WiFi ==================
static void wifi_connect() {
  Serial.printf("[WiFi] Connecting to SSID: %s\n", WIFI_SSID);
  showMessage("WiFi Connecting", "SSID: " + String(WIFI_SSID));

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) { 
    delay(250);
    Serial.print(".");
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("[WiFi] Connected! IP: %s\n", WiFi.localIP().toString().c_str());
    showMessage("WiFi Connected", WiFi.localIP().toString());
    delay(1500);
  } else {
    Serial.println("[WiFi] Connect FAILED.");
    showMessage("WiFi Error", "Check Router!");
    delay(2000);
  }
}

static void ensureWifi() {
  if (WiFi.status() == WL_CONNECTED) return;

  unsigned long now = millis();
  if (now - lastWifiTry < WIFI_RETRY_EVERY) return; 

  lastWifiTry = now;
  Serial.println("[WiFi] Disconnected -> retry wifi_connect()...");
  showMessage("WiFi Lost", "Reconnecting...");
  wifi_connect();
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
  Serial.printf("[IR] State changed -> %s\n", text);
  
  // Gửi state lên Flask API
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String url = String(IR_STATE_URL) + "?state=" + String(text);
    http.begin(url);
    http.setTimeout(2000);
    int code = http.GET();
    http.end();
    
    if (code == 200) {
      Serial.printf("[IR] Sent state to server: %s\n", text);
    } else {
      Serial.printf("[IR] Failed to send state (HTTP %d)\n", code);
    }
  }
}

// ================== Helper Thay thế LCD ==================
// Vì đã bỏ LCD, hàm này sẽ in ra Serial để bạn tiện theo dõi lỗi nếu có
void showMessage(String line1, String line2) {
  Serial.println(">>> [STATUS] " + line1 + " | " + line2);
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
    s->set_contrast(s, 0);
  }
  return true;
}

// ================== LƯU LOG & GỬI ẢNH ==================
static void save_log_to_db(bool recognized, const String &who, int confidence) {
  HTTPClient http;
  String logUrl = "http://10.87.241.224:5000/api/logs";
  
  http.begin(logUrl);
  http.addHeader("Content-Type", "application/json");
  
  String status = recognized ? "granted" : "denied";
  String name = recognized ? who : "Unknown";
  String payload = "{";
  payload += "\"status\":\"" + status + "\",";
  payload += "\"recognized_name\":\"" + name + "\",";
  payload += "\"confidence\":" + String(confidence) + ",";
  payload += "\"source\":\"esp32_auto\"";
  payload += "}";
  
  http.POST(payload);
  http.end();
}

static bool post_frame_to_backend(bool &recognized, String &who, int &confidence) {
  recognized = false;
  who = "";
  confidence = 0;

  camera_fb_t *fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("[CAM] Capture failed");
    return false;
  }

  showMessage("Analyzing...", "Please wait");

  HTTPClient http;
  http.begin(BACKEND_URL);
  http.addHeader("Content-Type", "image/jpeg");
  http.setTimeout(10000); 
  
  int status = http.POST((uint8_t*)fb->buf, fb->len);
  String body;
  if (status == 200) body = http.getString();
  
  http.end();
  esp_camera_fb_return(fb);

  if (status != 200 || body.length() == 0) return false;

  String low = body;
  low.toLowerCase();

  int recognizedIdx = low.indexOf("\"recognized\"");
  if (recognizedIdx >= 0) {
    int colonIdx = low.indexOf(':', recognizedIdx);
    int commaIdx = low.indexOf(',', colonIdx);
    if (commaIdx < 0) commaIdx = low.indexOf('}', colonIdx);
    String recognizedVal = low.substring(colonIdx + 1, commaIdx);
    recognizedVal.trim();
    recognized = (recognizedVal == "true");
  }

  int nameIdx = low.indexOf("\"name\"");
  if (nameIdx >= 0) {
    int q1 = body.indexOf('"', nameIdx + 6);
    int q2 = body.indexOf('"', q1 + 1);
    if (q1 > 0 && q2 > q1) who = body.substring(q1 + 1, q2);
  }

  int confIdx = low.indexOf("\"confidence\"");
  if (confIdx >= 0) {
    int colonIdx = low.indexOf(':', confIdx);
    int commaIdx = low.indexOf(',', colonIdx);
    if (commaIdx < 0) commaIdx = low.indexOf('}', colonIdx);
    String confVal = low.substring(colonIdx + 1, commaIdx);
    confVal.trim();
    confidence = confVal.toInt();
  }

  return true;
}

// ================== SETUP ==================
void setup() {
  Serial.begin(115200);
  
  showMessage("System Booting", "Face Unlock v2 (No LCD)");
  delay(1000);

  // 1. Khởi tạo Servo
  myDoorServo.setPeriodHertz(50);    
  myDoorServo.attach(PIN_SERVO, 500, 2400); 
  myDoorServo.write(POS_CLOSE);      
  delay(500);                        
  myDoorServo.detach();              

  // 2. Cảm biến IR (Chân 13)
  pinMode(PIN_LM393, INPUT); 

  // 3. Init Camera
  if (!camera_init_qvga()) {
    Serial.println("[ERR] Camera init FAIL");
    showMessage("Camera Error", "Init Failed");
    delay(2000);
    ESP.restart();
  }

  // Kết nối WiFi
  wifi_connect();

  startCameraServer();
  
  int initialState = digitalRead(PIN_LM393);
  bool initialMotion = (initialState == MOTION_ACTIVE_STATE);
  set_ir_state(initialMotion ? IR_STATE_DETECTING : IR_STATE_WAITING);

  Serial.println("[SYSTEM] Ready");
  showMessage("System Ready", "Waiting IR...");
}

// ================== LOOP ==================
void loop() {
  ensureWifi();

  unsigned long now   = millis();
  int sensor          = digitalRead(PIN_LM393);
  bool motionDetected = (sensor == MOTION_ACTIVE_STATE);

  set_ir_state(motionDetected ? IR_STATE_DETECTING : IR_STATE_WAITING);

  // Hiển thị trạng thái chờ nếu không làm gì
  if (!isChecking && !gateLocked && !motionDetected && (now - lastTrigger > 2000)) {
     static unsigned long lastUpdate = 0;
     if (now - lastUpdate > 5000) {
        // Chỉ in log nhẹ nhàng để biết hệ thống vẫn sống
        // Serial.println("[IDLE] Waiting..."); 
        lastUpdate = now;
     }
  }

  // Xử lý logic chính
  if (motionDetected && !gateLocked && !isChecking && (now - lastTrigger > COOLDOWN_MS)) {

    gateLocked  = true;      
    lastTrigger = now;
    isChecking  = true;

    Serial.println("[LM393] PHÁT HIỆN -> Bắt đầu nhận diện");
    
    if (WiFi.status() != WL_CONNECTED) {
      showMessage("WiFi Error", "No Connection");
      delay(2000);
      isChecking = false;
    } else {
      bool ok = false, recognized = false;
      String who;
      int confidence = 0;

      ok = post_frame_to_backend(recognized, who, confidence);

      if (!ok) {
        showMessage("Server Error", "No Response");
        delay(2000);
      }
      else if (recognized) {
        Serial.println("✅ NHẬN DIỆN THÀNH CÔNG!");
        
        showMessage("Welcome:", who);
        // Log đã được lưu bởi backend Flask khi gọi /api/face-unlock

        // Mở Servo
        myDoorServo.attach(PIN_SERVO, 500, 2400); 
        delay(100);                               
        myDoorServo.write(POS_OPEN);
        
        // Thông báo mở cửa
        delay(1000);
        showMessage("Door Opened", "Please Enter");

        delay(2000);                               
        
        // Chờ người đi qua
        unsigned long waitStart = millis();
        const unsigned long MAX_WAIT = 10000;
        
        while (millis() - waitStart < MAX_WAIT) {
          int currentSensor = digitalRead(PIN_LM393);
          if (currentSensor != MOTION_ACTIVE_STATE) {
            delay(500);
            if (digitalRead(PIN_LM393) != MOTION_ACTIVE_STATE) break;
          }
          delay(100);
        }
        
        // Đóng cửa
        showMessage("Door Closing", "Goodbye!");
        myDoorServo.write(POS_CLOSE);              
        delay(2000);                                
        myDoorServo.detach();                       
      }
      else {
        Serial.println("❌ TỪ CHỐI!");
        
        showMessage("Access Denied", "Unknown Face");
        // Log đã được lưu bởi backend Flask khi gọi /api/face-unlock
        
        delay(3000); 
      }

      isChecking = false;
      showMessage("System Ready", "Waiting...");
    }
  }

  if (!motionDetected) {
    gateLocked = false;
  }

  delay(40);
}