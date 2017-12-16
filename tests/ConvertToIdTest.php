<?php

namespace Lish;

class ConvertToIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testConvertToId($expected, $argument)
    {
        $this->assertEquals($expected, convertToId($argument));
    }

    public function additionProvider()
    {
        return [
            ['1', '1'],
            ['10', 'A'],
            ['11', 'B'],
            ['61', 'z'],
            ['62', '10'],
            ['12296565', 'path']
        ];
    }
}
