<?php

namespace Lish;

class ClientStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testClientState($expected, $argument)
    {
        $stub = $this->getMockBuilder('Cookiesetter')
                        ->setMethods(['setCookies'])
                        ->getMock();

        $client = new ClientState([], $stub);
        $asIfCookies = $client->setState($argument);

        $client = new ClientState($asIfCookies, $stub);
        $this->assertEquals($expected, $client->getState());
    }

    public function additionProvider()
    {
        return [
            [['path0', 'path1', 'path2', 'path3'], ['path0', 'path1', 'path2', 'path3']],
            [[], null]
        ];
    }
}
