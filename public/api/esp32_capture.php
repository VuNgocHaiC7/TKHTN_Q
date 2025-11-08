<?php
require __DIR__ . '/_util.php';
$cfg = cfg();
$ip = get_esp_ip();

$lastErr = null;
foreach ($cfg['snapshot_paths'] as $p) {
    $url = "http://{$ip}{$p}";
    $bytes = http_get_bytes($url, 6);
    if ($bytes && str_starts_with($bytes, "\xFF\xD8")) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store');
        echo $bytes;
        exit;
    } else {
        $lastErr = "Không lấy được snapshot tại $url";
    }
}
bad($lastErr ?? 'Không lấy được ảnh từ ESP32');
