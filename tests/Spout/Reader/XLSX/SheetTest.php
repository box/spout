<?php

namespace Box\Spout\Reader\XLSX;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class SheetTest
 */
class SheetTest extends TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testReaderShouldReturnCorrectSheetInfos()
    {
        // NOTE: This spreadsheet has its second tab defined as active
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names_and_custom_active_tab.xlsx');

        $this->assertEquals('CustomName1', $sheets[0]->getName());
        $this->assertEquals(0, $sheets[0]->getIndex());
        $this->assertFalse($sheets[0]->isActive());

        $this->assertEquals('CustomName2', $sheets[1]->getName());
        $this->assertEquals(1, $sheets[1]->getIndex());
        $this->assertTrue($sheets[1]->isActive());
    }

    /**
     * @return void
     */
    public function testReaderShouldReturnCorrectSheetVisibility()
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_one_hidden_one_not.xlsx');

        $this->assertFalse($sheets[0]->isVisible());
        $this->assertTrue($sheets[1]->isVisible());
    }

    /**
     * @param string $fileName
     * @return Sheet[]
     */
    private function openFileAndReturnSheets($fileName)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
