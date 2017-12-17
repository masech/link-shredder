<?php

/**
 * Middleware that determines the client IP address
 * and stores it as an ServerRequest attribute called ip_address.
 */
$app->add(new \RKA\Middleware\IpAddress());
