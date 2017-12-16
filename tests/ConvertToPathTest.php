<?php

namespace Lish;

class ConvertToPathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testConvertToPath($expected, $argument)
    {
        $this->assertEquals($expected, convertToPath($argument));
    }

    public function additionProvider()
    {
        return [
            ['1', '1'],
            ['A', '10'],
            ['B', '11'],
            ['z', '61'],
            ['10', '62'],
            ['path', '12296565']
        ];
    }
}
