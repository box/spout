<?php

namespace Box\Spout\Writer\ODS;

use Box\Spout\Common\Type;
use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Sheet;
use Box\Spout\Writer\WriterFactory;

/**
 * Class SheetTest
 *
 * @package Box\Spout\Writer\ODS
 */
class SheetTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testGetSheetIndex()
    {
        $sheets = $this->writeDataToMulitpleSheetsAndReturnSheets('test_get_sheet_index.ods');

        $this->assertEquals(2, count($sheets), '2 sheets should have been created');
        $this->assertEquals(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        $this->assertEquals(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
    }

    /**
     * @return void
     */
    public function testGetSheetName()
    {
        $sheets = $this->writeDataToMulitpleSheetsAndReturnSheets('test_get_sheet_name.ods');

        $this->assertEquals(2, count($sheets), '2 sheets should have been created');
        $this->assertEquals('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        $this->assertEquals('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $fileName = 'test_set_name_should_create_sheet_with_custom_name.ods';
        $customSheetName = 'CustomName';
        $this->writeDataAndReturnSheetWithCustomName($fileName, $customSheetName);

        $this->assertSheetNameEquals($customSheetName, $fileName, "The sheet name should have been changed to '$customSheetName'");
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\InvalidSheetNameException
     * @return void
     */
    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $fileName = 'test_set_name_with_non_unique_name.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);

        $customSheetName = 'Sheet name';

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);

        $writer->addNewSheetAndMakeItCurrent();
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);
    }

    /**
     * @param string $fileName
     * @param string $sheetName
     * @return Sheet
     */
    private function writeDataAndReturnSheetWithCustomName($fileName, $sheetName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheetName);

        $writer->addRow(['ods--11', 'ods--12']);
        $writer->close();
    }

    /**
     * @param string $fileName
     * @return Sheet[]
     */
    private function writeDataToMulitpleSheetsAndReturnSheets($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);

        $writer->addRow(['ods--sheet1--11', 'ods--sheet1--12']);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(['ods--sheet2--11', 'ods--sheet2--12', 'ods--sheet2--13']);

        $writer->close();

        return $writer->getSheets();
    }

    /**
     * @param string $expectedName
     * @param string $fileName
     * @param string $message
     * @return void
     */
    private function assertSheetNameEquals($expectedName, $fileName, $message = '')
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains("table:name=\"$expectedName\"", $xmlContents, $message);
    }
}
