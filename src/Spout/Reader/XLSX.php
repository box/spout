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
    const INLINE_STRING_CELL_TYPE = 'inlineStr';
    const STR_CELL_TYPE = 'str';
    const SHARED_STRING_CELL_TYPE = 's';
    const BOOLEAN_CELL_TYPE = 'b';
    const NUMERIC_CELL_TYPE = 'n';
    const DATE_CELL_TYPE = 'd';
    const EMPTY_CELL_TYPE = 'e';
    
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
            $this->sharedStringsHelper = new SharedStringsHelper($filePath, $this->tempFolder);

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
     * Returns the cell String value associated to the given XML node where string is inline.
     *
     * @param \DOMNode $node
     * @return mixed The value associated with the cell
     */
    protected function getVNodeValue(&$node)
    {
        // all other cell types should have a "v" tag containing the value.
        // if not, the returned value should be empty string.
        $vNode = $node->getElementsByTagName('v')->item(0);
        if ($vNode !== null) {
            return $vNode->nodeValue;
        }
        return "";
    }
    
    /**
     * Returns the cell String value associated to the given XML node where string is inline.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatInlineStringCellValue(&$node, &$escaper)
    {
        // inline strings are formatted this way:
        // <c r="A1" t="inlineStr"><is><t>[INLINE_STRING]</t></is></c>
        $tNode = $node->getElementsByTagName('t')->item(0);
        $escapedCellValue = trim($tNode->nodeValue);
        $cellValue = $escaper->unescape($escapedCellValue);
        return $cellValue;
    }
    
    /**
     * Returns the cell String value associated to the given XML node where string is shared in shared-strings file.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatSharedStringCellValue(&$node, &$escaper)
    {
        // shared strings are formatted this way:
        // <c r="A1" t="s"><v>[SHARED_STRING_INDEX]</v></c>
        $sharedStringIndex = intval($this->getVNodeValue($node));
        $escapedCellValue = $this->sharedStringsHelper->getStringAtIndex($sharedStringIndex);
        $cellValue = $escaper->unescape($escapedCellValue);
        return $cellValue;
    }
    
    /**
     * Returns the cell String value associated to the given XML node where string is stored in value node.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatStrCellValue(&$node, &$escaper)
    {
        $escapedCellValue = trim($this->getVNodeValue($node));
        $cellValue = $escaper->unescape($escapedCellValue);
        return $cellValue;
    }
    
    /**
     * Returns the cell Numeric value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return int|float The value associated with the cell
     */
    protected function formatNumericCellValue(&$node)
    {
        $nodeValue = $this->getVNodeValue($node);
        $cellValue = is_int($nodeValue) ? intval($nodeValue) : floatval($nodeValue);
        return $cellValue;
    }
    
    /**
     * Returns the cell Boolean value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @return bool The value associated with the cell
     */
    protected function formatBooleanCellValue(&$node)
    {
        // !! is similar to boolval()
        $cellValue = (!!$this->getVNodeValue($node));
        return $cellValue;
    }

    /**
     * Returns the cell Date value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return DateTime The value associated with the cell (null when the cell has an error)
     */
    protected function formatDateCellValue(&$node)
    {
        // Mitigate thrown Exception on invalid date-time format (http://php.net/manual/en/datetime.construct.php)
        try {
            $cellValue = new \DateTime($this->getVNodeValue($node));
            return $cellValue;
        } catch ( \Exception $e ) {
            // Maybe do something... Not famiiar enough to see about exceptions at this stage
            return null;
        }
    }

    /**
     * Returns the (unescaped) cell value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @param \Box\Spout\Common\Escaper\XLSX $escaper
     * @return string|int|float|bool|null The value associated with the cell (null when the cell has an error)
     */
    protected function getCellValue($node, $escaper)
    {
        // Default cell type is "n"
        $cellType = $node->getAttribute('t') ?: 'n';
        
        switch($cellType) {
            case self::INLINE_STRING_CELL_TYPE:
                return $this->formatInlineStringCellValue($node, $escaper);
            case self::SHARED_STRING_CELL_TYPE:
                return $this->formatSharedStringCellValue($node, $escaper);
            case self::STR_CELL_TYPE:
                return $this->formatStrCellValue($node, $escaper);
            case self::BOOLEAN_CELL_TYPE:
                return $this->formatBooleanCellValue($node);
            case self::NUMERIC_CELL_TYPE:
                return $this->formatNumericCellValue($node);
            case self::DATE_CELL_TYPE:
                return $this->formatDateCellValue($node);
            default:
                if($cellType !== self::EMPTY_CELL_TYPE) {
                    \trigger_error('UNKNOWN CELL TYPE', \E_USER_NOTICE);
                }
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
