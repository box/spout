<?php

namespace Box\Spout\Writer\ODS;

use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Exception\InvalidSheetNameException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
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
        $sheets = $this->writeDataToMulitpleSheetsAndReturnSheets('test_get_sheet_index.ods');

        $this->assertCount(2, $sheets, '2 sheets should have been created');
        $this->assertEquals(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        $this->assertEquals(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
    }

    /**
     * @return void
     */
    public function testGetSheetName()
    {
        $sheets = $this->writeDataToMulitpleSheetsAndReturnSheets('test_get_sheet_name.ods');

        $this->assertCount(2, $sheets, '2 sheets should have been created');
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
     * @return void
     */
    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $this->expectException(InvalidSheetNameException::class);

        $fileName = 'test_set_name_with_non_unique_name.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
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
        $fileName = 'test_set_visibility_should_create_sheet_hidden.ods';
        $this->writeDataToHiddenSheet($fileName);

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToContentFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToContentFile);

        $this->assertContains(' table:display="false"', $xmlContents, 'The sheet visibility should have been changed to "hidden"');
    }

    public function testThrowsIfWorkbookIsNotInitialized()
    {
        $this->expectException(WriterNotOpenedException::class);
        $writer = WriterEntityFactory::createODSWriter();

        $writer->addRow($this->createRowFromValues([]));
    }

    public function testThrowsWhenTryingToSetDefaultsBeforeWorkbookLoaded()
    {
        $this->expectException(WriterNotOpenedException::class);
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setDefaultColumnWidth(10.0);
    }

    public function testWritesDefaultCellSizesIfSet()
    {
        $fileName = 'test_writes_default_cell_sizes_if_set.ods';
        $writer = $this->writerForFile($fileName);

        $writer->setDefaultColumnWidth(100.0);
        $writer->setDefaultRowHeight(20.0);
        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12']));
        $writer->close();

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains(' style:column-width="100pt"', $xmlContents, 'No default col width found in sheet');
        $this->assertContains(' style:row-height="20pt"', $xmlContents, 'No default row height found in sheet');
        $this->assertContains(' style:use-optimal-row-height="false', $xmlContents, 'No optimal row height override found in sheet');
    }

    public function testWritesColumnWidths()
    {
        $fileName = 'test_column_widths.ods';
        $writer = $this->writerForFile($fileName);

        $writer->setColumnWidth(100.0, 1);
        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12']));
        $writer->close();

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains('<style:style style:family="table-column" style:name="co0">', $xmlContents, 'No matching custom col style definition found in sheet');
        $this->assertContains('<style:table-column-properties fo:break-before="auto" style:use-optimal-column-width="false" style:column-width="100pt"/>', $xmlContents, 'No matching table-column-properties found in sheet');
        $this->assertContains('table:style-name="co0"', $xmlContents, 'No matching table:style-name found in sheet');
        $this->assertContains('table:number-columns-repeated="1"', $xmlContents, 'No matching table:number-columns-repeated count found in sheet');
    }

    public function testWritesMultipleColumnWidths()
    {
        $fileName = 'test_multiple_column_widths.ods';
        $writer = $this->writerForFile($fileName);

        $writer->setColumnWidth(100.0, 1, 2, 3);
        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12', 'ods--13']));
        $writer->close();

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains('<style:style style:family="table-column" style:name="co0">', $xmlContents, 'No matching custom col style definition found in sheet');
        $this->assertContains('<style:table-column-properties fo:break-before="auto" style:use-optimal-column-width="false" style:column-width="100pt"/>', $xmlContents, 'No matching table-column-properties found in sheet');
        $this->assertContains('table:style-name="co0"', $xmlContents, 'No matching table:style-name found in sheet');
        $this->assertContains('table:number-columns-repeated="3"', $xmlContents, 'No matching table:number-columns-repeated count found in sheet');
    }

    public function testWritesMultipleColumnWidthsInRanges()
    {
        $fileName = 'test_multiple_column_widths_in_ranges.ods';
        $writer = $this->writerForFile($fileName);

        $writer->setColumnWidth(50.0, 1, 3, 4, 6);
        $writer->setColumnWidth(100.0, 2, 5);
        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12', 'ods--13', 'ods--14', 'ods--15', 'ods--16']));
        $writer->close();

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains('<style:style style:family="table-column" style:name="co0">', $xmlContents, 'No matching custom col style 0 definition found in sheet');
        $this->assertContains('<style:style style:family="table-column" style:name="co1">', $xmlContents, 'No matching custom col style 1 definition found in sheet');
        $this->assertContains('<style:style style:family="table-column" style:name="co2">', $xmlContents, 'No matching custom col style 2 definition found in sheet');
        $this->assertContains('<style:style style:family="table-column" style:name="co3">', $xmlContents, 'No matching custom col style 3 definition found in sheet');
        $this->assertContains('<style:style style:family="table-column" style:name="co4">', $xmlContents, 'No matching custom col style 4 definition found in sheet');
        $this->assertContains('<style:table-column-properties fo:break-before="auto" style:use-optimal-column-width="false" style:column-width="100pt"/>', $xmlContents, 'No matching table-column-properties found in sheet');
        $this->assertContains('<style:table-column-properties fo:break-before="auto" style:use-optimal-column-width="false" style:column-width="50pt"/>', $xmlContents, 'No matching table-column-properties found in sheet');
        $this->assertContains('<table:table-column table:default-cell-style-name=\'Default\' table:style-name="co0" table:number-columns-repeated="1"/><table:table-column table:default-cell-style-name=\'Default\' table:style-name="co1" table:number-columns-repeated="1"/><table:table-column table:default-cell-style-name=\'Default\' table:style-name="co2" table:number-columns-repeated="2"/><table:table-column table:default-cell-style-name=\'Default\' table:style-name="co3" table:number-columns-repeated="1"/><table:table-column table:default-cell-style-name=\'Default\' table:style-name="co4" table:number-columns-repeated="1"/>', $xmlContents, 'No matching table:number-columns-repeated count found in sheet');
    }

    public function testCanTakeColumnWidthsAsRange()
    {
        $fileName = 'test_column_widths_as_ranges.ods';
        $writer = $this->writerForFile($fileName);

        $writer->setColumnWidthForRange(150.0, 1, 3);
        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12', 'ods--13']));
        $writer->close();

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains('<style:style style:family="table-column" style:name="co0">', $xmlContents, 'No matching custom col style 0 definition found in sheet');
        $this->assertContains('style:column-width="150pt"/>', $xmlContents, 'No matching table-column-properties found in sheet');
        $this->assertContains('table:style-name="co0"', $xmlContents, 'No matching table:style-name found in sheet');
        $this->assertContains('table:number-columns-repeated="3"', $xmlContents, 'No matching table:number-columns-repeated count found in sheet');
    }

    private function writerForFile($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($resourcePath);

        return $writer;
    }

    /**
     * @param string $fileName
     * @param string $sheetName
     * @return void
     */
    private function writeDataAndReturnSheetWithCustomName($fileName, $sheetName)
    {
        $writer = $this->writerForFile($fileName);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheetName);

        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12']));
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

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($resourcePath);

        $writer->addRow($this->createRowFromValues(['ods--sheet1--11', 'ods--sheet1--12']));
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow($this->createRowFromValues(['ods--sheet2--11', 'ods--sheet2--12', 'ods--sheet2--13']));

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

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setIsVisible(false);

        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12']));
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
        $pathToWorkbookFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToWorkbookFile);

        $this->assertContains("table:name=\"$expectedName\"", $xmlContents, $message);
    }
}
