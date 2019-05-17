<?php

namespace Box\Spout\Reader\ODS;

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
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names.ods');

        $this->assertEquals('Sheet First', $sheets[0]->getName());
        $this->assertEquals(0, $sheets[0]->getIndex());
        $this->assertFalse($sheets[0]->isActive());

        $this->assertEquals('Sheet Last', $sheets[1]->getName());
        $this->assertEquals(1, $sheets[1]->getIndex());
        $this->assertTrue($sheets[1]->isActive());
    }

    /**
     * @return void
     */
    public function testReaderShouldDefineFirstSheetAsActiveByDefault()
    {
        // NOTE: This spreadsheet has no information about the active sheet
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_no_settings_xml_file.ods');

        $this->assertTrue($sheets[0]->isActive());
        $this->assertFalse($sheets[1]->isActive());
    }

    /**
     * @return void
     */
    public function testReaderShouldReturnCorrectSheetVisibility()
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_one_hidden_one_not.ods');

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
        $reader = ReaderEntityFactory::createODSReader();
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
