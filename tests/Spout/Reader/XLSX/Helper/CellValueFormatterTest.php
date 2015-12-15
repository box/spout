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
    public function dataProviderForExcelDateTest()
    {
        return [
            [ CellValueFormatter::CELL_TYPE_NUMERIC, 42429, '2016-02-29 00:00:00' ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, '146098', '2299-12-31 00:00:00' ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, -700, null ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, 0, null ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, 0.5, null ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, 1, '1900-01-01 00:00:00' ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, 59.999988425926, '1900-02-28 23:59:59' ],
            [ CellValueFormatter::CELL_TYPE_NUMERIC, 60.458333333333, '1900-02-28 11:00:00' ],
        ];
    }

    /**
     * @dataProvider dataProviderForExcelDateTest
     *
     * @return void
     */
    public function testExcelDate($cellType, $nodeValue, $expectedDateAsString)
    {
        $nodeListMock = $this->getMockBuilder('DOMNodeList')->disableOriginalConstructor()->getMock();

        $nodeListMock
            ->expects($this->atLeastOnce())
            ->method('item')
            ->with(0)
            ->will($this->returnValue((object)[ 'nodeValue' => $nodeValue ]));

        $nodeMock = $this->getMockBuilder('DOMElement')->disableOriginalConstructor()->getMock();

        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getAttribute')
            ->will($this->returnValueMap([
                [ CellValueFormatter::XML_ATTRIBUTE_TYPE, $cellType ],
                [ CellValueFormatter::XML_ATTRIBUTE_STYLE_ID, 123 ],
            ]));

        $nodeMock
            ->expects($this->atLeastOnce())
            ->method('getElementsByTagName')
            ->with(CellValueFormatter::XML_NODE_VALUE)
            ->will($this->returnValue($nodeListMock));

        $styleHelperMock = $this->getMockBuilder(__NAMESPACE__ . '\StyleHelper')->disableOriginalConstructor()->getMock();

        $styleHelperMock
            ->expects($this->once())
            ->method('shouldFormatNumericValueAsDate')
            ->with(123)
            ->will($this->returnValue(true));

        $instance = new CellValueFormatter(null, $styleHelperMock);

        $result = $instance->extractAndFormatNodeValue($nodeMock);

        if ($expectedDateAsString === null) {
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf('DateTime', $result);
            $this->assertSame($expectedDateAsString, $result->format('Y-m-d H:i:s'));
        }
    }

}
