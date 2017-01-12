<?php

return array(
    'debug' => true,
    'app.showExceptions' => true,
    'log.level' =>  Monolog\Logger::DEBUG,
    'db.settings' => array(
        'dbname' => '',
        'user' => '',
        'password' => '',
        'host' => '',
        'driver' => 'pdo_mysql',
    ),
    'admin.base_url' => '',
    'image.base_url' => '',
    'image.upload_path' => '',
);