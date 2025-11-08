<?php
require __DIR__ . '/../../../src/bootstrap.php';

$deviceId = $_POST['deviceId'] ?? '';
$token    = $_POST['token']    ?? '';

require_fields(['deviceId', 'token'], $_POST);
auth_device($deviceId, $token);

$ip = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'];

$stmt = pdo()->prepare('UPDATE devices SET ip=?, last_seen=NOW() WHERE id=?');
$stmt->execute([$ip, $deviceId]);

json_out(['ok' => true, 'ip' => $ip, 'device' => $deviceId]);
