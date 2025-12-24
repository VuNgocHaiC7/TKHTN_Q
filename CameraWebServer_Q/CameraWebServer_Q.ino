#include <Arduino.h>
#include <WiFi.h>
#include "esp_camera.h"
#include "board_config.h"
#include "camera_pins.h"
#include "esp_http_client.h"
#include <HTTPClient.h>
#include <ESP32Servo.h> 

// ================== CẤU HÌNH NGƯỜI DÙNG ==================
static const char* WIFI_SSID   = "Q";
static const char* WIFI_PASS   = "1709200004";
static const char* BACKEND_URL = "http://10.87.241.224:5000/api/face-unlock";  
static const char* IR_STATE_URL = "http://10.87.241.224:5000/api/ir-state";

// ================== CẤU HÌNH CHÂN ==================
#define PIN_SERVO 2
#define PIN_BUZZER 14       
const int PIN_LM393 = 13;   

// ================== BIẾN TOÀN CỤC ==================
Servo myDoorServo;
const int POS_CLOSE = 10;    
const int POS_OPEN  = 180;  

int failedCount = 0;        
const int MAX_FAILED = 3;   
bool g_manualUnlock = false;

const int MOTION_ACTIVE_STATE = LOW; 
bool isChecking = false;
bool gateLocked = false;             
unsigned long lastTrigger = 0;
const unsigned long COOLDOWN_MS = 10000;   

unsigned long lastWifiTry = 0;
const unsigned long WIFI_RETRY_EVERY = 10000; 

enum IrStateEnum {
  IR_STATE_UNKNOWN = 0,
  IR_STATE_WAITING,
  IR_STATE_DETECTING
};
IrStateEnum g_lastIrState = IR_STATE_UNKNOWN;

// ================== PROTOTYPE ==================
void startCameraServer(); 
static void set_ir_state(IrStateEnum s);
void showMessage(String line1, String line2);
void triggerAlarm();

// ================== CÁC HÀM HỖ TRỢ ==================

void showMessage(String line1, String line2) {
  Serial.println(">>> [STATUS] " + line1 + " | " + line2);
}

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
  
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String url = String(IR_STATE_URL) + "?state=" + String(text);
    http.begin(url);
    http.setTimeout(2000);
    int code = http.GET();
    http.end();
  }
}

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
  
  sensor_t* s = esp_camera_sensor_get();
  if (s) {
    s->set_brightness(s, 0);
    s->set_saturation(s, 0);
    s->set_contrast(s, 0);
  }
  return true;
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

void openGate() {
  Serial.println("[DOOR] Opening via Request/FaceID...");
  
  myDoorServo.attach(PIN_SERVO, 500, 2400);
  delay(100); 
  myDoorServo.write(POS_OPEN);
  Serial.println("   -> Door OPEN");
  
  delay(5000); 

  Serial.println("   -> Door CLOSING");
  myDoorServo.write(POS_CLOSE);
  delay(2000);
  
  myDoorServo.detach();
  Serial.println("[DOOR] Locked.");
}

// Hàm báo động đã sửa lại cho phù hợp với cách đấu nối mới (Chân 14 + GND)
void triggerAlarm() {
  Serial.println("[ALARM] CẢNH BÁO: SAI QUÁ 3 LẦN!");
  showMessage("ALARM!!!", "Intruder Alert");
  
  // LOGIC MỚI: HIGH = BẬT, LOW = TẮT
  for (int i = 0; i < 3; i++) {
    digitalWrite(PIN_BUZZER, HIGH);  // Bật (3.3V)
    delay(500);                     
    digitalWrite(PIN_BUZZER, LOW);   // Tắt (0V)
    delay(300);                     
  }
}

// ================== SETUP ==================
void setup() {
  Serial.begin(115200);

  // 1. TẮT BUZZER NGAY LẬP TỨC
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_BUZZER, LOW); // Đảm bảo mức thấp ngay từ đầu
  
  showMessage("System Booting", "Face Unlock");
  delay(1000);

  // 2. Khởi tạo Servo
  myDoorServo.setPeriodHertz(50);    
  myDoorServo.attach(PIN_SERVO, 500, 2400); 
  myDoorServo.write(POS_CLOSE);      
  delay(500);                        
  myDoorServo.detach();       

  // 3. Cảm biến IR 
  pinMode(PIN_LM393, INPUT); 

  // 4. Init Camera
  if (!camera_init_qvga()) {
    Serial.println("[ERR] Camera init FAIL");
    showMessage("Camera Error", "Init Failed");
    delay(2000);
    ESP.restart();
  }

  // Chốt tắt Buzzer lần nữa (đề phòng thư viện camera reset chân)
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_BUZZER, LOW); 

  // 5. Kết nối WiFi
  wifi_connect();
  
  // Chốt tắt Buzzer lần cuối sau khi WiFi connect
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_BUZZER, LOW); 

  // 6. Start Web Server
  startCameraServer();

  // Kiểm tra trạng thái IR ban đầu
  int initialState = digitalRead(PIN_LM393);
  bool initialMotion = (initialState == MOTION_ACTIVE_STATE);
  set_ir_state(initialMotion ? IR_STATE_DETECTING : IR_STATE_WAITING);

  Serial.println("[SYSTEM] Ready");
  showMessage("System Ready", "Waiting IR...");
}

// ================== LOOP ==================
void loop() {
  ensureWifi(); 

  if (g_manualUnlock) {
    g_manualUnlock = false; 
    openGate();            
  }

  unsigned long now = millis();
  int sensor = digitalRead(PIN_LM393);
  bool motionDetected = (sensor == MOTION_ACTIVE_STATE);

  set_ir_state(motionDetected ? IR_STATE_DETECTING : IR_STATE_WAITING);

  if (!isChecking && !gateLocked && !motionDetected && (now - lastTrigger > 2000)) {
     static unsigned long lastUpdate = 0;
     if (now - lastUpdate > 5000) {
        lastUpdate = now;
     }
  }

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
        Serial.println("NHẬN DIỆN THÀNH CÔNG!");
        failedCount = 0; 
        
        showMessage("Welcome:", who);
        openGate(); 
      }
      else {
        Serial.println("TỪ CHỐI!");
        failedCount++;
        Serial.printf("Sai lần thứ: %d/%d\n", failedCount, MAX_FAILED);

        if (failedCount >= MAX_FAILED) {
            triggerAlarm(); 
            failedCount = 0;
        } else {
            showMessage("Access Denied", "Unknown Face");
            delay(3000); 
        }
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