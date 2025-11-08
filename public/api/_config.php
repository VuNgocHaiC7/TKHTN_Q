<?php
// --- Cấu hình cơ bản ---
return [
    // IP ESP32-CAM (có thể override bằng ?ip=)
    'esp32_ip' => '192.168.0.107',

    // Endpoint snapshot trên ESP32 (CameraWebServer mặc định là /capture hoặc /jpg)
    // Thử lần lượt, tuỳ firmware bạn đang chạy:
    'snapshot_paths' => ['/capture', '/jpg', '/capture?_cb=1'],

    // Python env (đường dẫn python trong server)
    'python_bin' => 'C:\\Users\\Acer\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',

    // Đường dẫn tool và DB ảnh khuôn mặt
    'tools_dir' => dirname(__DIR__) . '/tool',
    'faces_db_dir' => dirname(__DIR__) . '/tool/faces_db',

    // Ngưỡng so khớp - OPTIMIZED for production
    // 0.6 = STANDARD (cân bằng tốt, ít false positive)
    // 0.55 = STRICT (chính xác cao, có thể miss một số trường hợp)
    // 0.65-0.7 = RELAXED (dễ match hơn, có thể có false positive)
    'tolerance' => 0.6,

    // Timeout cho Python process (giây)
    'python_timeout' => 10
];
