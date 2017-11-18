<?php

namespace Box\Spout\Writer\Common\Helper;

/**
 * Class CellHelperTest
 */
class CellHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestGetCellIndexFromColumnIndex()
    {
        return [
            [0, 'A'],
            [1, 'B'],
            [25, 'Z'],
            [26, 'AA'],
            [28, 'AC'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetCellIndexFromColumnIndex
     *
     * @param int $columnIndex
     * @param string $expectedCellIndex
     * @return void
     */
    public function testGetCellIndexFromColumnIndex($columnIndex, $expectedCellIndex)
    {
        $this->assertEquals($expectedCellIndex, CellHelper::getCellIndexFromColumnIndex($columnIndex));
    }
}
