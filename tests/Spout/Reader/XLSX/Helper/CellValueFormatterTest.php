<?php

namespace Box\Spout\Reader\XLSX\Helper;

/**
 * Class CellValueFormatterTest
 *
 * @package Box\Spout\Reader\XLSX\Helper
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
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0, null],
            [CellValueFormatter::CELL_TYPE_NUMERIC, 0.5, null],
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
        $nodeListMock = $this->getMockBuilder('DOMNodeList')->disableOriginalConstructor()->getMock();

        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('item')
            ->with(0)
            ->will($this->returnValue((object)['nodeValue' => $nodeValue]));

        $nodeMock = $this->getMockBuilder('DOMElement')->disableOriginalConstructor()->getMock();

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

        $styleHelperMock = $this->getMockBuilder('Box\Spout\Reader\XLSX\Helper\StyleHelper')->disableOriginalConstructor()->getMock();

        $styleHelperMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->with(123)
            ->will($this->returnValue(true));

        $formatter = new CellValueFormatter(null, $styleHelperMock);
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
        return [
            [42, 42, 'integer'],
            [42.5, 42.5, 'double'],
            [-42, -42, 'integer'],
            [-42.5, -42.5, 'double'],
            ['42', 42, 'integer'],
            ['42.5', 42.5, 'double'],
            [865640023012945, 865640023012945, 'integer'],
            ['865640023012945', 865640023012945, 'integer'],
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
        $styleHelperMock = $this->getMockBuilder('Box\Spout\Reader\XLSX\Helper\StyleHelper')->disableOriginalConstructor()->getMock();
        $styleHelperMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->will($this->returnValue(false));

        $formatter = new CellValueFormatter(null, $styleHelperMock);
        $formattedValue = \ReflectionHelper::callMethodOnObject($formatter, 'formatNumericCellValue', $value, 0);

        $this->assertEquals($expectedFormattedValue, $formattedValue);
        $this->assertEquals($expectedType, gettype($formattedValue));
    }
}
