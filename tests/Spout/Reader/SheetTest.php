<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Type;
use Box\Spout\TestUsingResource;

/**
 * Class SheetTest
 *
 * @package Box\Spout\Reader
 */
class SheetTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testNextSheetShouldReturnCorrectSheetInfos()
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names.xlsx');

        $this->assertEquals('CustomName1', $sheets[0]->getName());
        $this->assertEquals(0, $sheets[0]->getIndex());
        $this->assertEquals(1, $sheets[0]->getId());

        $this->assertEquals('CustomName2', $sheets[1]->getName());
        $this->assertEquals(1, $sheets[1]->getIndex());
        $this->assertEquals(2, $sheets[1]->getId());
    }

    /**
     * @param string $fileName
     * @return Sheet[]
     */
    private function openFileAndReturnSheets($fileName)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($resourcePath);

        $sheets = [];
        while ($reader->hasNextSheet()) {
            $sheets[] = $reader->nextSheet();
        }

        $reader->close();

        return $sheets;
    }
}
