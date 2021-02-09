<?php

namespace Box\Spout\Common\Entity;

use Box\Spout\Common\Entity\Style\Style;

class RowTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Style
     */
    private function getStyleMock()
    {
        return $this->createMock(Style::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Cell
     */
    private function getCellMock()
    {
        return $this->createMock(Cell::class);
    }

    /**
     * @return void
     */
    public function testValidInstance()
    {
        $this->assertInstanceOf(Row::class, new Row([], null));
    }

    /**
     * @return void
     */
    public function testSetCells()
    {
        $row = new Row([], null);
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, $row->getNumCells());
    }

    /**
     * @return void
     */
    public function testSetCellsResets()
    {
        $row = new Row([], null);
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, $row->getNumCells());

        $row->setCells([$this->getCellMock()]);

        $this->assertEquals(1, $row->getNumCells());
    }

    /**
     * @return void
     */
    public function testGetCells()
    {
        $row = new Row([], null);

        $this->assertEquals(0, $row->getNumCells());

        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, $row->getNumCells());
    }

    /**
     * @return void
     */
    public function testGetCellAtIndex()
    {
        $row = new Row([], null);
        $cellMock = $this->getCellMock();
        $row->setCellAtIndex($cellMock, 3);

        $this->assertEquals($cellMock, $row->getCellAtIndex(3));
        $this->assertNull($row->getCellAtIndex(10));
    }

    /**
     * @return void
     */
    public function testSetCellAtIndex()
    {
        $row = new Row([], null);
        $cellMock = $this->getCellMock();
        $row->setCellAtIndex($cellMock, 1);

        $this->assertEquals(2, $row->getNumCells());
        $this->assertNull($row->getCellAtIndex(0));
    }

    /**
     * @return void
     */
    public function testAddCell()
    {
        $row = new Row([], null);
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, $row->getNumCells());

        $row->addCell($this->getCellMock());

        $this->assertEquals(3, $row->getNumCells());
    }

    /**
     * @return void
     */
    public function testFluentInterface()
    {
        $row = new Row([], null);
        $row
            ->addCell($this->getCellMock())
            ->setStyle($this->getStyleMock())
            ->setCells([]);

        $this->assertInstanceOf(Row::class, $row);
    }
}
