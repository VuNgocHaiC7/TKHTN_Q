<?php
// Project_Q/public/api/esp32/capture.php

// Tắt hiển thị lỗi PHP để không làm hỏng JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/../../../src/bootstrap.php';

$ip = $_GET['ip'] ?? '';
if ($ip === '') json_out(['ok' => false, 'error' => 'Missing ip'], 422);

try {
    $url = "http://{$ip}/capture";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $bin  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    curl_close($ch);

    if ($bin === false || $code < 200 || $code >= 300) {
        json_out(['ok' => false, 'error' => $err ?: "ESP32 returns HTTP {$code}"], 502);
    }

    [$day, $dayPath] = ensure_upload_dir(); // đã có sẵn trong src/helpers.php
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
    file_put_contents($dayPath . '/' . $filename, $bin);

    $base = rtrim($GLOBALS['cfg']['app']['upload_base'], '/'); // lấy từ config/env.php
    $publicUrl = "{$base}/{$day}/{$filename}";

    json_out(['ok' => true, 'url' => $publicUrl]);
} catch (Exception $e) {
    json_out(['ok' => false, 'error' => 'Exception: ' . $e->getMessage()], 500);
}
