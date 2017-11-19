<?php

namespace Spout\Writer\Common\Manager;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Common\Manager\RowManager;
use PHPUnit\Framework\TestCase;

class RowManagerTest extends TestCase
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

        $row = new Row($cells, null);
        $this->assertEquals($expectedIsEmpty, $rowManager->isEmpty($row));
    }
}
