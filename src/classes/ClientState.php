<?php

namespace Lish;

/**
 * Data serialization and splitting for sending to the client via cookies.
 * Merge and deserialization of data received from the client.
 */
class ClientState
{
    private $cookies;
    private $cookiesetter;

    private $chunkValueSize = 1800;
    private $maxChunks = 20;

    /**
     * @param array     $cookies
     * @param obj|null  $cookiesetter    instance of class-wrapper around setcookie function
     */
    public function __construct($cookies, $cookiesetter = null)
    {
        $this->cookies = $cookies;
        
        $this->cookiesetter = $cookiesetter ?? new Cookiesetter();
    }

    /**
     * @return array
     */
    public function getState()
    {
        $chunks = $this->cookies;

        $json = implode('', $chunks);
        
        return json_decode($json, true) ?? [];
    }

    /**
     * @param array $state
     * @return array
     */
    public function setState($state)
    {
        if (empty($state)) {
            return [];
        }

        do {
            $json = json_encode($state);
            $chunks = str_split($json, $this->chunkValueSize);
            array_pop($state);
        } while (sizeof($chunks) > $this->maxChunks);
        
        $this->cookiesetter->setCookies($chunks);

        return $chunks;
    }
}
