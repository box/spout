<?php

namespace Box\Spout\Writer\XLSX;

use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Exception\InvalidSheetNameException;
use Box\Spout\Writer\RowCreationHelper;
use PHPUnit\Framework\TestCase;

/**
 * Class SheetTest
 */
class SheetTest extends TestCase
{
    use TestUsingResource;
    use RowCreationHelper;

    /**
     * @return void
     */
    public function testGetSheetIndex()
    {
        $sheets = $this->writeDataToMultipleSheetsAndReturnSheets('test_get_sheet_index.xlsx');

        $this->assertCount(2, $sheets, '2 sheets should have been created');
        $this->assertEquals(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        $this->assertEquals(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
    }

    /**
     * @return void
     */
    public function testGetSheetName()
    {
        $sheets = $this->writeDataToMultipleSheetsAndReturnSheets('test_get_sheet_name.xlsx');

        $this->assertCount(2, $sheets, '2 sheets should have been created');
        $this->assertEquals('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        $this->assertEquals('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $fileName = 'test_set_name_should_create_sheet_with_custom_name.xlsx';
        $customSheetName = 'CustomName';
        $this->writeDataToSheetWithCustomName($fileName, $customSheetName);

        $this->assertSheetNameEquals($customSheetName, $fileName, "The sheet name should have been changed to '$customSheetName'");
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $this->expectException(InvalidSheetNameException::class);

        $fileName = 'test_set_name_with_non_unique_name.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $customSheetName = 'Sheet name';

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);

        $writer->addNewSheetAndMakeItCurrent();
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);
    }

    /**
     * @return void
     */
    public function testSetSheetVisibilityShouldCreateSheetHidden()
    {
        $fileName = 'test_set_visibility_should_create_sheet_hidden.xlsx';
        $this->writeDataToHiddenSheet($fileName);

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertStringContainsString(' state="hidden"', $xmlContents, 'The sheet visibility should have been changed to "hidden"');
    }

    /**
     * @param string $fileName
     * @param string $sheetName
     * @return Sheet
     */
    private function writeDataToSheetWithCustomName($fileName, $sheetName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheetName);

        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        return $sheet;
    }

    /**
     * @param string $fileName
     * @return Sheet[]
     */
    private function writeDataToMultipleSheetsAndReturnSheets($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $writer->addRow($this->createRowFromValues(['xlsx--sheet1--11', 'xlsx--sheet1--12']));
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow($this->createRowFromValues(['xlsx--sheet2--11', 'xlsx--sheet2--12', 'xlsx--sheet2--13']));

        $writer->close();

        return $writer->getSheets();
    }

    /**
     * @param string $fileName
     * @return void
     */
    private function writeDataToHiddenSheet($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setIsVisible(false);

        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();
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
        $pathToWorkbookFile = $resourcePath . '#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertStringContainsString("<sheet name=\"$expectedName\"", $xmlContents, $message);
    }
}
