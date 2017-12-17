<?php

namespace Lish;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

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
