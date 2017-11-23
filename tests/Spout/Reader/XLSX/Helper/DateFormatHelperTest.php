<?php

namespace Box\Spout\Reader\XLSX\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Class DateFormatHelperTest
 */
class DateFormatHelperTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestToPHPDateFormat()
    {
        return [
            // Excel date format, expected PHP date format
            ['m/d/yy hh:mm', 'n/j/y H:i'],
            ['mmm-yy', 'M-y'],
            ['d-mmm-yy', 'j-M-y'],
            ['m/dd/yyyy', 'n/d/Y'],
            ['e mmmmm dddd', 'Y M l'],
            ['MMMM DDD', 'F D'],
            ['hh:mm:ss.s', 'H:i:s'],
            ['h:mm:ss AM/PM', 'g:i:s A'],
            ['hh:mm AM/PM', 'h:i A'],
            ['[$-409]hh:mm AM/PM', 'h:i A'],
            ['[$USD-F480]hh:mm AM/PM', 'h:i A'],
            ['"Day " d', '\\D\\a\\y\\  j'],
            ['yy "Year" m "Month"', 'y \\Y\\e\\a\\r n \\M\\o\\n\\t\\h'],
            ['mmm-yy;@', 'M-y'],
            ['[$-409]hh:mm AM/PM;"foo"@', 'h:i A'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestToPHPDateFormat
     *
     * @param string $excelDateFormat
     * @param string $expectedPHPDateFormat
     * @return void
     */
    public function testToPHPDateFormat($excelDateFormat, $expectedPHPDateFormat)
    {
        $phpDateFormat = DateFormatHelper::toPHPDateFormat($excelDateFormat);
        $this->assertEquals($expectedPHPDateFormat, $phpDateFormat);
    }
}
