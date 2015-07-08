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
    const CELL_TYPE_INLINE_STRING = 'inlineStr';
    const CELL_TYPE_STR = 'str';
    const CELL_TYPE_SHARED_STRING = 's';
    const CELL_TYPE_BOOLEAN = 'b';
    const CELL_TYPE_NUMERIC = 'n';
    const CELL_TYPE_DATE = 'd';
    const CELL_TYPE_ERROR = 'e';
    
    /** @var string Real path of the file to read */
    protected $filePath;

    /** @var string Temporary folder where the temporary files will be created */
    protected $tempFolder;
    
    /** @var bool Disabling this will increase your memory usage but can improve your execution time */
    protected $useSharedStringsFileCache = true;

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
     * Disabling the shared strings file cache will increase your memory usage but can improve your execution time.
     * The shared strings file cache is active by default.
     *
     * @param bool $useSharedStringsFileCache
     * @return XLSX
     */
    public function setUseSharedStringsFileCache($useSharedStringsFileCache)
    {    
        $this->useSharedStringsFileCache = $useSharedStringsFileCache;
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
            $this->sharedStringsHelper = new SharedStringsHelper($filePath, $this->tempFolder, $this->useSharedStringsFileCache);

            if ($this->sharedStringsHelper->hasSharedStrings()) {
                // Extracts all the strings from the worksheets for easy access in the future
                $this->sharedStringsHelper->extractSharedStrings();
            }

            // Fetch all available worksheets
            $this->worksheetHelper = new WorksheetHelper($filePath, $this->globalFunctionsHelper);
            $this->worksheets = $this->worksheetHelper->getWorksheets($filePath);

            if (count($this->worksheets) === 0) {
                throw new NoWorksheetsFoundException('The file must contain at least one worksheet.');
            }
        } else {
            throw new IOException('Could not open ' . $filePath . ' for reading.');
        }
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
     * @return \Box\Spout\Reader\Sheet The next sheet
     * @throws Exception\ReaderNotOpenedException If the stream was not opened first
     * @throws Exception\EndOfWorksheetsReachedException If there is no more worksheets to read
     */
    public function nextSheet()
    {
        if (!$this->hasNextSheet()) {
            throw new EndOfWorksheetsReachedException('End of worksheets was reached. Cannot read more worksheets.');
        }

        if ($this->currentWorksheet === null) {
            $nextWorksheet = $this->worksheets[0];
        } else {
            $currentWorksheetIndex = $this->currentWorksheet->getWorksheetIndex();
            $nextWorksheet = $this->worksheets[$currentWorksheetIndex + 1];
        }

        $this->initXmlReaderForWorksheetData($nextWorksheet);
        $this->currentWorksheet = $nextWorksheet;

        // make sure that we are ready to read more rows
        $this->hasReachedEndOfFile = false;
        $this->emptyRowDataBuffer();

        return $this->currentWorksheet->getExternalSheet();
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
        if ($this->xmlReader->open($worksheetDataFilePath, null, LIBXML_NOENT|LIBXML_NONET) === false) {
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

        $escaper = new \Box\Spout\Common\Escaper\XLSX();
        $isInsideRowTag = false;
        $rowData = [];

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
                $rowData[$currentColumnIndex] = $this->getCellValue($node, $escaper);

            } else if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name === 'row') {
                // End of the row description
                // If needed, we fill the empty cells
                $rowData = ($this->numberOfColumns !== 0) ? $rowData : CellHelper::fillMissingArrayIndexes($rowData);
                break;
            }
        }

        // no data means "end of file"
        return ($rowData !== []) ? $rowData : null;
    }

    /**
     * Returns the cell's string value from a node's nested value node
     *
     * @param \DOMNode $node
     * @return string The value associated with the cell
     */
    protected function getVNodeValue($node)
    {
        // for cell types having a "v" tag containing the value.
        // if not, the returned value should be empty string.
        $vNode = $node->getElementsByTagName('v')->item(0);
        if ($vNode !== null) {
            return $vNode->nodeValue;
        }
        return "";
    }

    /**
     * Returns the cell String value where string is inline.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatInlineStringCellValue($node, $escaper)
    {
        // inline strings are formatted this way:
        // <c r="A1" t="inlineStr"><is><t>[INLINE_STRING]</t></is></c>
        $tNode = $node->getElementsByTagName('t')->item(0);
        $escapedCellValue = trim($tNode->nodeValue);
        $cellValue = $escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell String value from shared-strings file using nodeValue index.
     *
     * @param string $nodeValue
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatSharedStringCellValue($nodeValue, $escaper)
    {
        // shared strings are formatted this way:
        // <c r="A1" t="s"><v>[SHARED_STRING_INDEX]</v></c>
        $sharedStringIndex = intval($nodeValue);
        $escapedCellValue = $this->sharedStringsHelper->getStringAtIndex($sharedStringIndex);
        $cellValue = $escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell String value, where string is stored in value node.
     *
     * @param string $nodeValue
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatStrCellValue($nodeValue, $escaper)
    {
        $escapedCellValue = trim($nodeValue);
        $cellValue = $escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell Numeric value from string of nodeValue.
     *
     * @param string $nodeValue
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return int|float The value associated with the cell
     */
    protected function formatNumericCellValue($nodeValue)
    {
        $cellValue = is_int($nodeValue) ? intval($nodeValue) : floatval($nodeValue);
        return $cellValue;
    }

    /**
     * Returns the cell Boolean value from a specific node's Value.
     *
     * @param string $nodeValue
     * @return bool The value associated with the cell
     */
    protected function formatBooleanCellValue($nodeValue)
    {
        // !! is similar to boolval()
        $cellValue = !!$nodeValue;
        return $cellValue;
    }

    /**
     * Returns a cell's PHP Date value, associated to the given stored nodeValue.
     *
     * @param string $nodeValue
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return DateTime|null The value associated with the cell (null when the cell has an error)
     */
    protected function formatDateCellValue($nodeValue)
    {
        try { // Mitigate thrown Exception on invalid date-time format (http://php.net/manual/en/datetime.construct.php)
            $cellValue = new \DateTime($nodeValue);
            return $cellValue;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Returns the (unescaped) correctly marshalled, cell value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string|int|float|bool|null The value associated with the cell (null when the cell has an error)
     */
    protected function getCellValue($node, $escaper)
    {
        // Default cell type is "n"
        $cellType = $node->getAttribute('t') ?: 'n';
        $vNodeValue = $this->getVNodeValue($node);
        if ( ($vNodeValue === "") && ($cellType !== self::CELL_TYPE_INLINE_STRING) ) {
            return $vNodeValue;
        }

        switch ($cellType)
        {
            case self::CELL_TYPE_INLINE_STRING:
                return $this->formatInlineStringCellValue($node, $escaper);
            case self::CELL_TYPE_SHARED_STRING:
                return $this->formatSharedStringCellValue($vNodeValue, $escaper);
            case self::CELL_TYPE_STR:
                return $this->formatStrCellValue($vNodeValue, $escaper);
            case self::CELL_TYPE_BOOLEAN:
                return $this->formatBooleanCellValue($vNodeValue);
            case self::CELL_TYPE_NUMERIC:
                return $this->formatNumericCellValue($vNodeValue);
            case self::CELL_TYPE_DATE:
                return $this->formatDateCellValue($vNodeValue);
            default:
                return null;
        }
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
