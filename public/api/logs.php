<?php
require __DIR__ . '/../../src/bootstrap.php';
auth_api_key(); // chỉ admin/app mới xem

$deviceId = $_GET['device_id'] ?? null;
$userId   = $_GET['user_id'] ?? null;
$limit    = min(max((int)($_GET['limit'] ?? 50), 1), 200);

$sql = 'SELECT l.id, l.ts, l.result, l.image_url, l.note,
               l.device_id, d.name AS device_name, l.user_id
        FROM access_logs l
        JOIN devices d ON d.id = l.device_id';
$cond = [];
$prms = [];

if ($deviceId) {
    $cond[] = 'l.device_id = ?';
    $prms[] = $deviceId;
}
if ($userId) {
    $cond[] = 'l.user_id = ?';
    $prms[] = $userId;
}
if ($cond) $sql .= ' WHERE ' . implode(' AND ', $cond);
$sql .= ' ORDER BY l.id DESC LIMIT ' . $limit;

$stmt = pdo()->prepare($sql);
$stmt->execute($prms);
json_out(['data' => $stmt->fetchAll()]);
