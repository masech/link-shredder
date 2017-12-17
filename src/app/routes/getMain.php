<?php

namespace Lish;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Maximal number of short links.
const MAX_LINKS = 2147483647;

// Number of previous shortetngs that will be displayed on the page.
const PATHS_NUM_ON_PAGE = 10;

/**
 * '/' main route and its action function for GET HTTP method.
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
