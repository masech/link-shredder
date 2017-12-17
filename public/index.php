<?php

require '../vendor/autoload.php';


$config = require '../config/app.php';

$app = new \Slim\App(["settings" => $config]);

// Set up dependencies
require '../src/app/dependencies.php';

// Register middleware
require '../src/app/middleware.php';

// Register routes
require '../src/app/routes/getMain.php';
require '../src/app/routes/getPath.php';
require '../src/app/routes/postShorten.php';


$app->run();
