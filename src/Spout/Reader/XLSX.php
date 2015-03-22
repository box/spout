<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Exception\BadUsageException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\EndOfWorksheetsReachedException;
use Box\Spout\Reader\Exception\NoWorksheetsFoundException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Reader\Helper\XLSX\CellHelper;
use Box\Spout\Reader\Helper\XLSX\SharedStringsHelper;
use Box\Spout\Reader\Helper\XLSX\WorksheetHelper;

/**
 * Class XLSX
 * This class provides support to read data from a XLSX file
 *
 * @package Box\Spout\Reader
 */
class XLSX extends AbstractReader
{
    /** @var string Real path of the file to read */
    protected $filePath;

    /** @var string Temporary folder where the temporary files will be created */
    protected $tempFolder;

    /** @var \ZipArchive */
    protected $zip;

    /** @var Helper\XLSX\SharedStringsHelper Helper to work with shared strings */
    protected $sharedStringsHelper;

    /** @var Helper\XLSX\WorksheetHelper Helper to work with worksheets */
    protected $worksheetHelper;

    /** @var Internal\XLSX\Worksheet[] The list of worksheets present in the file */
    protected $worksheets;

    /** @var Internal\XLSX\Worksheet The worksheet being read */
    protected $currentWorksheet;

    /** @var \XMLReader The XMLReader object that will help read sheets XML data */
    protected $xmlReader;

    /** @var int The number of columns the worksheet has (0 meaning undefined) */
    protected $numberOfColumns = 0;

    /**
     * @param string $tempFolder Temporary folder where the temporary files will be created
     * @return XLSX
     */
    public function setTempFolder($tempFolder)
    {
        $this->tempFolder = $tempFolder;
        return $this;
    }

    /**
     * Opens the file at the given file path to make it ready to be read.
     * It also parses the sharedStrings.xml file to get all the shared strings available in memory
     * and fetches all the available worksheets.
     *
     * @param  string $filePath Path of the file to be read
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the file at the given path or its content cannot be read
     * @throws Exception\NoWorksheetsFoundException If there are no worksheets in the file
     */
    protected function openReader($filePath)
    {
        $this->filePath = $filePath;
        $this->zip = new \ZipArchive();

        if ($this->zip->open($filePath) === true) {
            // Extracts all the strings from the worksheets for easy access in the future
            $this->extractSharedStrings($filePath);

            // Fetch all available worksheets
            $this->worksheetHelper = new WorksheetHelper($filePath);
            $this->worksheets = $this->worksheetHelper->getWorksheets($filePath);

            if (count($this->worksheets) === 0) {
                throw new NoWorksheetsFoundException('The file must contain at least one worksheet.');
            }
        } else {
            throw new IOException('Could not open ' . $filePath . ' for reading.');
        }
    }

    /**
     * Builds an in-memory array containing all the shared strings of the worksheets.
     *
     * @param  string $filePath Path of the XLSX file to be read
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If sharedStrings XML file can't be read
     */
    protected function extractSharedStrings($filePath)
    {
        $this->sharedStringsHelper = new SharedStringsHelper($filePath, $this->tempFolder);
        $this->sharedStringsHelper->extractSharedStrings();
    }

    /**
     * Returns whether another worksheet exists after the current worksheet.
     *
     * @return bool Whether another worksheet exists after the current worksheet.
     * @throws Exception\ReaderNotOpenedException If the stream was not opened first
     */
    public function hasNextSheet()
    {
        if (!$this->isStreamOpened) {
            throw new ReaderNotOpenedException('Stream should be opened first.');
        }

        return $this->worksheetHelper->hasNextWorksheet($this->currentWorksheet, $this->worksheets);
    }

    /**
     * Moves the pointer to the current worksheet.
     * Moving to another worksheet will stop the reading in the current worksheet.
     *
     * @return void
     * @throws Exception\ReaderNotOpenedException If the stream was not opened first
     * @throws Exception\EndOfWorksheetsReachedException If there is no more worksheets to read
     */
    public function nextSheet()
    {
        if ($this->hasNextSheet()) {
            if ($this->currentWorksheet === null) {
                $nextWorksheet = $this->worksheets[0];
            } else {
                $currentWorksheetNumber = $this->currentWorksheet->getWorksheetNumber();
                $nextWorksheet = $this->worksheets[$currentWorksheetNumber + 1];
            }

            $this->initXmlReaderForWorksheetData($nextWorksheet);
            $this->currentWorksheet = $nextWorksheet;

            // make sure that we are ready to read more rows
            $this->hasReachedEndOfFile = false;
            $this->emptyRowDataBuffer();
        } else {
            throw new EndOfWorksheetsReachedException('End of worksheets was reached. Cannot read more worksheets.');
        }
    }

    /**
     * Initializes the XMLReader object that reads worksheet data for the given worksheet.
     * If another worksheet was being read, it closes the reader before reopening it for the new worksheet.
     * The XMLReader is configured to be safe from billion laughs attack.
     *
     * @param Internal\XLSX\Worksheet $worksheet The worksheet to initialize the XMLReader with
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the worksheet data XML cannot be read
     */
    protected function initXmlReaderForWorksheetData($worksheet)
    {
        // if changing worksheet and the XMLReader was initialized for the current worksheet
        if ($worksheet != $this->currentWorksheet && $this->xmlReader) {
            $this->xmlReader->close();
        } else if (!$this->xmlReader) {
            $this->xmlReader = new \XMLReader();
        }

        $worksheetDataXMLFilePath = $worksheet->getDataXmlFilePath();

        $worksheetDataFilePath = 'zip://' . $this->filePath . '#' . $worksheetDataXMLFilePath;
        if ($this->xmlReader->open($worksheetDataFilePath, null, LIBXML_NONET) === false) {
            throw new IOException('Could not open "' . $worksheetDataXMLFilePath . '".');
        }
    }

    /**
     * Reads and returns data of the line that comes after the last read line, on the current worksheet.
     * Empty rows will be skipped.
     *
     * @return array|null Array that contains the data for the read line or null at the end of the file
     * @throws \Box\Spout\Common\Exception\BadUsageException If the pointer to the current worksheet has not been set
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If a shared string was not found
     */
    protected function read()
    {
        if (!$this->currentWorksheet) {
            throw new BadUsageException('You must call nextSheet() before calling hasNextRow() or nextRow()');
        }

        $isInsideRowTag = false;
        $rowData = array();

        while ($this->xmlReader->read()) {
            if ($this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name === 'dimension') {
                // Read dimensions of the worksheet
                $dimensionRef = $this->xmlReader->getAttribute('ref'); // returns 'A1:M13' for instance (or 'A1' for empty sheet)
                if (preg_match('/[A-Z\d]+:([A-Z\d]+)/', $dimensionRef, $matches)) {
                    $lastCellIndex = $matches[1];
                    $this->numberOfColumns = CellHelper::getColumnIndexFromCellIndex($lastCellIndex) + 1;
                }
            } else if ($this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name === 'row') {
                // Start of the row description
                $isInsideRowTag = true;

                // Read spans info if present
                $numberOfColumnsForRow = $this->numberOfColumns;
                $spans = $this->xmlReader->getAttribute('spans'); // returns '1:5' for instance
                if ($spans) {
                    list(, $numberOfColumnsForRow) = explode(':', $spans);
                    $numberOfColumnsForRow = intval($numberOfColumnsForRow);
                }
                $rowData = ($numberOfColumnsForRow !== 0) ? array_fill(0, $numberOfColumnsForRow, '') : [];
            } else if ($isInsideRowTag && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name === 'c') {
                // Start of a cell description
                $currentCellIndex = $this->xmlReader->getAttribute('r');
                $currentColumnIndex = CellHelper::getColumnIndexFromCellIndex($currentCellIndex);
                $node = $this->xmlReader->expand();

                $hasSharedString = ($this->xmlReader->getAttribute('t') === 's');
                if ($hasSharedString) {
                    $sharedStringIndex = intval($node->nodeValue);
                    $rowData[$currentColumnIndex] = $this->sharedStringsHelper->getStringAtIndex($sharedStringIndex);
                } else {
                    // for inline strings or numbers, just get the value
                    $rowData[$currentColumnIndex] = trim($node->nodeValue);
                }
            } else if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name === 'row') {
                // End of the row description
                // If needed, we fill the empty cells
                $rowData = ($this->numberOfColumns !== 0) ? $rowData : CellHelper::fillMissingArrayIndexes($rowData);
                break;
            }
        }

        // no data means "end of file"
        return ($rowData !== array()) ? $rowData : null;
    }

    /**
     * Closes the reader. To be used after reading the file.
     *
     * @return void
     */
    protected function closeReader()
    {
        if ($this->xmlReader) {
            $this->xmlReader->close();
        }

        if ($this->zip) {
            $this->zip->close();
        }

        $this->sharedStringsHelper->cleanup();
    }
}
