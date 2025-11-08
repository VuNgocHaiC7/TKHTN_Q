<?php
require __DIR__ . '/_util.php';
$cfg = cfg();

// 1) Lấy ảnh đầu vào: ưu tiên file upload 'image'; nếu không có thì chụp từ ESP32
$tmp = tempnam(sys_get_temp_dir(), 'det_') . '.jpg';
if (!empty($_FILES['image']['tmp_name'])) {
    move_uploaded_file($_FILES['image']['tmp_name'], $tmp);
} else {
    $ip = get_esp_ip();
    $ok = false;
    foreach ($cfg['snapshot_paths'] as $p) {
        $url = "http://{$ip}{$p}";
        $bytes = http_get_bytes($url, 6);
        if ($bytes && str_starts_with($bytes, "\xFF\xD8")) {
            file_put_contents($tmp, $bytes);
            $ok = true;
            break;
        }
    }
    if (!$ok) bad('Không chụp được ảnh từ ESP32');
}

// 2) Gọi Python xử lý DETECT ONLY (nhanh)
$cmd = escapeshellcmd($cfg['python_bin']) . ' ' .
    escapeshellarg($cfg['tools_dir'] . '/face_detect_only.py') . ' ' .
    '--image ' . escapeshellarg($tmp);
exec($cmd . ' 2>&1', $out, $code);
@unlink($tmp);

if ($code !== 0) {
    bad("Python error ($code): " . implode("\n", $out), 500);
}

// 3) Python in ra JSON -> pass through
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo implode("\n", $out);
