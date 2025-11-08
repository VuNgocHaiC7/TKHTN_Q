<?php
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'esp32lock',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'app' => [
        // Đường dẫn web gốc (backend đang chạy ở đây)
        'base_url'    => 'http://localhost/Project_Q/public',

        // Đường dẫn thư mục uploads (nơi lưu ảnh log)
        'upload_dir'  => __DIR__ . '/../public/uploads',

        // URL public để truy cập ảnh qua trình duyệt
        'upload_base' => '/Project_Q/public/uploads',
    ],
];
