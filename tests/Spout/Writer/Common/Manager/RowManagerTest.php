<?php

namespace Spout\Writer\Common\Manager;

use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Manager\RowManager;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use PHPUnit\Framework\TestCase;

class RowManagerTest extends TestCase
{
    /**
     * @var RowManager
     */
    protected $rowManager;

    public function setUp()
    {
        $this->rowManager = new RowManager(new StyleMerger());
        parent::setUp();
    }

    public function testIsEmptyRow()
    {
        $row = new Row([], null, $this->rowManager);
        $this->assertTrue($this->rowManager->isEmpty($row));

        $row = new Row([
            new Cell(''),
        ], null, $this->rowManager);
        $this->assertTrue($this->rowManager->isEmpty($row));

        $row = new Row([
            new Cell(''),
            new Cell(''),
            new Cell('Okay'),
        ], null, $this->rowManager);
        $this->assertFalse($this->rowManager->isEmpty($row));
    }
}
