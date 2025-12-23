#include <Arduino.h>
#include "esp_http_server.h"
#include "esp_timer.h"
#include "esp_camera.h"
#include "img_converters.h"
#include "fb_gfx.h"
#include "esp32-hal-ledc.h"
#include "sdkconfig.h"
#include "camera_index.h"
#include "board_config.h"

#if defined(ARDUINO_ARCH_ESP32) && defined(CONFIG_ARDUHAL_ESP_LOG)
  #include "esp32-hal-log.h"
#else
  #define log_i(...)
  #define log_e(...)
#endif

// Biến toàn cục từ file chính (.ino)
extern bool g_manualUnlock;
extern bool isChecking;
extern unsigned long lastTrigger;

// Callback
extern "C" void esp32cam_on_face_presence(bool present);

#define PART_BOUNDARY "123456789000000000000987654321"
static const char *_STREAM_CONTENT_TYPE = "multipart/x-mixed-replace;boundary=" PART_BOUNDARY;
static const char *_STREAM_BOUNDARY = "\r\n--" PART_BOUNDARY "\r\n";
static const char *_STREAM_PART = "Content-Type: image/jpeg\r\nContent-Length: %u\r\nX-Timestamp: %d.%06d\r\n\r\n";

httpd_handle_t stream_httpd = NULL;
httpd_handle_t camera_httpd = NULL;

// LED flash support
#if defined(LED_GPIO_NUM)
  #define CONFIG_LED_MAX_INTENSITY 255
  int led_duty = 0;
  bool isStreaming = false;
#endif

// Moving average filter for FPS
typedef struct {
  size_t size;
  size_t index;
  size_t count;
  int sum;
  int *values;
} ra_filter_t;

static ra_filter_t ra_filter;

static ra_filter_t *ra_filter_init(ra_filter_t *f, size_t n) {
  memset(f, 0, sizeof(ra_filter_t));
  f->values = (int *)malloc(n * sizeof(int));
  if (!f->values) return NULL;
  memset(f->values, 0, n * sizeof(int));
  f->size = n;
  return f;
}

static int ra_filter_run(ra_filter_t *f, int val) {
  if (!f->values) return val;
  f->sum -= f->values[f->index];
  f->values[f->index] = val;
  f->sum += f->values[f->index];
  f->index = (f->index + 1) % f->size;
  if (f->count < f->size) f->count++;
  return f->sum / f->count;
}

// ========== FACE DETECT FLAG ==========
static int detection_enabled = 0;

#if defined(LED_GPIO_NUM)
static void enable_led(bool en) {
  int duty = en ? led_duty : 0;
  if (en && isStreaming && (led_duty > CONFIG_LED_MAX_INTENSITY))
    duty = CONFIG_LED_MAX_INTENSITY;
  ledcWrite(LED_GPIO_NUM, duty);
}
#endif

// ========== CAPTURE HANDLER (optimized) ==========
static esp_err_t capture_handler(httpd_req_t *req) {
  camera_fb_t *fb = NULL;
  esp_err_t res = ESP_OK;

#if defined(LED_GPIO_NUM)
  enable_led(true);
  vTaskDelay(150 / portTICK_PERIOD_MS); 
#endif

  fb = esp_camera_fb_get();
  
#if defined(LED_GPIO_NUM)
  enable_led(false);
#endif

  if (!fb) {
    log_e("Camera capture failed");
    httpd_resp_send_500(req);
    return ESP_FAIL;
  }

  httpd_resp_set_type(req, "image/jpeg");
  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  httpd_resp_set_hdr(req, "Content-Disposition", "inline; filename=capture.jpg");
  
  char ts[32];
  snprintf(ts, 32, "%lld.%06ld", fb->timestamp.tv_sec, fb->timestamp.tv_usec);
  httpd_resp_set_hdr(req, "X-Timestamp", ts);

  if (fb->format == PIXFORMAT_JPEG) {
    res = httpd_resp_send(req, (const char *)fb->buf, fb->len);
  } else {
    uint8_t *jpg_buf = NULL;
    size_t jpg_len = 0;
    bool ok = frame2jpg(fb, 80, &jpg_buf, &jpg_len);
    esp_camera_fb_return(fb);
    if (!ok) {
      log_e("JPEG compression failed");
      httpd_resp_send_500(req);
      return ESP_FAIL;
    }
    res = httpd_resp_send(req, (const char *)jpg_buf, jpg_len);
    free(jpg_buf);
    return res;
  }

  esp_camera_fb_return(fb);
  return res;
}

// ========== STREAM HANDLER ==========
static esp_err_t stream_handler(httpd_req_t *req) {
  camera_fb_t *fb = NULL;
  esp_err_t res = httpd_resp_set_type(req, _STREAM_CONTENT_TYPE);
  if (res != ESP_OK) return res;

  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  httpd_resp_set_hdr(req, "X-Framerate", "60");

#if defined(LED_GPIO_NUM)
  isStreaming = true;
  enable_led(true);
#endif

  static int64_t last_frame = 0;
  if (!last_frame) last_frame = esp_timer_get_time();

  while (true) {
    fb = esp_camera_fb_get();
    if (!fb) {
      log_e("Camera capture failed");
      res = ESP_FAIL;
      break;
    }

    uint8_t *_jpg_buf = fb->buf;
    size_t _jpg_len = fb->len;
    struct timeval ts = fb->timestamp;

    char part_buf[256];
    size_t hlen = snprintf(part_buf, sizeof(part_buf), 
                           "%sContent-Type: image/jpeg\r\nContent-Length: %u\r\nX-Timestamp: %d.%06d\r\n\r\n",
                           _STREAM_BOUNDARY, _jpg_len, ts.tv_sec, ts.tv_usec);
    res = httpd_resp_send_chunk(req, part_buf, hlen);

    if (res == ESP_OK) res = httpd_resp_send_chunk(req, (const char *)_jpg_buf, _jpg_len);

    esp_camera_fb_return(fb);
    fb = NULL;

    if (res != ESP_OK) break;

    int64_t now = esp_timer_get_time();
    int frame_time = (now - last_frame) / 1000;
    last_frame = now;
    ra_filter_run(&ra_filter, frame_time);
  }

#if defined(LED_GPIO_NUM)
  isStreaming = false;
  enable_led(false);
#endif

  return res;
}

// ========== STATUS HANDLER ==========
static esp_err_t status_handler(httpd_req_t *req) {
  static char resp[512];
  sensor_t *s = esp_camera_sensor_get();
  snprintf(resp, sizeof(resp),
           "{\"framesize\":%u,\"quality\":%u,\"brightness\":%d,"
           "\"contrast\":%d,\"saturation\":%d,\"awb\":%u,\"aec\":%u,"
           "\"agc\":%u,\"vflip\":%u,\"hmirror\":%u,"
           "\"face_detect\":%d}",
           s->status.framesize, s->status.quality, s->status.brightness,
           s->status.contrast, s->status.saturation, s->status.awb,
           s->status.aec, s->status.agc, s->status.vflip, s->status.hmirror,
           detection_enabled);

  httpd_resp_set_type(req, "application/json");
  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  return httpd_resp_send(req, resp, strlen(resp));
}

// ========== CONTROL HANDLER (ĐÃ SỬA LỖI) ==========
static esp_err_t cmd_handler(httpd_req_t *req) {
  char *buf = NULL;
  size_t buf_len = httpd_req_get_url_query_len(req) + 1;
  if (buf_len <= 1) return httpd_resp_send_404(req);

  buf = (char *)malloc(buf_len);
  httpd_req_get_url_query_str(req, buf, buf_len);
  char var[32], val[32];
  if (httpd_query_key_value(buf, "var", var, sizeof(var)) != ESP_OK ||
      httpd_query_key_value(buf, "val", val, sizeof(val)) != ESP_OK) {
    free(buf);
    return httpd_resp_send_404(req);
  }
  free(buf);

  // Chuyển val (chuỗi) thành số nguyên
  int value = atoi(val);
  sensor_t *s = esp_camera_sensor_get();
  int r = 0;

  // Dùng đúng tên biến 'var' và so sánh số nguyên 'value'
  if(!strcmp(var, "unlock")) {
      if(value == 1) {
          g_manualUnlock = true; // Bật cờ để loop() xử lý
          Serial.println("[HTTP] Received UNLOCK command from Web!");
      }
      // Trả về thành công ngay lập tức cho lệnh unlock
      httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
      return httpd_resp_send(req, NULL, 0);
  }

  // Các lệnh điều khiển Camera
  if (!strcmp(var, "framesize")) r = s->set_framesize(s, (framesize_t)value);
  else if (!strcmp(var, "quality")) r = s->set_quality(s, value);
  else if (!strcmp(var, "brightness")) r = s->set_brightness(s, value);
  else if (!strcmp(var, "contrast")) r = s->set_contrast(s, value);
  else if (!strcmp(var, "saturation")) r = s->set_saturation(s, value);
  else if (!strcmp(var, "vflip")) r = s->set_vflip(s, value);
  else if (!strcmp(var, "hmirror")) r = s->set_hmirror(s, value);
  else if (!strcmp(var, "face_detect")) detection_enabled = value ? 1 : 0;
  
  // Đã xóa đoạn lặp lại xử lý framesize sai cú pháp ở đây

  if (r < 0) return httpd_resp_send_500(req);
  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  return httpd_resp_send(req, NULL, 0);
}

// ========== SENSOR STATUS HANDLER (LM393) ==========
// Cập nhật chân 13 cho đúng với file .ino (vì chân 14 dùng cho LCD rồi)
static const int PIN_LM393 = 13; 
static const int MOTION_ACTIVE_STATE = LOW;
static const unsigned long COOLDOWN_MS = 5000;

static esp_err_t sensor_handler(httpd_req_t *req) {
  httpd_resp_set_type(req, "application/json");
  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  
  // Đọc trạng thái cảm biến
  int sensor_value = digitalRead(PIN_LM393);
  bool detected = (sensor_value == MOTION_ACTIVE_STATE);
  
  unsigned long now = millis();
  unsigned long cooldown_remaining = 0;
  
  if (now > lastTrigger && (now - lastTrigger) < COOLDOWN_MS) {
    cooldown_remaining = COOLDOWN_MS - (now - lastTrigger);
  }
  
  char json[256];
  snprintf(json, sizeof(json),
    "{\"ok\":true,\"detected\":%s,\"value\":%d,\"is_checking\":%s,\"cooldown_ms\":%lu,\"timestamp\":%lu}",
    detected ? "true" : "false",
    sensor_value,
    isChecking ? "true" : "false",
    cooldown_remaining,
    now
  );
  
  return httpd_resp_send(req, json, strlen(json));
}

// ========== INDEX (web UI) ==========
static esp_err_t index_handler(httpd_req_t *req) {
  httpd_resp_set_type(req, "text/html");
  httpd_resp_set_hdr(req, "Content-Encoding", "gzip");
  sensor_t *s = esp_camera_sensor_get();
  if (!s) return httpd_resp_send_500(req);

  if (s->id.PID == OV3660_PID)
    return httpd_resp_send(req, (const char *)index_ov3660_html_gz, index_ov3660_html_gz_len);
  else if (s->id.PID == OV5640_PID)
    return httpd_resp_send(req, (const char *)index_ov5640_html_gz, index_ov5640_html_gz_len);
  else
    return httpd_resp_send(req, (const char *)index_ov2640_html_gz, index_ov2640_html_gz_len);
}

// ========== SERVER START ==========
void startCameraServer() {
  httpd_config_t config = HTTPD_DEFAULT_CONFIG();
  config.max_uri_handlers = 8;
  ra_filter_init(&ra_filter, 10);

  httpd_uri_t index_uri   = {"/",        HTTP_GET, index_handler,   NULL};
  httpd_uri_t status_uri  = {"/status",  HTTP_GET, status_handler,  NULL};
  httpd_uri_t cmd_uri     = {"/control", HTTP_GET, cmd_handler,     NULL};
  httpd_uri_t capture_uri = {"/capture", HTTP_GET, capture_handler, NULL};
  httpd_uri_t sensor_uri  = {"/sensor",  HTTP_GET, sensor_handler,  NULL};
  httpd_uri_t stream_uri  = {"/stream",  HTTP_GET, stream_handler,  NULL};

  if (httpd_start(&camera_httpd, &config) == ESP_OK) {
    httpd_register_uri_handler(camera_httpd, &index_uri);
    httpd_register_uri_handler(camera_httpd, &status_uri);
    httpd_register_uri_handler(camera_httpd, &cmd_uri);
    httpd_register_uri_handler(camera_httpd, &capture_uri);
    httpd_register_uri_handler(camera_httpd, &sensor_uri);
  }

  config.server_port += 1;
  config.ctrl_port += 1;
  if (httpd_start(&stream_httpd, &config) == ESP_OK) {
    httpd_register_uri_handler(stream_httpd, &stream_uri);
  }

  log_i("[HTTPD] Camera servers started");
}