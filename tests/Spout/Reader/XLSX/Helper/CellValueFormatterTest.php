<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Common\Helper\Escaper;
use Box\Spout\Reader\XLSX\Manager\StyleManager;

/**
 * Class CellValueFormatterTest
 */
class CellValueFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestExcelDate()
    {
        return [
            [CellValueFormatter::CELL_TYPE_NUMERIC, 42429, '2016-02-29 00:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, '146098', '2299-12-31 00:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, -700, null],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0, '1900-01-01 00:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0.25, '1900-01-01 06:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0.5, '1900-01-01 12:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0.75, '1900-01-01 18:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0.99999, '1900-01-01 23:59:59'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 1, '1900-01-01 00:00:00'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 59.999988425926, '1900-02-28 23:59:59'],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 60.458333333333, '1900-02-28 11:00:00'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestExcelDate
     *
     * @param string $cellType
     * @param int|float|string $nodeValue
     * @param string|null $expectedDateAsString
     * @return void
     */
    public function testExcelDate($cellType, $nodeValue, $expectedDateAsString)
    {
        $nodeListMock = $this->createMock('DOMNodeList');

        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('item')
            ->with(0)
            ->will($this->returnValue((object) ['nodeValue' => $nodeValue]));

        $nodeMock = $this->createMock('DOMElement');

        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getAttribute')
            ->will($this->returnValueMap([
                [CellValueFormatter::XML_ATTRIBUTE_TYPE, $cellType],
                [CellValueFormatter::XML_ATTRIBUTE_STYLE_ID, 123],
            ]));

        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getElementsByTagName')
            ->with(CellValueFormatter::XML_NODE_VALUE)
            ->will($this->returnValue($nodeListMock));

        /** @var \Box\Spout\Reader\XLSX\Manager\StyleManager|\PHPUnit_Framework_MockObject_MockObject $styleManagerMock */
        $styleManagerMock = $this->createMock(StyleManager::class);

        $styleManagerMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->with(123)
            ->will($this->returnValue(true));

        $formatter = new CellValueFormatter(null, $styleManagerMock, false, new Escaper\XLSX());
        $result = $formatter->extractAndFormatNodeValue($nodeMock);

        if ($expectedDateAsString === null) {
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf('DateTime', $result);
            $this->assertSame($expectedDateAsString, $result->format('Y-m-d H:i:s'));
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
        /** @var \Box\Spout\Reader\XLSX\Manager\StyleManager|\PHPUnit_Framework_MockObject_MockObject $styleManagerMock */
        $styleManagerMock = $this->createMock(StyleManager::class);
        $styleManagerMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->will($this->returnValue(false));

        $formatter = new CellValueFormatter(null, $styleManagerMock, false, new Escaper\XLSX());
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
        $nodeListMock = $this->createMock('DOMNodeList');
        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('item')
            ->with(0)
            ->will($this->returnValue((object) ['nodeValue' => $value]));

        $nodeMock = $this->createMock('DOMElement');
        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getElementsByTagName')
            ->with(CellValueFormatter::XML_NODE_INLINE_STRING_VALUE)
            ->will($this->returnValue($nodeListMock));

        $formatter = new CellValueFormatter(null, null, false, new Escaper\XLSX());
        $formattedValue = \ReflectionHelper::callMethodOnObject($formatter, 'formatInlineStringCellValue', $nodeMock);

        $this->assertEquals($expectedFormattedValue, $formattedValue);
    }
}
