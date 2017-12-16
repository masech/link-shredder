<?php

namespace Lish;

class CalculateOffsetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testCalculateOffset($expected, $argument1, $argument2, $argument3)
    {
        $this->assertEquals($expected, calculateOffset($argument1, $argument2, $argument3));
    }

    public function additionProvider()
    {
        return [
            [50, ['direction' => 'prev', 'current' => 60], 77, 10],
            [0, ['direction' => 'prev', 'current' => 0], 77, 10],
            [70, ['direction' => 'next', 'current' => 60], 77, 10],
            [0, ['direction' => 'next', 'current' => 70], 77, 10],
            [0, ['direction' => 'first', 'current' => 70], 77, 10],
            [70, ['direction' => 'last', 'current' => 0], 77, 10],
            [30, ['direction' => 'another', 'current' => 30], 77, 10],
            [0, ['direction' => 'another', 'current' => 'another'], 77, 10],
            [0, ['direction' => 'next', 'current' => 30], 40, 10],
            [0, [], 40, 10]
        ];
    }
}
