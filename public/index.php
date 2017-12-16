<?php

namespace Lish;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';


$config = require '../config/app.php';

$app = new \Slim\App(["settings" => $config]);

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

// Maximal number of short links.
const MAX_LINKS = 2147483647;

// Number of previous shortetngs that will be displayed on the page.
const PATHS_NUM_ON_PAGE = 10;

/**
 * Middleware that determines the client IP address
 * and stores it as an ServerRequest attribute called ip_address.
 */
$app->add(new \RKA\Middleware\IpAddress());

/**
 * '/' route and its action function for GET HTTP method.
 *
 * It is responsible for processing:
 * - current shortening (original URI and short link);
 * - previous shortenings;
 * - adding current shortening to previous.
 * Also calculation of movement on previous shortenings.
 */
$app->get('/', function (Request $request, Response $response) {
    $queryParams = $request->getQueryParams();
    
    if (array_key_exists('path', $queryParams)) {
        $path = preg_match('/^[0-9a-zA-Z]{1,6}$/', $queryParams['path']) ?
            $queryParams['path'] :
            null;
    } else {
        $path = null;
    }

    try {
        $repo = new LinkRepository($this->db);
    } catch (\PDOException $e) {
        $this->logger->alert('DB not available!');
        $respons = $this->view->render($response, 'on_treatment.phtml');
        return $response;
    }
    
    if (isset($path)) {
        $id = convertToId($path);
        list($uri) = (integer) $id <= MAX_LINKS ?
            $repo->find([$id]) :
            [false];
    } else {
        $uri = false;
    }

    $ownUri = $request->getUri()->getHost();

    $shortening = $uri ?
        [$uri, "${ownUri}/${path}"] :
        [];
    
    $cookies = $request->getCookieParams();
    
    $client = new ClientState($cookies);
    $previousPaths = $client->getState();

    $numOfPreviousPaths = sizeof($previousPaths);

    $offset = calculateOffset($queryParams, $numOfPreviousPaths, PATHS_NUM_ON_PAGE);
    $asIfPrevPathsOnPage = array_slice($previousPaths, $offset, PATHS_NUM_ON_PAGE);

    $prevPathsOnPage = array_reduce($asIfPrevPathsOnPage, function ($acc, $item) {
        if (preg_match('/^[0-9a-zA-Z]{1,6}$/', $item)) {
            $acc[] = $item;
        }
        return $acc;
    }, []);

    $ids = array_reduce($prevPathsOnPage, function ($acc, $prevPath) {
        $id = convertToId($prevPath);
        if ((integer) $id <= MAX_LINKS) {
            $acc[] = $id;
        }
        return $acc;
    }, []);

    try {
        $prevUrisOnPage = $repo->find($ids);
    } catch (\PDOException $e) {
        $this->logger->alert('DB not available!');
        $respons = $this->view->render($response, 'on_treatment.phtml');
        return $response;
    }

    $asIfPrevShorteningsOnPage = array_map(function ($prevUri, $prevPath) use ($ownUri) {
        return [$prevUri, "${ownUri}/${prevPath}"];
    }, $prevUrisOnPage, $prevPathsOnPage);

    $prevShorteningsOnPage = array_filter($asIfPrevShorteningsOnPage, function ($shortening) {
        return $shortening[0] === false ? false : true;
    });

    $currentPageNum = $offset / PATHS_NUM_ON_PAGE + 1;
    $totalPages = ceil($numOfPreviousPaths / PATHS_NUM_ON_PAGE);
    $onPage = [$prevShorteningsOnPage, $offset, $currentPageNum, $totalPages];

    $paths = addPath($path, $previousPaths);
    $client->setState($paths);

    $respons = $this->view->render($response, 'index.phtml', [
        'invalidUri' => '',
        'error' => '',
        'shortening' => $shortening,
        'page' => $onPage
    ]);
 
    return $response;
});

/**
 * '/{path}' route and its action function for GET HTTP method.
 *
 * It is responsible for redirecting from a short link to the original URI.
 */
$app->get('/{path}', function (Request $request, Response $response, $args) {
    $path = $args['path'];

    if (!preg_match('/^[0-9a-zA-Z]{1,6}$/', $path)) {
        $response = $response->withStatus(404)->withHeader('Content-Type', 'text/html');
        $response = $this->view->render($response, 'not_found.phtml');
        return $response;
    }

    $id = convertToId($path);
    if ((integer) $id > MAX_LINKS) {
        $response = $response->withStatus(404)->withHeader('Content-Type', 'text/html');
        $response = $this->view->render($response, 'not_found.phtml');
        return $response;
    }

    try {
        $repo = new LinkRepository($this->db);
        list($uri) = $repo->find([$id]);
    } catch (\PDOException $e) {
        $this->logger->alert('DB not available!');
        $response = $this->view->render($response, 'on_treatment.phtml');
        return $response;
    }

    if ($uri === false) {
        $response = $response->withStatus(404)->withHeader('Content-Type', 'text/html');
        $response = $this->view->render($response, 'not_found.phtml');
        return $response;
    }
    
    $uriWithScheme = preg_match('/(^([^:]+):\/\/).+$/', $uri) ?
        $uri :
        'http://' . $uri;

    return $response->withStatus(301)->withHeader('Location', $uriWithScheme);
});

/**
 * '/shorten' route and its action function for POST HTTP method.
 *
 * It is responsible for processing:
 * - validating inputed URI;
 * - in case of successful validation, insert URI into the database
 * - and then converting ID of inserted URI to path of short link,
 * - then redirect to '/' route;
 * - inform the user in case of unsuccessful validation
 * - and then calculation of movement on previous shortenings.
 */
$app->post('/shorten', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    if (!isset($data) ||
        !array_key_exists('uri', $data)) {
        $this->logger->warning("On '/shorten' request  with changed attributes!");
        return $response->withStatus(415)->write("Sorry, I didn't understand.");
    }

    $uri = trim($data['uri']);

    if (strlen($uri) == 0) {
        return $response->withRedirect('/', 302);
    }

    $ipAddressClient = $request->getAttribute('ip_address');
    $this->logger->info('', ['Inputed' => $uri, 'From' => $ipAddressClient]);

    try {
        $repo = new LinkRepository($this->db);
    } catch (\PDOException $e) {
        $this->logger->alert('DB not available!');
        $respons = $this->view->render($response, 'on_treatment.phtml');
        return $response;
    }

    $ownUri = $request->getUri()->getHost();

    $uriValidationResult = validateUri($uri, $ownUri);

    if ($uriValidationResult == 'valid') {
        try {
            $repo->insert($uri);
            $lastId = $repo->getLastId();
        } catch (\PDOException $e) {
            $this->logger->warning('Something is wrong with DB!', [$e->getMessage()]);
            $respons = $this->view->render($response, 'on_treatment.phtml');
            return $response;
        }
        $path = convertToPath($lastId);

        $this->logger->info('', ['URI' => $uri, 'Short path' => $path]);
            
        return $response->withRedirect("/?path=${path}", 302);
    }

    $errorMessage = $uriValidationResult == 'invalid' ?
        'the link is incorrect' :
        'the link seems already short';

    $cookies = $request->getCookieParams();
    
    $client = new ClientState($cookies);
    $previousPaths = $client->getState();

    $queryParams = $request->getQueryParams();
    $numOfPreviousPaths = sizeof($previousPaths);

    $offset = calculateOffset($queryParams, $numOfPreviousPaths, PATHS_NUM_ON_PAGE);
    $asIfPrevPathsOnPage = array_slice($previousPaths, $offset, PATHS_NUM_ON_PAGE);

    $prevPathsOnPage = array_reduce($asIfPrevPathsOnPage, function ($acc, $item) {
        if (preg_match('/^[0-9a-zA-Z]{1,6}$/', $item)) {
            $acc[] = $item;
        }
        return $acc;
    }, []);

    $ids = array_reduce($prevPathsOnPage, function ($acc, $prevPath) {
        $id = convertToId($prevPath);
        if ((integer) $id <= MAX_LINKS) {
            $acc[] = $id;
        }
        return $acc;
    }, []);

    try {
        $prevUrisOnPage = $repo->find($ids);
    } catch (\PDOException $e) {
        $this->logger->alert('DB not available!');
        $respons = $this->view->render($response, 'on_treatment.phtml');
        return $response;
    }

    $asIfPrevShorteningsOnPage = array_map(function ($prevUri, $prevPath) use ($ownUri) {
        return [$prevUri, "${ownUri}/${prevPath}"];
    }, $prevUrisOnPage, $prevPathsOnPage);

    $prevShorteningsOnPage = array_filter($asIfPrevShorteningsOnPage, function ($shortening) {
        return $shortening[0] === false ? false : true;
    });

    $currentPageNum = $offset / PATHS_NUM_ON_PAGE + 1;
    $totalPages = ceil($numOfPreviousPaths / PATHS_NUM_ON_PAGE);
    $onPage = [$prevShorteningsOnPage, $offset, $currentPageNum, $totalPages];

    $this->logger->info('', ['Message' => $errorMessage, 'Inputed' => $uri]);

    $response = $this->view->render($response, 'index.phtml', [
        'invalidUri' => $uri,
        'error' => $errorMessage,
        'shortening' => [],
        'page' => $onPage
    ]);
    return $response;
});


$app->run();
