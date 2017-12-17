<?php

/**
 * Dependency container.
 */
$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $opt = array(
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
    );
    $pdo = new \PDO($db['dsn'], $db['username'], $db['password'], $opt);
    return $pdo;
};

$container['view'] = function ($c) {
    return new \Slim\Views\PhpRenderer('../resources/views/');
};

$container['logger'] = function ($c) {
    $logger = new \Monolog\Logger('lish_logger');
    $logger->setTimezone(new \DateTimeZone('Europe/Moscow'));
    $maxFiles = 7;
    $fileHandler = new \Monolog\Handler\RotatingFileHandler("../logs/app.log", $maxFiles, \Monolog\Logger::INFO);
    $logger->pushHandler($fileHandler);
    return $logger;
};
