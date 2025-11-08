<?php
function cfg()
{
    static $cfg;
    if (!$cfg) $cfg = require __DIR__ . '/_config.php';
    return $cfg;
}
function get_esp_ip(): string
{
    $cfg = cfg();
    $ip = isset($_GET['ip']) ? $_GET['ip'] : $cfg['esp32_ip'];
    return preg_replace('/\s+/', '', $ip);
}
function http_get_bytes(string $url, int $timeout = 5)
{
    $ctx = stream_context_create(['http' => ['timeout' => $timeout]]);
    return @file_get_contents($url, false, $ctx);
}
function json_out($data, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function bad($msg, int $code = 400)
{
    json_out(['ok' => false, 'error' => $msg], $code);
}
