<?php
require __DIR__ . '/../../src/bootstrap.php';
auth_api_key(); // chỉ admin/app mới xem

$q = pdo()->query('SELECT id,name,ip,is_active,last_seen,created_at FROM devices ORDER BY id');
json_out(['data' => $q->fetchAll()]);
