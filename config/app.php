<?php

$db = require __DIR__ . '/db.php';

$config = [
    'addContentLengthHeader' => false,
    'db' => [
        'dsn' => $db['dsn'],
        'username' => $db['username'],
        'password' => $db['password']
    ]
];

return $config;
