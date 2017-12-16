<?php

namespace Lish;

/**
 * Class-wrapper around setcookie function for testability of instances ClientState class
 */
class Cookiesetter
{
    /**
     * @param array $chunks
     * @return null
     */
    public function setCookies($chunks)
    {
        foreach ($chunks as $num => $chunk) {
            setcookie("previous${num}", $chunk, time()+60*60*24, '', '', false, true);
        }
    }
}
