// CameraWebServer_Final_Full_LCD.ino
// Face Unlock (LM393 + WiFi + IR State + Servo + LCD 16x2 I2C)
// Core ESP32 3.3.x

#include <Arduino.h>
#include <WiFi.h>
#include "esp_camera.h"
#include "board_config.h"
#include "camera_pins.h"
#include "esp_http_client.h"
#include <HTTPClient.h>

// === THƯ VIỆN SERVO ===
#include <ESP32Servo.h>

// === THƯ VIỆN LCD I2C (MỚI) ===
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ================== WiFi ==================
static const char* WIFI_SSID   = "Q";
static const char* WIFI_PASS   = "1709200004";
static const char* BACKEND_URL = "http://10.87.241.224:5000/api/face-unlock";  // Flask Python API
static const char* IR_STATE_URL = "http://10.87.241.224:5000/api/ir-state";

// ================== CẤU HÌNH CHÂN (ĐÃ ĐỔI) ==================
#define PIN_SERVO 2

// ĐỔI CHÂN IR SANG 13 ĐỂ DÙNG 14, 15 CHO I2C
const int PIN_LM393 = 13; 

// Cấu hình I2C cho ESP32-CAM (Sử dụng chân cũ của IR và chân SD)
#define I2C_SDA 14
#define I2C_SCL 15

// KHỞI TẠO ĐỐI TƯỢNG
Servo myDoorServo;
// Địa chỉ I2C thường là 0x27 hoặc 0x3F. Nếu không lên chữ hãy thử đổi 0x3F
LiquidCrystal_I2C lcd(0x27, 16, 2); 

// Cấu hình góc Servo
const int POS_CLOSE = 0;    
const int POS_OPEN  = 180;  

// QUAN TRỌNG: Kiểm tra module của bạn
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
void showMessage(String line1, String line2); // Hàm hiển thị LCD

// ================== WiFi ==================
static void wifi_connect() {
  Serial.printf("[WiFi] Connecting to SSID: %s\n", WIFI_SSID);
  
  // Hiển thị LCD
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
}

// ================== Helper LCD ==================
void showMessage(String line1, String line2) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1);
  lcd.setCursor(0, 1);
  lcd.print(line2);
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
    showMessage("Camera Error", "Capture Failed");
    return false;
  }

  // Báo lên LCD đang gửi
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
  
  // 1. Khởi tạo LCD I2C
  // QUAN TRỌNG: Định nghĩa chân SDA, SCL cho ESP32-CAM
  Wire.begin(I2C_SDA, I2C_SCL); 
  lcd.init();
  lcd.backlight();
  showMessage("System Booting", "Face Unlock v2");
  delay(1000);

  // 2. Khởi tạo Servo
  myDoorServo.setPeriodHertz(50);    
  myDoorServo.attach(PIN_SERVO, 500, 2400); 
  myDoorServo.write(POS_CLOSE);      
  delay(500);                        
  myDoorServo.detach();              

  // 3. Cảm biến IR (Chân 13)
  pinMode(PIN_LM393, INPUT); 

  // 4. Init Camera
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

  // Nếu phát hiện người, cập nhật LCD (nếu chưa đang check)
  if (motionDetected && !isChecking && !gateLocked) {
     showMessage("Motion Detected", "Scanning...");
  } else if (!isChecking && !gateLocked && !motionDetected && (now - lastTrigger > 2000)) {
     // Hiển thị trạng thái chờ nếu không làm gì
     static unsigned long lastLcdUpdate = 0;
     if (now - lastLcdUpdate > 5000) {
        showMessage("System Ready", "Waiting...");
        lastLcdUpdate = now;
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

      // Hàm này đã có LCD "Analyzing..." bên trong
      ok = post_frame_to_backend(recognized, who, confidence);

      if (!ok) {
        showMessage("Server Error", "No Response");
        delay(2000);
      }
      else if (recognized) {
        Serial.println("✅ NHẬN DIỆN THÀNH CÔNG!");
        
        // Hiển thị LCD Chào mừng
        showMessage("Welcome:", who);
        
        save_log_to_db(true, who, confidence);

        // Mở Servo
        myDoorServo.attach(PIN_SERVO, 500, 2400); 
        delay(100);                               
        myDoorServo.write(POS_OPEN);
        
        // Thông báo mở cửa
        delay(1000);
        showMessage("Door Opened", "Please Enter");

        delay(2000);                               
        
        // Chờ người đi qua (giữ nguyên logic cũ)
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
        
        // Hiển thị LCD Từ chối
        showMessage("Access Denied", "Unknown Face");
        
        save_log_to_db(false, who, confidence);
        delay(3000); // Giữ thông báo lâu chút để người dùng đọc
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