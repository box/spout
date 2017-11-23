<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Common\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class CellHelperTest
 */
class CellHelperTest extends TestCase
{
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
     * @return void
     */
    public function testGetColumnIndexFromCellIndexShouldThrowIfInvalidCellIndex()
    {
        $this->expectException(InvalidArgumentException::class);

        CellHelper::getColumnIndexFromCellIndex('InvalidCellIndex');
    }
}
