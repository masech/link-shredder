<?php

namespace Lish;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

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
