<?php

namespace Lish;

/**
 * Validation of URI.
 *
 * @param string $uri
 * @param string $ownUri
 * @return string
 */
function validateUri($uri, $ownUri)
{
    $result = 'invalid';

    if (strlen($uri) > 2041) {
        return $result;
    }

    $schemes = ['http', 'https', 'ftp'];

    // search pattern for validation on   scheme://user@host:port/path
    $regExp = '/(^([^:]+):\/\/)([0-9a-zA-Z-_\.]*@)?([^`~!@#$%^&*()_=+[\]{}\\\\|;:"\',<>\/? ]{3,})(:[\d]+)?([\/]+.*)?$/';

    // search pattern for validation on   user@host:port/path
    $regExp1 = '/^([0-9a-zA-Z-_\.]*@)?([^`~!@#$%^&*()_=+[\]{}\\\\|;:"\',<>\/? ]{3,})(:[\d]+)?([\/]+.*)?$/';

    // search pattern for validation on correct a host
    $regExp2 = '/^\..+|\.{2,}|.+\.$/';

    if (preg_match($regExp, $uri, $matches)) {
        if (in_array($matches[2], $schemes) && !preg_match($regExp2, $matches[4])) {
            $result = 'valid';
        }
        if ($matches[4] == $ownUri) {
            $result = 'own';
        }
    } elseif (preg_match($regExp1, $uri, $matches)) {
        if (!preg_match($regExp2, $matches[2])) {
            $result = 'valid';
        }
        if ($matches[2] == $ownUri) {
            $result = 'own';
        }
    }
    return $result;
}
