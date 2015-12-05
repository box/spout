<?php

namespace Box\Spout\Writer\ODS;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Wrapper\XMLReader;
use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Helper\ZipHelper;
use Box\Spout\Writer\WriterFactory;

/**
 * Class WriterTest
 *
 * @package Box\Spout\Writer\ODS
 */
class WriterTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     */
    public function testAddRowShouldThrowExceptionIfCannotOpenAFileForWriting()
    {
        $fileName = 'file_that_wont_be_written.ods';
        $this->createUnwritableFolderIfNeeded($fileName);
        $filePath = $this->getGeneratedUnwritableResourcePath($fileName);

        $writer = WriterFactory::create(Type::ODS);
        @$writer->openToFile($filePath);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::ODS);
        $writer->addRow(['ods--11', 'ods--12']);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::ODS);
        $writer->addRows([['ods--11', 'ods--12']]);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterAlreadyOpenedException
     */
    public function testSetTempFolderShouldThrowExceptionIfCalledAfterOpeningWriter()
    {
        $fileName = 'file_that_wont_be_written.ods';
        $filePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($filePath);

        $writer->setTempFolder('');
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterAlreadyOpenedException
     */
    public function testsetShouldCreateNewSheetsAutomaticallyShouldThrowExceptionIfCalledAfterOpeningWriter()
    {
        $fileName = 'file_that_wont_be_written.ods';
        $filePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($filePath);

        $writer->setShouldCreateNewSheetsAutomatically(true);
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\InvalidArgumentException
     */
    public function testAddRowShouldThrowExceptionIfUnsupportedDataTypePassedIn()
    {
        $fileName = 'test_add_row_should_throw_exception_if_unsupported_data_type_passed_in.ods';
        $dataRows = [
            [new \stdClass()],
        ];

        $this->writeToODSFile($dataRows, $fileName);
    }

    /**
     * @return void
     */
    public function testAddNewSheetAndMakeItCurrent()
    {
        $fileName = 'test_add_new_sheet_and_make_it_current.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->close();

        $sheets = $writer->getSheets();
        $this->assertEquals(2, count($sheets), 'There should be 2 sheets');
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

        $writer = WriterFactory::create(Type::ODS);
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
    public function testAddRowShouldWriteGivenDataToSheet()
    {
        $fileName = 'test_add_row_should_write_given_data_to_sheet.ods';
        $dataRows = [
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22', 'ods--23'],
        ];

        $this->writeToODSFile($dataRows, $fileName);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow as $cellValue) {
                $this->assertValueWasWritten($fileName, $cellValue);
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldWriteGivenDataToTwoSheets()
    {
        $fileName = 'test_add_row_should_write_given_data_to_two_sheets.ods';
        $dataRows = [
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22', 'ods--23'],
        ];

        $numSheets = 2;
        $this->writeToMultipleSheetsInODSFile($dataRows, $numSheets, $fileName);

        for ($i = 1; $i <= $numSheets; $i++) {
            foreach ($dataRows as $dataRow) {
                foreach ($dataRow as $cellValue) {
                    $this->assertValueWasWritten($fileName, $cellValue);
                }
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldSupportMultipleTypesOfData()
    {
        $fileName = 'test_add_row_should_support_multiple_types_of_data.ods';
        $dataRows = [
            ['ods--11', true, '', 0, 10.2, null],
        ];

        $this->writeToODSFile($dataRows, $fileName);

        $this->assertValueWasWritten($fileName, 'ods--11');
        $this->assertValueWasWrittenToSheet($fileName, 1, 1); // true is converted to 1
        $this->assertValueWasWrittenToSheet($fileName, 1, 0);
        $this->assertValueWasWrittenToSheet($fileName, 1, 10.2);
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
        $this->writeToODSFile([$dataRow], $fileName);

        $sheetXmlNode = $this->getSheetXmlNode($fileName, 1);
        $tableCellNodes = $sheetXmlNode->getElementsByTagName('table-cell');

        $this->assertEquals($expectedNumTableCells, $tableCellNodes->length);

        if ($expectedNumTableCells === 1) {
            $tableCellNode = $tableCellNodes->item(0);
            $numColumnsRepeated = intval($tableCellNode->getAttribute('table:number-columns-repeated'));
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
    public function testAddRowShouldWriteGivenDataToTheCorrectSheet()
    {
        $fileName = 'test_add_row_should_write_given_data_to_the_correct_sheet.ods';
        $dataRowsSheet1 = [
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
        ];
        $dataRowsSheet2 = [
            ['ods--sheet2--11', 'ods--sheet2--12'],
            ['ods--sheet2--21', 'ods--sheet2--22', 'ods--sheet2--23'],
        ];
        $dataRowsSheet1Again = [
            ['ods--sheet1--31', 'ods--sheet1--32'],
            ['ods--sheet1--41', 'ods--sheet1--42', 'ods--sheet1--43'],
        ];

        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);

        $writer->addRows($dataRowsSheet1);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRows($dataRowsSheet2);

        $firstSheet = $writer->getSheets()[0];
        $writer->setCurrentSheet($firstSheet);

        $writer->addRows($dataRowsSheet1Again);

        $writer->close();

        foreach ($dataRowsSheet1 as $dataRow) {
            foreach ($dataRow as $cellValue) {
                $this->assertValueWasWrittenToSheet($fileName, 1, $cellValue, 'Data should have been written in Sheet 1');
            }
        }
        foreach ($dataRowsSheet2 as $dataRow) {
            foreach ($dataRow as $cellValue) {
                $this->assertValueWasWrittenToSheet($fileName, 2, $cellValue, 'Data should have been written in Sheet 2');
            }
        }
        foreach ($dataRowsSheet1Again as $dataRow) {
            foreach ($dataRow as $cellValue) {
                $this->assertValueWasWrittenToSheet($fileName, 1, $cellValue, 'Data should have been written in Sheet 1');
            }
        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldAutomaticallyCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOn()
    {
        $fileName = 'test_add_row_should_automatically_create_new_sheets_if_max_rows_reached_and_option_turned_on.ods';
        $dataRows = [
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
            ['ods--sheet2--11', 'ods--sheet2--12'], // this should be written in a new sheet
        ];

        // set the maxRowsPerSheet limit to 2
        \ReflectionHelper::setStaticValue('\Box\Spout\Writer\ODS\Internal\Workbook', 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToODSFile($dataRows, $fileName, $shouldCreateSheetsAutomatically = true);
        $this->assertEquals(2, count($writer->getSheets()), '2 sheets should have been created.');

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
        $dataRows = [
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
            ['ods--sheet1--31', 'ods--sheet1--32'], // this should NOT be written in a new sheet
        ];

        // set the maxRowsPerSheet limit to 2
        \ReflectionHelper::setStaticValue('\Box\Spout\Writer\ODS\Internal\Workbook', 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToODSFile($dataRows, $fileName, $shouldCreateSheetsAutomatically = false);
        $this->assertEquals(1, count($writer->getSheets()), 'Only 1 sheet should have been created.');

        $this->assertValueWasNotWrittenToSheet($fileName, 1, 'ods--sheet1--31');

        \ReflectionHelper::reset();
    }

    /**
     * @return void
     */
    public function testAddRowShouldEscapeHtmlSpecialCharacters()
    {
        $fileName = 'test_add_row_should_escape_html_special_characters.ods';
        $dataRows = [
            ['I\'m in "great" mood', 'This <must> be escaped & tested'],
        ];

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

        $this->writeToODSFile([$dataRow], $fileName);

        $this->assertValueWasWrittenToSheet($fileName, 1, 'I have');
        $this->assertValueWasWrittenToSheet($fileName, 1, 'a dream');
    }

    /**
     * @return void
     */
    public function testGeneratedFileShouldHaveTheCorrectMimeType()
    {
        // Only PHP7+ gives the correct mime type since it requires adding
        // uncompressed files to the final archive (which support was added in PHP7)
        if (!ZipHelper::canChooseCompressionMethod()) {
            $this->markTestSkipped(
                'The PHP version used does not support setting the compression method of archived files,
                resulting in the mime type to be detected incorrectly.'
            );
        }

        $fileName = 'test_mime_type.ods';
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $dataRow = ['foo'];

        $this->writeToODSFile([$dataRow], $fileName);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $finfo->file($resourcePath));
    }

    /**
     * @param array $allRows
     * @param string $fileName
     * @param bool $shouldCreateSheetsAutomatically
     * @return Writer
     */
    private function writeToODSFile($allRows, $fileName, $shouldCreateSheetsAutomatically = true)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
        $writer->setShouldCreateNewSheetsAutomatically($shouldCreateSheetsAutomatically);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param array $allRows
     * @param int $numSheets
     * @param string $fileName
     * @param bool $shouldCreateSheetsAutomatically
     * @return Writer
     */
    private function writeToMultipleSheetsInODSFile($allRows, $numSheets, $fileName, $shouldCreateSheetsAutomatically = true)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);
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

        $this->assertContains($value, $xmlContents, $message);
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

        $this->assertContains($valueAsXmlString, $sheetXmlAsString, $message);
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

        $this->assertNotContains($valueAsXmlString, $sheetXmlAsString, $message);
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
        $pathToSheetFile = $resourcePath . '#content.xml';

        $xmlReader = new XMLReader();
        $xmlReader->open('zip://' . $pathToSheetFile);
        $xmlReader->readUntilNodeFound('table:table');

        for ($i = 1; $i < $sheetIndex; $i++) {
            $xmlReader->readUntilNodeFound('table:table');
        }

        return $xmlReader;
    }
}
