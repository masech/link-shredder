<?php

namespace Lish;

class AddPathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testAddPath($expected, $argument1, $argument2)
    {
        $this->assertEquals($expected, addPath($argument1, $argument2));
    }

    public function additionProvider()
    {
        return [
            [[], null, []],
            [['second', 'first'], 'second', ['first']],
            [['first'], 'first', ['first']],
            [['first'], 'first', []]
        ];
    }
}
