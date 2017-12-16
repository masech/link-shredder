<?php

namespace Lish;

/**
 * Adding new element to the beginning of collection.
 *
 * @param string $path
 * @param array $paths
 * @return array
 */
function addPath($path, $paths)
{
    if (!isset($path)) {
        return [];
        
    } elseif (!empty($paths)) {
        if ($path !== $paths[0]) {
            array_unshift($paths, $path);
        }
    } else {
        array_unshift($paths, $path);
    }
    return $paths;
}
