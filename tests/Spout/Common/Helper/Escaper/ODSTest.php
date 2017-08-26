<?php

namespace Box\Spout\Common\Helper\Escaper;

use Box\Spout\Common\Helper\Escaper;

/**
 * Class ODSTest
 *
 * @package Box\Spout\Common\Helper\Escaper
 */
class ODSTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestEscape()
    {
        return [
            ['test', 'test'],
            ['carl\'s "pokemon"', 'carl\'s "pokemon"'],
            ["\n", "\n"],
            ["\r", "\r"],
            ["\t", "\t"],
            ["\v", "�"],
            ["\f", "�"],
        ];
    }

    /**
     * @dataProvider dataProviderForTestEscape
     *
     * @param string $stringToEscape
     * @param string $expectedEscapedString
     * @return void
     */
    public function testEscape($stringToEscape, $expectedEscapedString)
    {
        $escaper = new Escaper\ODS();
        $escapedString = $escaper->escape($stringToEscape);

        $this->assertEquals($expectedEscapedString, $escapedString, 'Incorrect escaped string');
    }
}
