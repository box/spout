<?php

namespace Spout\Writer\Common\Manager;

use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Manager\RowManager;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use PHPUnit\Framework\TestCase;

class RowManagerTest extends TestCase
{
    /**
     * @return void
     */
    public function testApplyStyle()
    {
        $rowManager = new RowManager(new StyleMerger());
        $row = new Row([new Cell('test')], null, $rowManager);

        $this->assertFalse($row->getStyle()->isFontBold());

        $style = (new StyleBuilder())->setFontBold()->build();
        $rowManager->applyStyle($row, $style);

        $this->assertTrue($row->getStyle()->isFontBold());
    }

    /**
     * @return array
     */
    public function dataProviderForTestHasCells()
    {
        return [
            // cells, expected hasCells
            [[], false],
            [[new Cell('')], true],
            [[new Cell(null)], true],
            [[new Cell('test')], true],
        ];
    }

    /**
     * @dataProvider dataProviderForTestHasCells
     *
     * @param array $cells
     * @param bool $expectedHasCells
     * @return void
     */
    public function testHasCells(array $cells, $expectedHasCells)
    {
        $rowManager = new RowManager(new StyleMerger());

        $row = new Row($cells, null, $rowManager);
        $this->assertEquals($expectedHasCells, $rowManager->hasCells($row));
    }

    /**
     * @return array
     */
    public function dataProviderForTestIsEmptyRow()
    {
        return [
            // cells, expected isEmpty
            [[], true],
            [[new Cell('')], true],
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
        $rowManager = new RowManager(new StyleMerger());

        $row = new Row($cells, null, $rowManager);
        $this->assertEquals($expectedIsEmpty, $rowManager->isEmpty($row));
    }
}
