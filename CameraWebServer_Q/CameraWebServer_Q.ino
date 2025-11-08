// CameraWebServer_Q.ino — Face Unlock (theo kiến trúc tối ưu: ESP32 chỉ chụp + POST)
// Core ESP32 3.3.x

#include <WiFi.h>
#include "esp_camera.h"
#include "board_config.h"   // chọn CAMERA_MODEL_AI_THINKER
#include "camera_pins.h"
#include "esp_http_client.h"

// ================== Cấu hình của bạn ==================
static const char* WIFI_SSID = "TP-Link_F6D2";
static const char* WIFI_PASS = "1223334444";
static const char* BACKEND_URL = "http://192.168.0.103/Project_Q/public"; // endpoint backend

// ================== Phần cứng mở khóa/đèn ==================
#define PIN_RELAY  2     // đổi theo mạch thực tế
#define PIN_LED_OK 12
#define PIN_LED_NG 13

// KHÔNG dùng extern "C" để tránh lỗi link
void startCameraServer();   // được định nghĩa trong app_httpd.cpp (bản tối ưu)

// -------------------- WiFi --------------------
static void wifi_connect() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) { delay(250); }
}

// -------------------- Camera --------------------
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
  // Tối ưu tốc độ: QVGA + quality 15 (có thể nâng nếu backend khỏe)
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

// -------------------- POST JPEG lên backend --------------------
// Backend trả JSON dạng tối ưu: {"recognized":true,"name":"...","studentCode":"..."}
static bool post_frame_to_backend(bool &recognized, String &who) {
  recognized = false; who = "";

  camera_fb_t *fb = esp_camera_fb_get();
  if (!fb) return false;

  esp_http_client_config_t cfg = {};
  cfg.url = BACKEND_URL;
  cfg.method = HTTP_METHOD_POST;
  cfg.timeout_ms = 8000;

  esp_http_client_handle_t cli = esp_http_client_init(&cfg);
  if (!cli) { esp_camera_fb_return(fb); return false; }

  esp_http_client_set_header(cli, "Content-Type", "image/jpeg");
  esp_http_client_set_post_field(cli, (const char*)fb->buf, fb->len);

  esp_err_t err = esp_http_client_perform(cli);
  int status = (err == ESP_OK) ? esp_http_client_get_status_code(cli) : -1;

  String body;
  if (status == 200) {
    int len = esp_http_client_get_content_length(cli);
    if (len < 0) len = 2048;
    body.reserve(len > 0 ? len : 2048);

    char buf[256];
    while (true) {
      int r = esp_http_client_read(cli, buf, sizeof(buf));
      if (r <= 0) break;
      body.concat(String(buf).substring(0, r));
    }
  }

  esp_http_client_cleanup(cli);
  esp_camera_fb_return(fb);

  // Parse nhẹ để giữ tốc độ (không kéo thư viện JSON nặng)
  String low = body; low.toLowerCase();
  recognized = low.indexOf("\"recognized\":true") >= 0;

  int iName = low.indexOf("\"name\"");
  if (iName >= 0) {
    int q1 = body.indexOf('"', iName + 6);
    int q2 = body.indexOf('"', q1 + 1);
    if (q1 > 0 && q2 > q1) who = body.substring(q1 + 1, q2);
  }
  return (status == 200);
}

void setup() {
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_LED_OK, OUTPUT);
  pinMode(PIN_LED_NG, OUTPUT);
  digitalWrite(PIN_RELAY, LOW);
  digitalWrite(PIN_LED_OK, LOW);
  digitalWrite(PIN_LED_NG, LOW);

  Serial.begin(115200);
  Serial.println("\n[BOOT] Face-Unlock (optimized pipeline)");

  if (!camera_init_qvga()) {
    Serial.println("[ERR] Camera init failed"); delay(2000); ESP.restart();
  }

  wifi_connect();
  Serial.printf("[WIFI] %s, IP=%s\n",
    WiFi.isConnected() ? "Connected" : "Failed",
    WiFi.isConnected() ? WiFi.localIP().toString().c_str() : "-");

  // Bắt đầu webserver tối ưu (stream/capture cho debug)
  startCameraServer();
  Serial.println("[HTTPD] camera server started");
}

unsigned long lastTry = 0;
const unsigned long TRY_INTERVAL_MS = 1500;   // theo kiến trúc cũ: lấy mẫu định kỳ nhanh

void loop() {
  if (millis() - lastTry < TRY_INTERVAL_MS) return;
  lastTry = millis();

  bool ok = false, recognized = false;
  String who;
  ok = post_frame_to_backend(recognized, who);

  if (!ok) {
    // Lỗi kết nối backend: chớp LED đỏ ngắn (không chặn luồng)
    digitalWrite(PIN_LED_NG, HIGH); delay(120); digitalWrite(PIN_LED_NG, LOW);
    return;
  }

  if (recognized) {
    Serial.printf("[UNLOCK] Welcome %s\n", who.c_str());
    digitalWrite(PIN_LED_OK, HIGH);
    digitalWrite(PIN_RELAY, HIGH); // mở khóa
    delay(3000);
    digitalWrite(PIN_RELAY, LOW);
    digitalWrite(PIN_LED_OK, LOW);
  } else {
    Serial.println("[DENY] Face not recognized");
    for (int i=0;i<2;++i){ digitalWrite(PIN_LED_NG, HIGH); delay(80); digitalWrite(PIN_LED_NG, LOW); delay(80); }
  }
}
