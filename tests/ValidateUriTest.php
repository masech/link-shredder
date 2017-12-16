<?php

namespace Lish;

class ValidateUriTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testValidateUri($expected, $argument)
    {
        $this->assertEquals($expected, validateUri($argument, 'some.com'));
    }

    public function additionProvider()
    {
        return [
            ['valid', 'http://example.org:8080/path'],
            ['valid', 'https://example.org/path'],
            ['valid', 'ftp://user@host:12000/path'],
            ['valid', 'example.org/path'],
            ['invalid', 'any://example.org/path'],
            ['invalid', '.org/path'],
            ['invalid', '/path'],
            ['own', 'https://some.com/path'],
            ['own', 'some.com/path']
        ];
    }
}
