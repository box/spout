<?php

namespace Box\Spout\Writer\ODS;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\SpoutException;
use Box\Spout\Reader\Wrapper\XMLReader;
use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Exception\WriterAlreadyOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Box\Spout\Writer\RowCreationHelper;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterTest
 */
class WriterTest extends TestCase
{
    use TestUsingResource;
    use RowCreationHelper;

    /**
     * @return void
     */
    public function testAddRowShouldThrowExceptionIfCannotOpenAFileForWriting()
    {
        $this->expectException(IOException::class);

        $fileName = 'file_that_wont_be_written.ods';
        $this->createUnwritableFolderIfNeeded();
        $filePath = $this->getGeneratedUnwritableResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        @$writer->openToFile($filePath);
    }

    /**
     * @return void
     */
    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->addRow($this->createRowFromValues(['ods--11', 'ods--12']));
    }

    /**
     * @return void
     */
    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->addRows([$this->createRowFromValues(['ods--11', 'ods--12'])]);
    }

    /**
     * @return void
     */
    public function testSetTempFolderShouldThrowExceptionIfCalledAfterOpeningWriter()
    {
        $this->expectException(WriterAlreadyOpenedException::class);

        $fileName = 'file_that_wont_be_written.ods';
        $filePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($filePath);

        $writer->setTempFolder('');
    }

    /**
     * @return void
     */
    public function testSetShouldCreateNewSheetsAutomaticallyShouldThrowExceptionIfCalledAfterOpeningWriter()
    {
        $this->expectException(WriterAlreadyOpenedException::class);

        $fileName = 'file_that_wont_be_written.ods';
        $filePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($filePath);

        $writer->setShouldCreateNewSheetsAutomatically(true);
    }

    /**
     * @return void
     */
    public function testAddRowShouldThrowExceptionIfUnsupportedDataTypePassedIn()
    {
        $this->expectException(InvalidArgumentException::class);

        $fileName = 'test_add_row_should_throw_exception_if_unsupported_data_type_passed_in.ods';
        $dataRows = [
            $this->createRowFromValues([new \stdClass()]),
        ];

        $this->writeToODSFile($dataRows, $fileName);
    }

    /**
     * @return void
     */
    public function testAddRowShouldCleanupAllFilesIfExceptionIsThrown()
    {
        $fileName = 'test_add_row_should_cleanup_all_files_if_exception_thrown.ods';
        $dataRows = [
            $this->createRowFromValues(['wrong']),
            $this->createRowFromValues([new \stdClass()]),
        ];

        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $this->recreateTempFolder();
        $tempFolderPath = $this->getTempFolderPath();

        $writer = WriterEntityFactory::createODSWriter();
        $writer->setTempFolder($tempFolderPath);
        $writer->openToFile($resourcePath);

        try {
            $writer->addRows($dataRows);
            $this->fail('Exception should have been thrown');
        } catch (SpoutException $e) {
            $this->assertFileNotExists($fileName, 'Output file should have been deleted');

            $numFiles = iterator_count(new \FilesystemIterator($tempFolderPath, \FilesystemIterator::SKIP_DOTS));
            $this->assertEquals(0, $numFiles, 'All temp files should have been deleted');
        }
    }

    /**
     * @return void
     */
    public function testAddNewSheetAndMakeItCurrent()
    {
        $fileName = 'test_add_new_sheet_and_make_it_current.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($resourcePath);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->close();

        $sheets = $writer->getSheets();
        $this->assertCount(2, $sheets, 'There should be 2 sheets');
        $this->assertEquals($sheets[1], $writer->getCurrentSheet(), 'The current sheet should be the second one.');
    }

    /**
     * @return void
     */
    public function testSetCurrentSheet()
    {
        $fileName = 'test_set_current_sheet.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($resourcePath);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addNewSheetAndMakeItCurrent();

        $firstSheet = $writer->getSheets()[0];
        $writer->setCurrentSheet($firstSheet);

        $writer->close();

        $this->assertEquals($firstSheet, $writer->getCurrentSheet(), 'The current sheet should be the first one.');
    }

    /**
     * @return void
     */
    public function testCloseShouldNoopWhenWriterIsNotOpened()
    {
        $fileName = 'test_double_close_calls.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->close(); // This call should not cause any error

        $writer->openToFile($resourcePath);
        $writer->close();
        $writer->close(); // This call should not cause any error
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function testAddRowShouldWriteGivenDataToSheet()
    {
        $fileName = 'test_add_row_should_write_given_data_to_sheet.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22', 'ods--23'],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWritten($fileName, $cell->getValue());
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldWriteGivenDataToTwoSheets()
    {
        $fileName = 'test_add_row_should_write_given_data_to_two_sheets.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22', 'ods--23'],
        ]);

        $numSheets = 2;
        $this->writeToMultipleSheetsInODSFile($dataRows, $numSheets, $fileName);

        for ($i = 1; $i <= $numSheets; $i++) {
            foreach ($dataRows as $dataRow) {
                foreach ($dataRow->getCells() as $cell) {
                    $this->assertValueWasWritten($fileName, $cell->getValue());
                }
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldSupportAssociativeArrays()
    {
        $fileName = 'test_add_row_should_support_associative_arrays.ods';
        $dataRows = $this->createRowsFromValues([
            ['foo' => 'ods--11', 'bar' => 'ods--12'],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWritten($fileName, $cell->getValue());
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldSupportMultipleTypesOfData()
    {
        $fileName = 'test_add_row_should_support_multiple_types_of_data.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--11', true, '', 0, 10.2, null],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        $this->assertValueWasWritten($fileName, 'ods--11');
        $this->assertValueWasWrittenToSheet($fileName, 1, 1); // true is converted to 1
        $this->assertValueWasWrittenToSheet($fileName, 1, 0);
        $this->assertValueWasWrittenToSheet($fileName, 1, 10.2);
    }

    /**
     * @return void
     */
    public function testAddRowShouldSupportFloatValuesInDifferentLocale()
    {
        $previousLocale = \setlocale(LC_ALL, 0);

        try {
            // Pick a supported locale whose decimal point is a comma.
            // Installed locales differ from one system to another, so we can't pick
            // a given locale.
            $supportedLocales = explode("\n", shell_exec('locale -a'));
            foreach ($supportedLocales as $supportedLocale) {
                \setlocale(LC_ALL, $supportedLocale);
                if (\localeconv()['decimal_point'] === ',') {
                    break;
                }
            }
            $this->assertEquals(',', \localeconv()['decimal_point']);

            $fileName = 'test_add_row_should_support_float_values_in_different_locale.xlsx';
            $dataRows = $this->createRowsFromValues([
                [1234.5],
            ]);

            $this->writeToODSFile($dataRows, $fileName);

            $this->assertValueWasNotWrittenToSheet($fileName, 1, "1234,5");
            $this->assertValueWasWrittenToSheet($fileName, 1, "1234.5");
        } finally {
            // reset locale
            \setlocale(LC_ALL, $previousLocale);
        }
    }

    /**
     * @return array
     */
    public function dataProviderForTestAddRowShouldUseNumberColumnsRepeatedForRepeatedValues()
    {
        return [
            [['ods--11', 'ods--11', 'ods--11'], 1, 3],
            [['', ''], 1, 2],
            [[true, true, true, true], 1, 4],
            [[1.1, 1.1], 1, 2],
            [['foo', 'bar'], 2, 0],
        ];
    }

    /**
     * @dataProvider dataProviderForTestAddRowShouldUseNumberColumnsRepeatedForRepeatedValues
     *
     * @param array $dataRow
     * @param int $expectedNumTableCells
     * @param int $expectedNumColumnsRepeated
     * @return void
     */
    public function testAddRowShouldUseNumberColumnsRepeatedForRepeatedValues($dataRow, $expectedNumTableCells, $expectedNumColumnsRepeated)
    {
        $fileName = 'test_add_row_should_use_number_columns_repeated.ods';
        $this->writeToODSFile($this->createRowsFromValues([$dataRow]), $fileName);

        $sheetXmlNode = $this->getSheetXmlNode($fileName, 1);
        $tableCellNodes = $sheetXmlNode->getElementsByTagName('table-cell');

        $this->assertEquals($expectedNumTableCells, $tableCellNodes->length);

        if ($expectedNumTableCells === 1) {
            $tableCellNode = $tableCellNodes->item(0);
            $numColumnsRepeated = (int) ($tableCellNode->getAttribute('table:number-columns-repeated'));
            $this->assertEquals($expectedNumColumnsRepeated, $numColumnsRepeated);
        } else {
            foreach ($tableCellNodes as $tableCellNode) {
                $this->assertFalse($tableCellNode->hasAttribute('table:number-columns-repeated'));
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldSupportCellInError()
    {
        $fileName = 'test_add_row_should_support_cell_in_error.ods';

        $cell = WriterEntityFactory::createCell('#DIV/0');
        $cell->setType(Cell::TYPE_ERROR);

        $row = WriterEntityFactory::createRow([$cell]);

        $this->writeToODSFile([$row], $fileName);

        $this->assertValueWasWritten($fileName, 'calcext:value-type="error"');
        $this->assertValueWasWritten($fileName, '<text:p>#DIV/0</text:p>');
    }

    /**
     * @return void
     */
    public function testAddRowShouldWriteGivenDataToTheCorrectSheet()
    {
        $fileName = 'test_add_row_should_write_given_data_to_the_correct_sheet.ods';
        $dataRowsSheet1 = $this->createRowsFromValues([
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
        ]);
        $dataRowsSheet2 = $this->createRowsFromValues([
            ['ods--sheet2--11', 'ods--sheet2--12'],
            ['ods--sheet2--21', 'ods--sheet2--22', 'ods--sheet2--23'],
        ]);
        $dataRowsSheet1Again = $this->createRowsFromValues([
            ['ods--sheet1--31', 'ods--sheet1--32'],
            ['ods--sheet1--41', 'ods--sheet1--42', 'ods--sheet1--43'],
        ]);

        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($resourcePath);

        $writer->addRows($dataRowsSheet1);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRows($dataRowsSheet2);

        $firstSheet = $writer->getSheets()[0];
        $writer->setCurrentSheet($firstSheet);

        $writer->addRows($dataRowsSheet1Again);

        $writer->close();

        foreach ($dataRowsSheet1 as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWrittenToSheet($fileName, 1, $cell->getValue(), 'Data should have been written in Sheet 1');
            }
        }
        foreach ($dataRowsSheet2 as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWrittenToSheet($fileName, 2, $cell->getValue(), 'Data should have been written in Sheet 2');
            }
        }
        foreach ($dataRowsSheet1Again as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWrittenToSheet($fileName, 1, $cell->getValue(), 'Data should have been written in Sheet 1');
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldAutomaticallyCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOn()
    {
        $fileName = 'test_add_row_should_automatically_create_new_sheets_if_max_rows_reached_and_option_turned_on.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
            ['ods--sheet2--11', 'ods--sheet2--12'], // this should be written in a new sheet
        ]);

        // set the maxRowsPerSheet limit to 2
        \ReflectionHelper::setStaticValue('\Box\Spout\Writer\ODS\Manager\WorkbookManager', 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToODSFile($dataRows, $fileName, $shouldCreateSheetsAutomatically = true);
        $this->assertCount(2, $writer->getSheets(), '2 sheets should have been created.');

        $this->assertValueWasNotWrittenToSheet($fileName, 1, 'ods--sheet2--11');
        $this->assertValueWasWrittenToSheet($fileName, 2, 'ods--sheet2--11');

        \ReflectionHelper::reset();
    }

    /**
     * @return void
     */
    public function testAddRowShouldNotCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOff()
    {
        $fileName = 'test_add_row_should_not_create_new_sheets_if_max_rows_reached_and_option_turned_off.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
            ['ods--sheet1--31', 'ods--sheet1--32'], // this should NOT be written in a new sheet
        ]);

        // set the maxRowsPerSheet limit to 2
        \ReflectionHelper::setStaticValue('\Box\Spout\Writer\ODS\Manager\WorkbookManager', 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToODSFile($dataRows, $fileName, $shouldCreateSheetsAutomatically = false);
        $this->assertCount(1, $writer->getSheets(), 'Only 1 sheet should have been created.');

        $this->assertValueWasNotWrittenToSheet($fileName, 1, 'ods--sheet1--31');

        \ReflectionHelper::reset();
    }

    /**
     * @return void
     */
    public function testAddRowShouldEscapeHtmlSpecialCharacters()
    {
        $fileName = 'test_add_row_should_escape_html_special_characters.ods';
        $dataRows = $this->createRowsFromValues([
            ['I\'m in "great" mood', 'This <must> be escaped & tested'],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        $this->assertValueWasWritten($fileName, 'I&#039;m in &quot;great&quot; mood', 'Quotes should be escaped');
        $this->assertValueWasWritten($fileName, 'This &lt;must&gt; be escaped &amp; tested', '<, > and & should be escaped');
    }

    /**
     * @return void
     */
    public function testAddRowShouldKeepNewLines()
    {
        $fileName = 'test_add_row_should_keep_new_lines.ods';
        $dataRow = ["I have\na dream"];

        $this->writeToODSFile($this->createRowsFromValues([$dataRow]), $fileName);

        $this->assertValueWasWrittenToSheet($fileName, 1, 'I have');
        $this->assertValueWasWrittenToSheet($fileName, 1, 'a dream');
    }

    /**
     * @return void
     */
    public function testGeneratedFileShouldHaveTheCorrectMimeType()
    {
        $fileName = 'test_mime_type.ods';
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $dataRow = ['foo'];

        $this->writeToODSFile($this->createRowsFromValues([$dataRow]), $fileName);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $finfo->file($resourcePath));
    }

    /**
     * @param Row[] $allRows
     * @param string $fileName
     * @param bool $shouldCreateSheetsAutomatically
     * @return Writer
     */
    private function writeToODSFile($allRows, $fileName, $shouldCreateSheetsAutomatically = true)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->setShouldCreateNewSheetsAutomatically($shouldCreateSheetsAutomatically);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[] $allRows
     * @param int $numSheets
     * @param string $fileName
     * @param bool $shouldCreateSheetsAutomatically
     * @return Writer
     */
    private function writeToMultipleSheetsInODSFile($allRows, $numSheets, $fileName, $shouldCreateSheetsAutomatically = true)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->setShouldCreateNewSheetsAutomatically($shouldCreateSheetsAutomatically);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);

        for ($i = 1; $i < $numSheets; $i++) {
            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRows($allRows);
        }

        $writer->close();

        return $writer;
    }

    /**
     * @param string $fileName
     * @param string $value
     * @param string $message
     * @return void
     */
    private function assertValueWasWritten($fileName, $value, $message = '')
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToContentFile = $resourcePath . '#content.xml';
        $xmlContents = file_get_contents('zip://' . $pathToContentFile);

        $this->assertStringContainsString($value, $xmlContents, $message);
    }

    /**
     * @param string $fileName
     * @param int $sheetIndex
     * @param mixed $value
     * @param string $message
     * @return void
     */
    private function assertValueWasWrittenToSheet($fileName, $sheetIndex, $value, $message = '')
    {
        $sheetXmlAsString = $this->getSheetXmlNodeAsString($fileName, $sheetIndex);
        $valueAsXmlString = "<text:p>$value</text:p>";

        $this->assertStringContainsString($valueAsXmlString, $sheetXmlAsString, $message);
    }

    /**
     * @param string $fileName
     * @param int $sheetIndex
     * @param mixed $value
     * @param string $message
     * @return void
     */
    private function assertValueWasNotWrittenToSheet($fileName, $sheetIndex, $value, $message = '')
    {
        $sheetXmlAsString = $this->getSheetXmlNodeAsString($fileName, $sheetIndex);
        $valueAsXmlString = "<text:p>$value</text:p>";

        $this->assertStringNotContainsString($valueAsXmlString, $sheetXmlAsString, $message);
    }

    /**
     * @param string $fileName
     * @param int $sheetIndex
     * @return \DOMNode
     */
    private function getSheetXmlNode($fileName, $sheetIndex)
    {
        $xmlReader = $this->moveReaderToCorrectTableNode($fileName, $sheetIndex);

        return $xmlReader->expand();
    }

    /**
     * @param string $fileName
     * @param int $sheetIndex
     * @return string
     */
    private function getSheetXmlNodeAsString($fileName, $sheetIndex)
    {
        $xmlReader = $this->moveReaderToCorrectTableNode($fileName, $sheetIndex);

        return $xmlReader->readOuterXml();
    }

    /**
     * @param string $fileName
     * @param int $sheetIndex
     * @return XMLReader
     */
    private function moveReaderToCorrectTableNode($fileName, $sheetIndex)
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');
        $xmlReader->readUntilNodeFound('table:table');

        for ($i = 1; $i < $sheetIndex; $i++) {
            $xmlReader->readUntilNodeFound('table:table');
        }

        return $xmlReader;
    }
}
