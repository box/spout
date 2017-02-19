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
     * @return array
     */
    public function dataProviderForTestFillMissingArrayIndexes()
    {
        return [
            [ null, [] ],
            [ [], [] ],
            [ [1 => 1, 3 => 3], ['FILL', 1, 'FILL', 3] ]
        ];
    }

    /**
     * @dataProvider dataProviderForTestFillMissingArrayIndexes
     * @param array $arrayToFill
     * @param array $expectedFilledArray
     */
    public function testFillMissingArrayIndexes($arrayToFill, array $expectedFilledArray)
    {
        $filledArray = CellHelper::fillMissingArrayIndexes($arrayToFill, 'FILL');
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
