<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\RowManager;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
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
     * @return \PHPUnit_Framework_MockObject_MockObject|RowManager
     */
    private function getRowManagerMock()
    {
        return $this->createMock(RowManager::class);
    }

    /**
     * @return void
     */
    public function testValidInstance()
    {
        $this->assertInstanceOf(
            Row::class,
            new Row([], null, $this->getRowManagerMock())
        );
    }

    /**
     * @return void
     */
    public function testSetCells()
    {
        $row = new Row([], null, $this->getRowManagerMock());
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, count($row->getCells()));
    }

    /**
     * @return void
     */
    public function testSetCellsResets()
    {
        $row = new Row([], null, $this->getRowManagerMock());
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, count($row->getCells()));

        $row->setCells([$this->getCellMock()]);

        $this->assertEquals(1, count($row->getCells()));
    }

    /**
     * @return void
     */
    public function testGetCells()
    {
        $row = new Row([], null, $this->getRowManagerMock());

        $this->assertEquals(0, count($row->getCells()));

        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, count($row->getCells()));
    }

    /**
     * @return void
     */
    public function testAddCell()
    {
        $row = new Row([], null, $this->getRowManagerMock());
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        $this->assertEquals(2, count($row->getCells()));

        $row->addCell($this->getCellMock());

        $this->assertEquals(3, count($row->getCells()));
    }

    /**
     * @return void
     */
    public function testFluentInterface()
    {
        $row = new Row([], null, $this->getRowManagerMock());
        $row
            ->addCell($this->getCellMock())
            ->setStyle($this->getStyleMock())
            ->setCells([]);

        $this->assertTrue(is_object($row));
    }
}
