<?php

namespace Box\Spout\Reader\XLSX;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Reader\XLSX\Helper\CellHelper;

/**
 * Class RowIterator
 *
 * @package Box\Spout\Reader\XLSX
 */
class RowIterator implements IteratorInterface
{
    /** Definition of all possible cell types */
    const CELL_TYPE_INLINE_STRING = 'inlineStr';
    const CELL_TYPE_STR = 'str';
    const CELL_TYPE_SHARED_STRING = 's';
    const CELL_TYPE_BOOLEAN = 'b';
    const CELL_TYPE_NUMERIC = 'n';
    const CELL_TYPE_DATE = 'd';
    const CELL_TYPE_ERROR = 'e';

    /** Definition of XML nodes names used to parse data */
    const XML_NODE_DIMENSION = 'dimension';
    const XML_NODE_WORKSHEET = 'worksheet';
    const XML_NODE_ROW = 'row';
    const XML_NODE_CELL = 'c';
    const XML_NODE_VALUE = 'v';
    const XML_NODE_INLINE_STRING_VALUE = 't';

    /** Definition of XML attributes used to parse data */
    const XML_ATTRIBUTE_REF = 'ref';
    const XML_ATTRIBUTE_SPANS = 'spans';
    const XML_ATTRIBUTE_CELL_INDEX = 'r';
    const XML_ATTRIBUTE_TYPE = 't';

    /** @var string Path of the XLSX file being read */
    protected $filePath;

    /** @var string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml */
    protected $sheetDataXMLFilePath;

    /** @var Helper\SharedStringsHelper Helper to work with shared strings */
    protected $sharedStringsHelper;

    /** @var \XMLReader The XMLReader object that will help read sheet's XML data */
    protected $xmlReader;

    /** @var \Box\Spout\Common\Escaper\XLSX Used to unescape XML data */
    protected $escaper;

    /** @var int Number of read rows */
    protected $numReadRows = 0;

    /** @var array|null Buffer used to store the row data, while checking if there are more rows to read */
    protected $rowDataBuffer = null;

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /** @var int The number of columns the sheet has (0 meaning undefined) */
    protected $numColumns = 0;

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml
     * @param Helper\SharedStringsHelper $sharedStringsHelper Helper to work with shared strings
     */
    public function __construct($filePath, $sheetDataXMLFilePath, $sharedStringsHelper)
    {
        $this->filePath = $filePath;
        $this->sheetDataXMLFilePath = $this->normalizeSheetDataXMLFilePath($sheetDataXMLFilePath);
        $this->sharedStringsHelper = $sharedStringsHelper;

        $this->xmlReader = new \XMLReader();
        $this->escaper = new \Box\Spout\Common\Escaper\XLSX();
    }

    /**
     * @param string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml
     * @return string Path of the XML file containing the sheet data,
     *                without the leading slash.
     */
    protected function normalizeSheetDataXMLFilePath($sheetDataXMLFilePath)
    {
        return ltrim($sheetDataXMLFilePath, '/');
    }

    /**
     * Rewind the Iterator to the first element.
     * Initializes the XMLReader object that reads the associated sheet data.
     * The XMLReader is configured to be safe from billion laughs attack.
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data XML cannot be read
     */
    public function rewind()
    {
        $this->xmlReader->close();

        $sheetDataFilePath = 'zip://' . $this->filePath . '#' . $this->sheetDataXMLFilePath;
        if ($this->xmlReader->open($sheetDataFilePath, null, LIBXML_NONET) === false) {
            throw new IOException('Could not open "' . $this->sheetDataXMLFilePath . '".');
        }

        $this->numReadRows = 0;
        $this->rowDataBuffer = null;
        $this->hasReachedEndOfFile = false;
        $this->numColumns = 0;

        $this->next();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return boolean
     */
    public function valid()
    {
        return (!$this->hasReachedEndOfFile);
    }

    /**
     * Move forward to next element. Empty rows will be skipped.
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return void
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If a shared string was not found
     */
    public function next()
    {
        $isInsideRowTag = false;
        $rowData = [];

        while ($this->xmlReader->read()) {
            if ($this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name === self::XML_NODE_DIMENSION) {
                // Read dimensions of the sheet
                $dimensionRef = $this->xmlReader->getAttribute(self::XML_ATTRIBUTE_REF); // returns 'A1:M13' for instance (or 'A1' for empty sheet)
                if (preg_match('/[A-Z\d]+:([A-Z\d]+)/', $dimensionRef, $matches)) {
                    $lastCellIndex = $matches[1];
                    $this->numColumns = CellHelper::getColumnIndexFromCellIndex($lastCellIndex) + 1;
                }

            } else if ($this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name === self::XML_NODE_ROW) {
                // Start of the row description
                $isInsideRowTag = true;

                // Read spans info if present
                $numberOfColumnsForRow = $this->numColumns;
                $spans = $this->xmlReader->getAttribute(self::XML_ATTRIBUTE_SPANS); // returns '1:5' for instance
                if ($spans) {
                    list(, $numberOfColumnsForRow) = explode(':', $spans);
                    $numberOfColumnsForRow = intval($numberOfColumnsForRow);
                }
                $rowData = ($numberOfColumnsForRow !== 0) ? array_fill(0, $numberOfColumnsForRow, '') : [];

            } else if ($isInsideRowTag && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name === self::XML_NODE_CELL) {
                // Start of a cell description
                $currentCellIndex = $this->xmlReader->getAttribute(self::XML_ATTRIBUTE_CELL_INDEX);
                $currentColumnIndex = CellHelper::getColumnIndexFromCellIndex($currentCellIndex);

                $node = $this->xmlReader->expand();
                $rowData[$currentColumnIndex] = $this->getCellValue($node);

            } else if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name === self::XML_NODE_ROW) {
                // End of the row description
                // If needed, we fill the empty cells
                $rowData = ($this->numColumns !== 0) ? $rowData : CellHelper::fillMissingArrayIndexes($rowData);
                $this->numReadRows++;
                break;

            } else if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name === self::XML_NODE_WORKSHEET) {
                // The closing "</worksheet>" marks the end of the file
                $this->hasReachedEndOfFile = true;
            }
        }

        $this->rowDataBuffer = $rowData;
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
        $vNode = $node->getElementsByTagName(self::XML_NODE_VALUE)->item(0);
        if ($vNode !== null) {
            return $vNode->nodeValue;
        }
        return "";
    }

    /**
     * Returns the cell String value where string is inline.
     *
     * @param \DOMNode $node
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatInlineStringCellValue($node)
    {
        // inline strings are formatted this way:
        // <c r="A1" t="inlineStr"><is><t>[INLINE_STRING]</t></is></c>
        $tNode = $node->getElementsByTagName(self::XML_NODE_INLINE_STRING_VALUE)->item(0);
        $escapedCellValue = trim($tNode->nodeValue);
        $cellValue = $this->escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell String value from shared-strings file using nodeValue index.
     *
     * @param string $nodeValue
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatSharedStringCellValue($nodeValue)
    {
        // shared strings are formatted this way:
        // <c r="A1" t="s"><v>[SHARED_STRING_INDEX]</v></c>
        $sharedStringIndex = intval($nodeValue);
        $escapedCellValue = $this->sharedStringsHelper->getStringAtIndex($sharedStringIndex);
        $cellValue = $this->escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell String value, where string is stored in value node.
     *
     * @param string $nodeValue
     * @return string The value associated with the cell (null when the cell has an error)
     */
    protected function formatStrCellValue($nodeValue)
    {
        $escapedCellValue = trim($nodeValue);
        $cellValue = $this->escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell Numeric value from string of nodeValue.
     *
     * @param string $nodeValue
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
     * @return \DateTime|null The value associated with the cell (null when the cell has an error)
     */
    protected function formatDateCellValue($nodeValue)
    {
        // Mitigate thrown Exception on invalid date-time format (http://php.net/manual/en/datetime.construct.php)
        try {
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
     * @return string|int|float|bool|\DateTime|null The value associated with the cell (null when the cell has an error)
     */
    protected function getCellValue($node)
    {
        // Default cell type is "n"
        $cellType = $node->getAttribute(self::XML_ATTRIBUTE_TYPE) ?: self::CELL_TYPE_NUMERIC;
        $vNodeValue = $this->getVNodeValue($node);

        if (($vNodeValue === '') && ($cellType !== self::CELL_TYPE_INLINE_STRING)) {
            return $vNodeValue;
        }

        switch ($cellType) {
            case self::CELL_TYPE_INLINE_STRING:
                return $this->formatInlineStringCellValue($node);
            case self::CELL_TYPE_SHARED_STRING:
                return $this->formatSharedStringCellValue($vNodeValue);
            case self::CELL_TYPE_STR:
                return $this->formatStrCellValue($vNodeValue);
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
     * Return the current element, from the buffer.
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return array|null
     */
    public function current()
    {
        return $this->rowDataBuffer;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key()
    {
        return $this->numReadRows;
    }


    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end()
    {
        $this->xmlReader->close();
    }
}
