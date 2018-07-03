<?php

namespace Box\Spout\Common\Escaper;

/**
 * Class ODSTest
 *
 * @package Box\Spout\Common\Escaper
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
            ['carl\'s "pokemon"', 'carl&#039;s &quot;pokemon&quot;'],
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
        $escaper = \Box\Spout\Common\Escaper\ODS::getInstance();
        $escapedString = $escaper->escape($stringToEscape);

        $this->assertEquals($expectedEscapedString, $escapedString, 'Incorrect escaped string');
    }
}
