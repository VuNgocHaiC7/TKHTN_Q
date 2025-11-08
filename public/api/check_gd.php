<?php
header('Content-Type: application/json');

$gd_info = [];
if (function_exists('gd_info')) {
    $gd_info = gd_info();
}

echo json_encode([
    'gd_available' => extension_loaded('gd'),
    'gd_info' => $gd_info,
    'imagecreatefromstring' => function_exists('imagecreatefromstring'),
    'imagecreatetruecolor' => function_exists('imagecreatetruecolor'),
]);
