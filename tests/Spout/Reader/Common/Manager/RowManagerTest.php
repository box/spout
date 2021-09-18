<?php

namespace Box\Spout\Reader\Common\Manager;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\XLSX\Creator\HelperFactory;
use Box\Spout\Reader\XLSX\Creator\InternalEntityFactory;
use Box\Spout\Reader\XLSX\Creator\ManagerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class RowManagerTest
 */
class RowManagerTest extends TestCase
{
    /**
     * @return array<array>
     */
    public function dataProviderForTestFillMissingIndexesWithEmptyCells()
    {
        $cell1 = new Cell(1);
        $cell3 = new Cell(3);

        return [
            [[], []],
            [[1 => $cell1, 3 => $cell3], [new Cell(''), $cell1, new Cell(''), $cell3]],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFillMissingIndexesWithEmptyCells
     *
     * @param Cell[]|null $rowCells
     * @param Cell[] $expectedFilledCells
     */
    public function testFillMissingIndexesWithEmptyCells($rowCells, $expectedFilledCells) : void
    {
        $rowManager = $this->createRowManager();

        $rowToFill = new Row([], null);
        foreach ($rowCells as $cellIndex => $cell) {
            $rowToFill->setCellAtIndex($cell, $cellIndex);
        }

        $filledRow = $rowManager->fillMissingIndexesWithEmptyCells($rowToFill);
        $this->assertEquals($expectedFilledCells, $filledRow->getCells());
    }

    /**
     * @return array<array>
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
     * @param array<Cell> $cells
     * @param bool $expectedIsEmpty
     * @return void
     */
    public function testIsEmptyRow(array $cells, $expectedIsEmpty)
    {
        $rowManager = $this->createRowManager();
        $row = new Row($cells, null);

        $this->assertEquals($expectedIsEmpty, $rowManager->isEmpty($row));
    }

    /**
     * @return RowManager
     */
    private function createRowManager()
    {
        /** @var ManagerFactory $managerFactory */
        $managerFactory = $this->createMock(ManagerFactory::class);

        /** @var HelperFactory $helperFactory */
        $helperFactory = $this->createMock(HelperFactory::class);

        $entityFactory = new InternalEntityFactory($managerFactory, $helperFactory);

        return new RowManager($entityFactory);
    }
}
