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
    public function testGetSheetIndex()
    {
        $sheets = $this->writeDataAndReturnSheets('test_get_sheet_index.xlsx');

        $this->assertEquals(2, count($sheets), '2 sheets should have been created');
        $this->assertEquals(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        $this->assertEquals(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
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
     * @return void
     */
    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $fileName = 'test_set_name_should_create_sheet_with_custom_name.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($resourcePath);

        $customSheetName = 'CustomName';
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);

        $writer->addRow(['xlsx--11', 'xlsx--12']);
        $writer->close();

        $this->assertSheetNameEquals($customSheetName, $resourcePath, "The sheet name should have been changed to '$customSheetName'");
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

    /**
     * @param string $expectedName
     * @param string $resourcePath
     * @param string $message
     * @return void
     */
    private function assertSheetNameEquals($expectedName, $resourcePath, $message = '')
    {
        $pathToWorkbookFile = $resourcePath . '#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains('<sheet name="' . $expectedName . '"', $xmlContents, $message);
    }
}
