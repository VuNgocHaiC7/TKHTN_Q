<?php
function auth_api_key()
{
    $key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');
    if (!$key) json_out(['error' => 'missing api key'], 401);
    $stmt = pdo()->prepare('SELECT * FROM api_keys WHERE api_key=?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) json_out(['error' => 'invalid api key'], 401);
    pdo()->prepare('UPDATE api_keys SET last_used_at=NOW() WHERE id=?')->execute([$row['id']]);
    return $row;
}
function auth_device($deviceId, $token)
{
    $stmt = pdo()->prepare('SELECT * FROM devices WHERE id=? AND secret=? AND is_active=1');
    $stmt->execute([$deviceId, $token]);
    $dev = $stmt->fetch();
    if (!$dev) json_out(['error' => 'unauthorized device'], 401);
    return $dev;
}
