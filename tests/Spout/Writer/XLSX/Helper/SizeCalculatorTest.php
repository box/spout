<?php

namespace Box\Spout\Writer\XLSX\Helper;

/**
 * Class SizeCalculatorTest
 * Simple unit tests only.
 */
class SizeCalculatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSetFontShouldCallSizeCollectionGetter()
    {
        $fontName = 'Arial';
        $fontSize = 12;

        $sizeCollectionMock = $this->getSizeCollectionMock();
        $sizeCollectionMock->expects(self::once())->method('get')->with($fontName, $fontSize);

        $sizeCalculator = new SizeCalculator($sizeCollectionMock);
        $sizeCalculator->setFont($fontName, $fontSize);
    }

    public function testGetCellWidthShouldReturnValueGreaterThanOneForNonEmptyString()
    {
        $sizeCalculator = new SizeCalculator($this->getSizeCollectionMock());
        self::assertGreaterThan(1, $sizeCalculator->getCellWidth('a', 12));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SizeCollection
     */
    private function getSizeCollectionMock()
    {
        return $this->getMockBuilder('Box\Spout\Writer\XLSX\Helper\SizeCollection')->disableOriginalConstructor()->getMock();
    }
}
