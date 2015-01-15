<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Type;
use Box\Spout\TestUsingResource;

/**
 * Class CSVTest
 *
 * @package Box\Spout\Reader
 */
class CSVTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @return void
     */
    public function testReadShouldThrowExceptionIfFileDoesNotExist()
    {
        $this->getAllRowsForFile('/path/to/fake/file.csv');
    }

    /**
     * @return void
     */
    public function testReadStandardCSV()
    {
        $allRows = $this->getAllRowsForFile('csv_standard.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldNotStopAtCommaIfEnclosed()
    {
        $allRows = $this->getAllRowsForFile('csv_with_comma_enclosed.csv');
        $this->assertEquals('This is, a comma', $allRows[0][0]);
    }

    /**
     * @return void
     */
    public function testReadShouldKeepEmptyCells()
    {
        $allRows = $this->getAllRowsForFile('csv_with_empty_cells.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', '', 'csv--23'],
            ['csv--31', 'csv--32', ''],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSkipEmptyLines()
    {
        $allRows = $this->getAllRowsForFile('csv_with_empty_line.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldHaveTheRightNumberOfCells()
    {
        $allRows = $this->getAllRowsForFile('csv_with_different_cells_number.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22'],
            ['csv--31'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportCustomFieldDelimiter()
    {
        $allRows = $this->getAllRowsForFile('csv_delimited_with_pipes.csv', '|');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportCustomFieldEnclosure()
    {
        $allRows = $this->getAllRowsForFile('csv_text_enclosed_with_pound.csv', ',', '#');
        $this->assertEquals('This is, a comma', $allRows[0][0]);
    }

    /**
     * @return void
     */
    public function testReadShouldSkipUtf8Bom()
    {
        $allRows = $this->getAllRowsForFile('csv_with_utf8_bom.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @param string $fileName
     * @param string|void $fieldDelimiter
     * @param string|void $fieldEnclosure
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile($fileName, $fieldDelimiter = ",", $fieldEnclosure = '"')
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        $reader = ReaderFactory::create(Type::CSV);
        $reader->setFieldDelimiter($fieldDelimiter);
        $reader->setFieldEnclosure($fieldEnclosure);

        $reader->open($resourcePath);

        while ($reader->hasNextRow()) {
            $allRows[] = $reader->nextRow();
        }

        $reader->close();

        return $allRows;
    }
}
