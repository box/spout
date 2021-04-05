<?php

namespace Box\Spout\Writer\CSV;

use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterPerfTest
 * Performance tests for CSV Writer
 */
class WriterPerfTest extends TestCase
{
    use TestUsingResource;

    /**
     * 1 million rows (each row containing 3 cells) should be written
     * in less than 30 seconds and the execution should not require
     * more than 1MB of memory
     *
     * @group perf-tests
     *
     * @return void
     */
    public function testPerfWhenWritingOneMillionRowsCSV()
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $numRows = 1000000;
        $expectedMaxExecutionTime = 30; // 30 seconds
        $expectedMaxMemoryPeakUsage = 1 * 1024 * 1024; // 1MB in bytes
        $startTime = time();

        $fileName = 'csv_with_one_million_rows.csv';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; $i++) {
            $writer->addRow(WriterEntityFactory::createRowFromArray(["csv--{$i}1", "csv--{$i}2", "csv--{$i}3"]));
        }

        $writer->close();

        $this->assertEquals($numRows, $this->getNumWrittenRows($resourcePath), "The created CSV should contain $numRows rows");

        $executionTime = time() - $startTime;
        $this->assertTrue($executionTime < $expectedMaxExecutionTime, "Writing 1 million rows should take less than $expectedMaxExecutionTime seconds (took $executionTime seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        $this->assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Writing 1 million rows should require less than ' . ($expectedMaxMemoryPeakUsage / 1024 / 1024) . ' MB of memory (required ' . round($memoryPeakUsage / 1024 / 1024, 2) . ' MB)');
    }

    /**
     * @param string $resourcePath
     * @return int
     */
    private function getNumWrittenRows($resourcePath)
    {
        $lineCountResult = shell_exec("wc -l $resourcePath");

        return (int) $lineCountResult;
    }
}
