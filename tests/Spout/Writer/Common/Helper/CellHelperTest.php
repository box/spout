<?php

namespace Box\Spout\Writer\Common\Helper;

/**
 * Class CellHelperTest
 *
 * @package Box\Spout\Writer\Common\Helper
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

    /**
     * @return array
     */
    public function testIsNonEmptyString()
    {
        $this->assertTrue(CellHelper::isNonEmptyString("string"));

        $this->assertFalse(CellHelper::isNonEmptyString(""));
        $this->assertFalse(CellHelper::isNonEmptyString(0));
        $this->assertFalse(CellHelper::isNonEmptyString(1));
        $this->assertFalse(CellHelper::isNonEmptyString(true));
        $this->assertFalse(CellHelper::isNonEmptyString(false));
        $this->assertFalse(CellHelper::isNonEmptyString(["string"]));
        $this->assertFalse(CellHelper::isNonEmptyString(new \stdClass()));
        $this->assertFalse(CellHelper::isNonEmptyString(null));
    }

    /**
     * @return array
     */
    public function testIsNumeric()
    {
        $this->assertTrue(CellHelper::isNumeric(0));
        $this->assertTrue(CellHelper::isNumeric(10));
        $this->assertTrue(CellHelper::isNumeric(10.1));
        $this->assertTrue(CellHelper::isNumeric(10.10000000000000000000001));
        $this->assertTrue(CellHelper::isNumeric(0x539));
        $this->assertTrue(CellHelper::isNumeric(02471));
        $this->assertTrue(CellHelper::isNumeric(0b10100111001));
        $this->assertTrue(CellHelper::isNumeric(1337e0));

        $this->assertFalse(CellHelper::isNumeric("0"));
        $this->assertFalse(CellHelper::isNumeric("42"));
        $this->assertFalse(CellHelper::isNumeric(true));
        $this->assertFalse(CellHelper::isNumeric([2]));
        $this->assertFalse(CellHelper::isNumeric(new \stdClass()));
        $this->assertFalse(CellHelper::isNumeric(null));
    }

    /**
     * @return array
     */
    public function testIsBoolean()
    {
        $this->assertTrue(CellHelper::isBoolean(true));
        $this->assertTrue(CellHelper::isBoolean(false));

        $this->assertFalse(CellHelper::isBoolean(0));
        $this->assertFalse(CellHelper::isBoolean(1));
        $this->assertFalse(CellHelper::isBoolean("0"));
        $this->assertFalse(CellHelper::isBoolean("1"));
        $this->assertFalse(CellHelper::isBoolean("true"));
        $this->assertFalse(CellHelper::isBoolean("false"));
        $this->assertFalse(CellHelper::isBoolean([true]));
        $this->assertFalse(CellHelper::isBoolean(new \stdClass()));
        $this->assertFalse(CellHelper::isBoolean(null));
    }
}
