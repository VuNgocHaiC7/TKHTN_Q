<?php

/**
 * Project_Q/public/api/esp32/auto_capture.php  (Hardened, namespaced helpers)
 * Tham số: ip, thr(0..100), delay(ms), full(0|1), debug(0|1)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('display_errors', '0');
error_reporting(E_ALL);

/* ===== JSON responder + global traps (prefixed) ===== */
function q_json_out(array $data, int $http = 200): void
{
    $leak = ob_get_contents();
    if ($leak) $data['_leak'] = mb_substr($leak, 0, 4000);
    http_response_code($http);
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
ob_start();
$DEBUG = (isset($_GET['debug']) && (int)$_GET['debug'] === 1);

set_error_handler(function ($sev, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $sev, $file, $line);
});
register_shutdown_function(function () use ($DEBUG) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        q_json_out(['ok' => false, 'error' => 'Fatal: ' . $e['message'], 'file' => $DEBUG ? $e['file'] : null, 'line' => $DEBUG ? $e['line'] : null], 500);
    }
});

/* ===== optional bootstrap (safe) ===== */
try {
    $bootstrap = __DIR__ . '/../../../src/bootstrap.php';
    if (is_file($bootstrap)) require_once $bootstrap;
} catch (Throwable $e) {
    q_json_out(['ok' => false, 'error' => 'Bootstrap load failed: ' . $e->getMessage()], 500);
}

/* ===== helpers with unique names ===== */
function q_cfg_upload_paths(): array
{
    $uploadRoot = $GLOBALS['cfg']['app']['upload_root'] ?? null;
    $uploadBase = $GLOBALS['cfg']['app']['upload_base'] ?? null;
    if (!$uploadRoot) {
        $publicDir  = dirname(__DIR__, 2); // .../public
        $uploadRoot = $publicDir . '/uploads';
    }
    if (!$uploadBase) $uploadBase = '/uploads';
    return ['root' => rtrim($uploadRoot, '/'), 'base' => rtrim($uploadBase, '/')];
}

function q_ensure_upload_dir(): array
{
    $cfg = q_cfg_upload_paths();
    $day = date('Y-m-d');
    $dir = $cfg['root'] . '/' . $day;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return ['day' => $day, 'dir' => $dir, 'base' => $cfg['base']];
}

function q_fetch_jpeg_bin(string $url, int $timeout = 6): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'ProjectQ-ESP32-Capture/1.0',
            CURLOPT_HTTPHEADER => ['Accept: image/jpeg, */*'],
        ]);
        $bin = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($bin === false || $code < 200 || $code >= 300) return [null, $err ?: "HTTP $code"];
        return [$bin, null];
    }
    $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "Accept: image/jpeg\r\n"]]);
    $bin = @file_get_contents($url, false, $ctx);
    if ($bin === false) {
        global $http_response_header;
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
        return [null, "HTTP $code (file_get_contents)"];
    }
    return [$bin, null];
}

function q_diff_percent(string $jpg1, string $jpg2): float
{
    if (!extension_loaded('gd') || !function_exists('imagecreatefromstring')) return 100.0;
    try {
        $im1 = @imagecreatefromstring($jpg1);
        $im2 = @imagecreatefromstring($jpg2);
        if (!$im1 || !$im2) {
            if ($im1) imagedestroy($im1);
            if ($im2) imagedestroy($im2);
            return 100.0;
        }
        $tw = 96;
        $th = 72;
        $t1 = imagecreatetruecolor($tw, $th);
        $t2 = imagecreatetruecolor($tw, $th);
        imagecopyresampled($t1, $im1, 0, 0, 0, 0, $tw, $th, imagesx($im1), imagesy($im1));
        imagecopyresampled($t2, $im2, 0, 0, 0, 0, $tw, $th, imagesx($im2), imagesy($im2));
        $sum = 0.0;
        $cnt = $tw * $th;
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $c1 = imagecolorat($t1, $x, $y);
                $c2 = imagecolorat($t2, $x, $y);
                $r1 = ($c1 >> 16) & 0xFF;
                $g1 = ($c1 >> 8) & 0xFF;
                $b1 = $c1 & 0xFF;
                $r2 = ($c2 >> 16) & 0xFF;
                $g2 = ($c2 >> 8) & 0xFF;
                $b2 = $c2 & 0xFF;
                $y1 = 0.299 * $r1 + 0.587 * $g1 + 0.114 * $b1;
                $y2 = 0.299 * $r2 + 0.587 * $g2 + 0.114 * $b2;
                $sum += abs($y1 - $y2);
            }
        }
        imagedestroy($t1);
        imagedestroy($t2);
        imagedestroy($im1);
        imagedestroy($im2);
        return ($cnt > 0) ? ($sum / ($cnt * 255.0) * 100.0) : 100.0;
    } catch (Throwable $e) {
        return 100.0;
    }
}

/* ===== params ===== */
$ip     = isset($_GET['ip']) ? trim((string)$_GET['ip']) : '';
$thr    = isset($_GET['thr']) ? (float)$_GET['thr'] : 7.5;
$delay  = isset($_GET['delay']) ? (int)$_GET['delay'] : 300;
$doFull = (isset($_GET['full']) ? (int)$_GET['full'] : 1) === 1;

if ($ip === '') q_json_out(['ok' => false, 'error' => 'Missing ip'], 422);
if ($thr < 0) $thr = 0;
if ($thr > 100) $thr = 100;
if ($delay < 0) $delay = 0;

$capUrl = "http://{$ip}/capture";

/* ===== main ===== */
try {
    // A
    [$a, $errA] = q_fetch_jpeg_bin($capUrl, 6);
    if ($a === null) q_json_out(['ok' => false, 'error' => "capture#1: $errA"], 502);
    // B
    usleep($delay * 1000);
    [$b, $errB] = q_fetch_jpeg_bin($capUrl, 6);
    if ($b === null) q_json_out(['ok' => false, 'error' => "capture#2: $errB"], 502);

    $score = round(q_diff_percent($a, $b), 2);
    $captured = false;
    $url = null;

    if ($score >= $thr && $doFull) {
        [$bin, $errC] = q_fetch_jpeg_bin($capUrl, 8);
        if ($bin !== null) {
            $up = q_ensure_upload_dir();
            $filename = time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $full = $up['dir'] . '/' . $filename;
            if (!is_dir($up['dir'])) @mkdir($up['dir'], 0775, true);
            if (@file_put_contents($full, $bin) !== false) {
                $url = $up['base'] . '/' . $up['day'] . '/' . $filename;
                $captured = true;
            }
        }
    }

    q_json_out(['ok' => true, 'captured' => $captured, 'score' => $score, 'url' => $url, 'thr' => $thr, 'delay' => $delay], 200);
} catch (Throwable $e) {
    q_json_out(['ok' => false, 'error' => 'Server exception: ' . $e->getMessage(), 'trace' => $DEBUG ? $e->getTraceAsString() : null], 500);
}
