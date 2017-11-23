<?php

namespace Box\Spout\Writer\XLSX\Manager\Style;

use PHPUnit\Framework\TestCase;

/**
 * Class StyleManagerTest
 */
class StyleManagerTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestShouldApplyStyleOnEmptyCell()
    {
        return [
            // fillId, borderId, expected result
            [null, null, false],
            [0, null, false],
            [null, 0, false],
            [0, 0, false],
            [12, null, true],
            [null, 12, true],
            [12, 0, true],
            [0, 12, true],
            [12, 13, true],
        ];
    }

    /**
     * @dataProvider dataProviderForTestShouldApplyStyleOnEmptyCell
     *
     * @param int|null $fillId
     * @param int|null $borderId
     * @param bool $expectedResult
     * @return void
     */
    public function testShouldApplyStyleOnEmptyCell($fillId, $borderId, $expectedResult)
    {
        $styleRegistryMock = $this->getMockBuilder(StyleRegistry::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getFillIdForStyleId', 'getBorderIdForStyleId'])
                                ->getMock();

        $styleRegistryMock
            ->method('getFillIdForStyleId')
            ->willReturn($fillId);

        $styleRegistryMock
            ->method('getBorderIdForStyleId')
            ->willReturn($borderId);

        $styleManager = new StyleManager($styleRegistryMock);
        $shouldApply = $styleManager->shouldApplyStyleOnEmptyCell(99);

        $this->assertEquals($expectedResult, $shouldApply);
    }
}
