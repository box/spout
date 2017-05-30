<?php

namespace Box\Spout\Writer\Common\Entity;

use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{
    protected function styleMock()
    {
        $styleMock = $this
            ->getMockBuilder('Box\Spout\Writer\Common\Entity\Style\Style');
        return $styleMock;
    }

    protected function cellMock()
    {
        $cellMock = $this
            ->getMockBuilder('Box\Spout\Writer\Common\Entity\Cell')
            ->disableOriginalConstructor();
        return $cellMock;
    }

    public function testValidInstance()
    {
        $this->assertInstanceOf('Box\Spout\Writer\Common\Entity\Row', new Row());
        $this->assertInstanceOf('Box\Spout\Writer\Common\Entity\Row', new Row([]));
        $this->assertInstanceOf('Box\Spout\Writer\Common\Entity\Row', new Row([], $this->styleMock()->getMock()));
        $this->assertInstanceOf('Box\Spout\Writer\Common\Entity\Row', new Row([$this->cellMock()->getMock()]));
    }

    public function testSetCells()
    {
        $o = new Row();
        $o->setCells([$this->cellMock()->getMock(), $this->cellMock()->getMock()]);
        $this->assertEquals(2, count($o->getCells()));
    }

    public function testSetCellsResets()
    {
        $o = new Row();
        $o->setCells([$this->cellMock()->getMock(), $this->cellMock()->getMock()]);
        $this->assertEquals(2, count($o->getCells()));
        $o->setCells([$this->cellMock()->getMock()]);
        $this->assertEquals(1, count($o->getCells()));
    }

    public function testGetCells()
    {
        $o = new Row();
        $this->assertEquals(0, count($o->getCells()));
        $o->setCells([$this->cellMock()->getMock(), $this->cellMock()->getMock()]);
        $this->assertEquals(2, count($o->getCells()));
    }

    public function testAddCell()
    {
        $o = new Row();
        $o->setCells([$this->cellMock()->getMock(), $this->cellMock()->getMock()]);
        $this->assertEquals(2, count($o->getCells()));
        $o->addCell($this->cellMock()->getMock());
        $this->assertEquals(3, count($o->getCells()));
    }

    public function testFluentInterface()
    {
        $o = new Row();
        $o
            ->addCell($this->cellMock()->getMock())
            ->setStyle($this->styleMock()->getMock())
            ->setCells([]);
        $this->assertTrue(is_object($o));
    }
}
