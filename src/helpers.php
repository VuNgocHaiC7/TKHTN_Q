<?php
function json_out($data, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
function require_fields(array $fields, array $src)
{
    foreach ($fields as $f) {
        if (!isset($src[$f]) || $src[$f] === '') json_out(['error' => "Missing field: $f"], 422);
    }
}
function ensure_upload_dir()
{
    $dir = $GLOBALS['cfg']['app']['upload_dir'];
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $day = date('Ymd');
    $dayPath = $dir . '/' . $day;
    if (!is_dir($dayPath)) mkdir($dayPath, 0777, true);
    return [$day, $dayPath];
}
