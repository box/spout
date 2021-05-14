<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Common\Helper\Escaper;
use Box\Spout\Reader\Exception\InvalidValueException;
use Box\Spout\Reader\XLSX\Manager\StyleManager;
use PHPUnit\Framework\TestCase;

/**
 * Class CellValueFormatterTest
 */
class CellValueFormatterTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestExcelDate()
    {
        return [
            // use 1904 dates, node value, expected date as string

            // 1900 calendar
            [false, 3687.4207639, '1910-02-03 10:05:54'],
            [false, 2.5000000, '1900-01-01 12:00:00'],
            [false, 2958465.9999884, '9999-12-31 23:59:59'],
            [false, 2958465.9999885, null],
            [false, -2337.999989, '1893-08-05 00:00:01'],
            [false, -693593, '0001-01-01 00:00:00'],
            [false, -693593.0000001, null],
            [false, 0, '1899-12-30 00:00:00'],
            [false, 0.25, '1899-12-30 06:00:00'],
            [false, 0.5, '1899-12-30 12:00:00'],
            [false, 0.75, '1899-12-30 18:00:00'],
            [false, 0.99999, '1899-12-30 23:59:59'],
            [false, 1, '1899-12-31 00:00:00'],
            [false, '3687.4207639', '1910-02-03 10:05:54'],

            // 1904 calendar
            [true, 2225.4207639, '1910-02-03 10:05:54'],
            [true, 2.5000000, '1904-01-03 12:00:00'],
            [true, 2957003.9999884, '9999-12-31 23:59:59'],
            [true, 2957003.9999885, null],
            [true, -3799.999989, '1893-08-05 00:00:01'],
            [true, -695055, '0001-01-01 00:00:00'],
            [true, -695055.0000001, null],
            [true, 0, '1904-01-01 00:00:00'],
            [true, 0.25, '1904-01-01 06:00:00'],
            [true, 0.5, '1904-01-01 12:00:00'],
            [true, 0.75, '1904-01-01 18:00:00'],
            [true, 0.99999, '1904-01-01 23:59:59'],
            [true, 1, '1904-01-02 00:00:00'],
            [true, '2225.4207639', '1910-02-03 10:05:54'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestExcelDate
     *
     * @param bool $shouldUse1904Dates
     * @param int|float|string $nodeValue
     * @param string|null $expectedDateAsString
     * @return void
     */
    public function testExcelDate($shouldUse1904Dates, $nodeValue, $expectedDateAsString)
    {
        $nodeListMock = $this->createMock(\DOMNodeList::class);

        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('item')
            ->with(0)
            ->will($this->returnValue((object) ['nodeValue' => $nodeValue]));

        $nodeMock = $this->createMock(\DOMElement::class);

        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getAttribute')
            ->will($this->returnValueMap([
                [CellValueFormatter::XML_ATTRIBUTE_TYPE, CellValueFormatter::CELL_TYPE_NUMERIC],
                [CellValueFormatter::XML_ATTRIBUTE_STYLE_ID, 123],
            ]));

        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getElementsByTagName')
            ->with(CellValueFormatter::XML_NODE_VALUE)
            ->will($this->returnValue($nodeListMock));

        /** @var StyleManager|\PHPUnit_Framework_MockObject_MockObject $styleManagerMock */
        $styleManagerMock = $this->createMock(StyleManager::class);

        $styleManagerMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->with(123)
            ->will($this->returnValue(true));

        $formatter = new CellValueFormatter(null, $styleManagerMock, false, $shouldUse1904Dates, new Escaper\XLSX());

        try {
            $result = $formatter->extractAndFormatNodeValue($nodeMock);

            if ($expectedDateAsString === null) {
                $this->fail('An exception should have been thrown');
            } else {
                $this->assertInstanceOf(\DateTime::class, $result);
                $this->assertSame($expectedDateAsString, $result->format('Y-m-d H:i:s'));
            }
        } catch (InvalidValueException $exception) {
            // do nothing
        }
    }

    /**
     * @return array
     */
    public function dataProviderForTestFormatNumericCellValueWithNumbers()
    {
        // Some test values exceed PHP_INT_MAX on 32-bit PHP. They are
        // therefore converted to as doubles automatically by PHP.
        $expectedBigNumberType = (PHP_INT_SIZE < 8 ? 'double' : 'integer');

        return [
            [42, 42, 'integer'],
            [42.5, 42.5, 'double'],
            [-42, -42, 'integer'],
            [-42.5, -42.5, 'double'],
            ['42', 42, 'integer'],
            ['42.5', 42.5, 'double'],
            [865640023012945, 865640023012945, $expectedBigNumberType],
            ['865640023012945', 865640023012945, $expectedBigNumberType],
            [865640023012945.5, 865640023012945.5, 'double'],
            ['865640023012945.5', 865640023012945.5, 'double'],
            [PHP_INT_MAX, PHP_INT_MAX, 'integer'],
            [~PHP_INT_MAX + 1, ~PHP_INT_MAX + 1, 'integer'], // ~PHP_INT_MAX === PHP_INT_MIN, PHP_INT_MIN being PHP7+
            [PHP_INT_MAX + 1, PHP_INT_MAX + 1, 'double'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFormatNumericCellValueWithNumbers
     *
     * @param int|float|string $value
     * @param int|float $expectedFormattedValue
     * @param string $expectedType
     * @return void
     */
    public function testFormatNumericCellValueWithNumbers($value, $expectedFormattedValue, $expectedType)
    {
        /** @var StyleManager|\PHPUnit_Framework_MockObject_MockObject $styleManagerMock */
        $styleManagerMock = $this->createMock(StyleManager::class);
        $styleManagerMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->will($this->returnValue(false));

        $formatter = new CellValueFormatter(null, $styleManagerMock, false, false, new Escaper\XLSX());
        $formattedValue = \ReflectionHelper::callMethodOnObject($formatter, 'formatNumericCellValue', $value, 0);

        $this->assertEquals($expectedFormattedValue, $formattedValue);
        $this->assertEquals($expectedType, gettype($formattedValue));
    }

    /**
     * @return array
     */
    public function dataProviderForTestFormatStringCellValue()
    {
        return [
            ['A', 'A'],
            [' A ', ' A '],
            ["\n\tA\n\t", "\n\tA\n\t"],
            [' ', ' '],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFormatStringCellValue
     *
     * @param string $value
     * @param string $expectedFormattedValue
     * @return void
     */
    public function testFormatInlineStringCellValue($value, $expectedFormattedValue)
    {
        $nodeListMock = $this->createMock(\DOMNodeList::class);
        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('count')
            ->willReturn(1);
        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('item')
            ->with(0)
            ->willReturn((object) ['nodeValue' => $value]);

        $nodeMock = $this->createMock(\DOMElement::class);
        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getElementsByTagName')
            ->with(CellValueFormatter::XML_NODE_INLINE_STRING_VALUE)
            ->willReturn($nodeListMock);

        $formatter = new CellValueFormatter(null, null, false, false, new Escaper\XLSX());
        $formattedValue = \ReflectionHelper::callMethodOnObject($formatter, 'formatInlineStringCellValue', $nodeMock);

        $this->assertEquals($expectedFormattedValue, $formattedValue);
    }
}
