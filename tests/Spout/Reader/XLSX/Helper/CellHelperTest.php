<?php

namespace Box\Spout\Reader\XLSX\Helper;

/**
 * Class CellHelperTest
 *
 * @package Box\Spout\Reader\XLSX\Helper
 */
class CellHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testFillMissingArrayIndexes()
    {
        $arrayToFill = [1 => 1, 3 => 3];
        $filledArray = CellHelper::fillMissingArrayIndexes($arrayToFill, 'FILL');

        $expectedFilledArray = ['FILL', 1, 'FILL', 3];
        $this->assertEquals($expectedFilledArray, $filledArray);
    }

    /**
     * @return array
     */
    public function dataProviderForTestGetColumnIndexFromCellIndex()
    {
        return [
            ['A1', 0],
            ['Z3', 25],
            ['AA5', 26],
            ['AB24', 27],
            ['BC5', 54],
            ['BCZ99', 1455],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetColumnIndexFromCellIndex
     *
     * @param string $cellIndex
     * @param int $expectedColumnIndex
     * @return void
     */
    public function testGetColumnIndexFromCellIndex($cellIndex, $expectedColumnIndex)
    {
        $this->assertEquals($expectedColumnIndex, CellHelper::getColumnIndexFromCellIndex($cellIndex));
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function testGetColumnIndexFromCellIndexShouldThrowIfInvalidCellIndex()
    {
        CellHelper::getColumnIndexFromCellIndex('InvalidCellIndex');
    }
}
