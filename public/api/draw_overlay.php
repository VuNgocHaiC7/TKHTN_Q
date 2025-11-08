<?php
require __DIR__ . '/_util.php';

// Lấy boxes từ GET parameter (dễ hơn cho client)
$boxesJson = $_GET['boxes'] ?? $_POST['boxes'] ?? null;
if (!$boxesJson) bad('Thiếu boxes JSON (dùng ?boxes=... hoặc POST boxes=...)');

$boxes = json_decode($boxesJson, true);
if (!$boxes || !isset($boxes['faces'])) bad('boxes JSON sai format');

// Lấy ảnh: ưu tiên từ file upload, không có thì từ raw body
if (!empty($_FILES['image']['tmp_name'])) {
    $imgBytes = file_get_contents($_FILES['image']['tmp_name']);
} else {
    $imgBytes = file_get_contents('php://input');
}

if (!$imgBytes) bad('Thiếu ảnh JPG');
$im = @imagecreatefromstring($imgBytes);
if (!$im) bad('Ảnh không hợp lệ');

// Vẽ khung
$green = imagecolorallocate($im, 0, 255, 0);
$red   = imagecolorallocate($im, 255, 0, 0);
$thickness = 3; // Độ dày khung

foreach ($boxes['faces'] as $f) {
    [$x1, $y1, $x2, $y2] = $f['box'];
    $col = !empty($f['matched']) ? $green : $red;

    // Vẽ khung dày hơn
    for ($i = 0; $i < $thickness; $i++) {
        imagerectangle($im, $x1 - $i, $y1 - $i, $x2 + $i, $y2 + $i, $col);
    }

    // Vẽ tên (nếu có)
    if (!empty($f['name'])) {
        $text = $f['name'];
        // Background cho text
        $textBg = $col;
        imagefilledrectangle($im, $x1, max(0, $y1 - 20), $x1 + strlen($text) * 8, $y1, $textBg);
        imagestring($im, 3, $x1 + 2, max(0, $y1 - 18), $text, $col === $green ? $green : $red);
    }
}

// Xuất JPEG
header('Content-Type: image/jpeg');
header('Cache-Control: no-store');
imagejpeg($im, null, 90);
imagedestroy($im);
