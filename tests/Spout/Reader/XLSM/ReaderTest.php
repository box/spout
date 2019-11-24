<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class ReaderTest
 */
class ReaderTest extends TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testReaderFactoryShouldReturnXLSXReader()
    {
        $resourcePath = $this->getResourcePath('simple-xlsm-sample.xlsm');
        $reader = ReaderEntityFactory::createReaderFromFile($resourcePath);

        $this->assertInstanceOf(Box\Spout\Reader\ReaderInterface::class, $reader);
    }

    /**
     * @return void
     */
    public function testReadXLSMSpreadsheetShouldReturnData()
    {
        $this->assertNotEmpty($this->getAllRowsForFile('simple-xlsm-sample.xlsm'));
    }

    /**
     * @return void
     */
    public function testSpreadsheetRowsShouldMatch()
    {
        $expectedRows = [
            ['a1', 'b1', 'c1'],
            ['a2', 'b2', 'c2'],
            ['a3', 'b3', 'c3'],
        ];

        $this->assertEquals($expectedRows, $this->getAllRowsForFile('simple-xlsm-sample.xlsm'));
    }

    /**
     * @param string $fileName
     * @param bool $shouldFormatDates
     * @param bool $shouldPreserveEmptyRows
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile($fileName, $shouldFormatDates = false, $shouldPreserveEmptyRows = false)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->setShouldFormatDates($shouldFormatDates);
        $reader->setShouldPreserveEmptyRows($shouldPreserveEmptyRows);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        return $allRows;
    }
}
