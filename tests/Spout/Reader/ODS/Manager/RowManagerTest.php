<?php

namespace Box\Spout\Reader\ODS\Manager;

use Box\Spout\Reader\Common\Entity\Cell;
use Box\Spout\Reader\Common\Entity\Row;

/**
 * Class RowManagerTest
 */
class RowManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestIsEmptyRow()
    {
        return [
            // cells, expected isEmpty
            [[], true],
            [[new Cell('')], true],
            [[new Cell(''), new Cell('')], true],
            [[new Cell(''), new Cell(''), new Cell('Okay')], false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsEmptyRow
     *
     * @param array $cells
     * @param bool $expectedIsEmpty
     * @return void
     */
    public function testIsEmptyRow(array $cells, $expectedIsEmpty)
    {
        $rowManager = new RowManager();
        $row = new Row($cells);

        $this->assertEquals($expectedIsEmpty, $rowManager->isEmpty($row));
    }
}
