<?php

namespace Lish;

/**
 * Calculate the offset for its further use in the array_slice function.
 *
 * @param array $params
 * @param integer $size
 * @param integer $shift
 * @return integer
 */
function calculateOffset($params, $size, $shift)
{
    if (array_key_exists('direction', $params)
        && array_key_exists('current', $params)) {

        if ($params['direction'] == 'next') {
            $drift = $shift;

        } elseif ($params['direction'] == 'prev') {
            $drift = -$shift;

        } elseif ($params['direction'] == 'last') {
            $offset = floor($size / $shift) * $shift;
            return $offset != $size ? $offset : $offset - $shift;

        } elseif ($params['direction'] == 'first') {
            return 0;
            
        } else {
            $drift = 0;
        }

        if (preg_match('/^[0-9]+$/', $params['current'])) {
            $current = (integer) $params['current'];
            $currentPosition = $current >= $shift ? $current : 0;
        } else {
            $currentPosition = 0;
        }

        if ($currentPosition == 0 && $drift < 0) {
            $offset = 0;
        } elseif ($currentPosition + $drift >= $size) {
            $offset = 0;
        } else {
            $offset = $currentPosition + $drift;
        }
        
    } else {
        $offset = 0;
    }

    return $offset;
}
