<?php

namespace Box\Spout\Common\Helper\Escaper;

use Box\Spout\Common\Helper\Escaper;
use PHPUnit\Framework\TestCase;

/**
 * Class XLSXTest
 */
class XLSXTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestEscape()
    {
        return [
            ['test', 'test'],
            ['adam\'s "car"', 'adam&#039;s &quot;car&quot;'],
            ["\n", "\n"],
            ["\r", "\r"],
            ["\t", "\t"],
            [chr(0), '_x0000_'],
            [chr(4), '_x0004_'],
            ['_x0000_', '_x005F_x0000_'],
            [chr(21), '_x0015_'],
            ['control ' . chr(21) . ' character', 'control _x0015_ character'],
            ['control\'s ' . chr(21) . ' "character"', 'control&#039;s _x0015_ &quot;character&quot;'],
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
        $escaper = new Escaper\XLSX();
        $escapedString = $escaper->escape($stringToEscape);

        $this->assertEquals($expectedEscapedString, $escapedString, 'Incorrect escaped string');
    }

    /**
     * @return array
     */
    public function dataProviderForTestUnescape()
    {
        return [
            ['test', 'test'],
            ['adam&#039;s &quot;car&quot;', 'adam&#039;s &quot;car&quot;'],
            ["\n", "\n"],
            ["\r", "\r"],
            ["\t", "\t"],
            ['_x0000_', chr(0)],
            ['_x0004_', chr(4)],
            ['_x005F_x0000_', '_x0000_'],
            ['_x0015_', chr(21)],
            ['control _x0015_ character', 'control ' . chr(21) . ' character'],
            ['control&#039;s _x0015_ &quot;character&quot;', 'control&#039;s ' . chr(21) . ' &quot;character&quot;'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestUnescape
     *
     * @param string $stringToUnescape
     * @param string $expectedUnescapedString
     * @return void
     */
    public function testUnescape($stringToUnescape, $expectedUnescapedString)
    {
        $escaper = new Escaper\XLSX();
        $unescapedString = $escaper->unescape($stringToUnescape);

        $this->assertEquals($expectedUnescapedString, $unescapedString, 'Incorrect escaped string');
    }
}
