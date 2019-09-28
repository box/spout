<?php

namespace Box\Spout\Writer\Common\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Class CellHelperTest
 */
class CellHelperTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestGetColumnLettersFromColumnIndex()
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
     * @dataProvider dataProviderForTestGetColumnLettersFromColumnIndex
     *
     * @param int    $columnIndex
     * @param string $expectedColumnLetters
     *
     * @return void
     */
    public function testGetColumnLettersFromColumnIndex($columnIndex, $expectedColumnLetters)
    {
        $this->assertEquals($expectedColumnLetters, CellHelper::getColumnLettersFromColumnIndex($columnIndex));
    }
}
