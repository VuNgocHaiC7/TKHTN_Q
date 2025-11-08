<?php
// Project_Q/public/api/esp32/ctrl.php
require __DIR__ . '/../../../src/bootstrap.php';

$ip  = $_GET['ip']  ?? '';
$var = $_GET['var'] ?? '';
$val = $_GET['val'] ?? '';

if ($ip === '' || $var === '' || $val === '') {
    json_out(['ok' => false, 'error' => 'Missing ip/var/val'], 422);
}

$url = "http://{$ip}/control?var=" . urlencode($var) . "&val=" . urlencode($val);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT        => 5,
]);
$body = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
curl_close($ch);

if ($body === false || $code < 200 || $code >= 300) {
    json_out(['ok' => false, 'error' => $err ?: "ESP32 returns HTTP {$code}"], 502);
}

json_out(['ok' => true, 'resp' => $body, 'url' => $url]);
