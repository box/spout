<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Type;
use Box\Spout\TestUsingResource;

/**
 * Class SheetTest
 *
 * @package Box\Spout\Writer
 */
class SheetTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testGetSheetNumber()
    {
        $sheets = $this->writeDataAndReturnSheets('test_get_sheet_number.xlsx');

        $this->assertEquals(2, count($sheets), '2 sheets should have been created');
        $this->assertEquals(0, $sheets[0]->getSheetNumber(), 'The first sheet should be number 0');
        $this->assertEquals(1, $sheets[1]->getSheetNumber(), 'The second sheet should be number 1');
    }

    /**
     * @return void
     */
    public function testGetSheetName()
    {
        $sheets = $this->writeDataAndReturnSheets('test_get_sheet_name.xlsx');

        $this->assertEquals(2, count($sheets), '2 sheets should have been created');
        $this->assertEquals('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        $this->assertEquals('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    /**
     * @param string $fileName
     * @return Sheet[]
     */
    private function writeDataAndReturnSheets($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\XLSX $writer */
        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($resourcePath);

        $writer->addRow(['xlsx--sheet1--11', 'xlsx--sheet1--12']);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(['xlsx--sheet2--11', 'xlsx--sheet2--12', 'xlsx--sheet2--13']);

        $writer->close();

        return $writer->getSheets();
    }
}
