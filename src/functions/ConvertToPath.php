<?php

namespace Lish;

/**
 * Converting ID of original URI to the path of short link.
 *
 * @param string $id
 * @return string
 */
function convertToPath($id)
{
    return gmp_strval(gmp_init($id), 62);
}
