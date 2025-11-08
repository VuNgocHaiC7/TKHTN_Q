<?php
require __DIR__ . '/../../src/bootstrap.php';

$deviceId = $_POST['deviceId'] ?? '';
$token    = $_POST['token'] ?? '';
$result   = $_POST['result'] ?? 'ALLOW'; // ALLOW/DENY/ENROLLED/UNKNOWN
$userId   = $_POST['userId'] ?? null;
$note     = $_POST['note'] ?? null;

require_fields(['deviceId', 'token', 'result'], $_POST);
auth_device($deviceId, $token);

$imageUrl = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    [$day, $dayPath] = ensure_upload_dir();
    $name = time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
    $dst  = $dayPath . '/' . $name;
    move_uploaded_file($_FILES['image']['tmp_name'], $dst);
    $imageUrl = $GLOBALS['cfg']['app']['upload_base'] . '/' . $day . '/' . $name;
}

$stmt = pdo()->prepare('INSERT INTO access_logs(device_id,user_id,result,image_url,note,ts)
                        VALUES (?,?,?,?,?,NOW())');
$stmt->execute([$deviceId, $userId, $result, $imageUrl, $note]);

json_out(['ok' => true, 'image' => $imageUrl]);
