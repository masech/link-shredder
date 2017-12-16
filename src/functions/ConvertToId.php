<?php

namespace Lish;

/**
 * Converting a path of short link to ID of original URI.
 *
 * @param string $path
 * @return string
 */
function convertToId($path)
{
    return gmp_strval(gmp_init($path, 62));
}
