<?php

namespace Box\Spout\Writer\CSV;

use Box\Spout\TestUsingResource;
use Box\Spout\Common\Type;
use Box\Spout\Common\Helper\EncodingHelper;
use Box\Spout\Writer\WriterFactory;

/**
 * Class WriterTest
 *
 * @package Box\Spout\Writer\CSV
 */
class WriterTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     */
    public function testWriteShouldThrowExceptionIfCannotOpenFileForWriting()
    {
        $fileName = 'file_that_wont_be_written.csv';
        $this->createUnwritableFolderIfNeeded($fileName);
        $filePath = $this->getGeneratedUnwritableResourcePath($fileName);

        $writer = WriterFactory::create(Type::CSV);
        @$writer->openToFile($filePath);
        $writer->addRow(['csv--11', 'csv--12']);
        $writer->close();
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testWriteShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::CSV);
        $writer->addRow(['csv--11', 'csv--12']);
        $writer->close();
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testWriteShouldThrowExceptionIfCallAddRowsBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::CSV);
        $writer->addRows([['csv--11', 'csv--12']]);
        $writer->close();
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\InvalidArgumentException
     */
    public function testAddRowsShouldThrowExceptionIfRowsAreNotArrayOfArrays()
    {
        $writer = WriterFactory::create(Type::CSV);
        $writer->addRows(['csv--11', 'csv--12']);
        $writer->close();
    }

    /**
     * @return void
     */
    public function testWriteShouldAddUtf8Bom()
    {
        $allRows = [
            ['csv--11', 'csv--12'],
        ];
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_utf8_bom.csv');

        $this->assertContains(EncodingHelper::BOM_UTF8, $writtenContent, 'The CSV file should contain a UTF-8 BOM');
    }

    /**
     * @return void
     */
    public function testWriteShouldSupportNullValues()
    {
        $allRows = [
            ['csv--11', null, 'csv--13'],
        ];
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_null_values.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        $this->assertEquals('csv--11,,csv--13', $writtenContent, 'The null values should be replaced by empty values');
    }

    /**
     * @return void
     */
    public function testWriteShouldSkipEmptyRows()
    {
        $allRows = [
            ['csv--11', 'csv--12'],
            [],
            ['csv--31', 'csv--32'],
        ];
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_empty_rows.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        $this->assertEquals("csv--11,csv--12\ncsv--31,csv--32", $writtenContent, 'Empty rows should be skipped');
    }

    /**
     * @return void
     */
    public function testWriteShouldSupportCustomFieldDelimiter()
    {
        $allRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
        ];
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_pipe_delimiters.csv', '|');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        $this->assertEquals("csv--11|csv--12|csv--13\ncsv--21|csv--22|csv--23", $writtenContent, 'The fields should be delimited with |');
    }

    /**
     * @return void
     */
    public function testWriteShouldSupportCustomFieldEnclosure()
    {
        $allRows = [
            ['This is, a comma', 'csv--12', 'csv--13'],
        ];
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_pound_enclosures.csv', ',', '#');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        $this->assertEquals('#This is, a comma#,csv--12,csv--13', $writtenContent, 'The fields should be enclosed with #');
    }

    /**
     * @param array $allRows
     * @param string $fileName
     * @param string $fieldDelimiter
     * @param string $fieldEnclosure
     * @return null|string
     */
    private function writeToCsvFileAndReturnWrittenContent($allRows, $fileName, $fieldDelimiter = ',', $fieldEnclosure = '"')
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::CSV);
        $writer->setFieldDelimiter($fieldDelimiter);
        $writer->setFieldEnclosure($fieldEnclosure);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return file_get_contents($resourcePath);
    }

    /**
     * @param string $writtenContent
     * @return string
     */
    private function trimWrittenContent($writtenContent)
    {
        // remove line feeds and UTF-8 BOM
        return trim($writtenContent, PHP_EOL . EncodingHelper::BOM_UTF8);
    }
}
