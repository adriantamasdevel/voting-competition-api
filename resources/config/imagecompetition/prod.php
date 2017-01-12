<?php

return array(
    'debug' => false,
    'log.level' => Monolog\Logger::ERROR,
    'api.version' => 'v1',
    'api.endpoint' => '',
    'app.showExceptions' => false,
    'GA.url' => 'http://www.google-analytics.com/collect',
    'GA.trackingId' => '',
    'db.settings' => array(
        'dbname' => '',
        'user' => '',
        'password' => '',
        'host' => '',
        'driver' => 'pdo_mysql'
    ),
    'recaptcha.verifyUrl' => 'https://www.google.com/recaptcha/api/siteverify',
    'recaptcha.secretKey' => '',
    'swiftmailer.fromAddresses' => array(
        ''
    ),
    'swiftmailer.toAddresses' => array(
        ''
    ),
    'swiftmailer.options' => array(
        'host'       => 'smtp.gmail.com',
        'port'       => 465,
        'username'   => '',
        'password'   => '',
        'encryption' => 'ssl',
        'auth_mode'  => 'login'
    ),
    'slack.notificationWebhook' => '',
    'admin.base_url' => '',
    'cookie.domain' => '.localhost.com',
    'default.locale' => 'GB',
    'image.base_url' => '',
    'image.upload_path' => '',
    'image.default_width' => '700',
);

