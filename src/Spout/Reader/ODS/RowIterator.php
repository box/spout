<?php

namespace Box\Spout\Reader\ODS;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\IteratorNotRewindableException;
use Box\Spout\Reader\Exception\XMLProcessingException;
use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Reader\Wrapper\XMLReader;

/**
 * Class RowIterator
 *
 * @package Box\Spout\Reader\ODS
 */
class RowIterator implements IteratorInterface
{
    /** Definition of all possible cell types */
    const CELL_TYPE_STRING = 'string';
    const CELL_TYPE_BOOLEAN = 'boolean';
    const CELL_TYPE_FLOAT = 'float';

    /** Definition of XML nodes names used to parse data */
    const XML_NODE_TABLE = 'table:table';
    const XML_NODE_ROW = 'table:table-row';
    const XML_NODE_CELL = 'table:table-cell';
    const XML_NODE_P = 'p';
    const XML_NODE_S = 'text:s';

    /** Definition of XML attribute used to parse data */
    const XML_ATTRIBUTE_TYPE = 'office:value-type';
    const XML_ATTRIBUTE_NUM_COLUMNS_REPEATED = 'table:number-columns-repeated';
    const XML_ATTRIBUTE_C = 'text:c';

    /** @var \Box\Spout\Reader\Wrapper\XMLReader The XMLReader object that will help read sheet's XML data */
    protected $xmlReader;

    /** @var bool Whether the iterator has already been rewound once */
    protected $hasAlreadyBeenRewound = false;

    /** @var \Box\Spout\Common\Escaper\ODS Used to unescape XML data */
    protected $escaper;

    /** @var int Number of read rows */
    protected $numReadRows = 0;

    /** @var array|null Buffer used to store the row data, while checking if there are more rows to read */
    protected $rowDataBuffer = null;

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /**
     * @param XMLReader $xmlReader XML Reader, positioned on the "<table:table>" element
     */
    public function __construct($xmlReader)
    {
        $this->xmlReader = $xmlReader;

        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->escaper = new \Box\Spout\Common\Escaper\ODS();
    }

    /**
     * Rewind the Iterator to the first element.
     * NOTE: It can only be done once, as it is not possible to read an XML file backwards.
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     * @throws \Box\Spout\Reader\Exception\IteratorNotRewindableException If the iterator is rewound more than once
     */
    public function rewind()
    {
        // Because sheet and row data is located in the file, we can't rewind both the
        // sheet iterator and the row iterator, as XML file cannot be read backwards.
        // Therefore, rewinding the row iterator has been disabled.
        if ($this->hasAlreadyBeenRewound) {
            throw new IteratorNotRewindableException();
        }

        $this->hasAlreadyBeenRewound = true;
        $this->numReadRows = 0;
        $this->rowDataBuffer = null;
        $this->hasReachedEndOfFile = false;

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
     * @throws \Box\Spout\Common\Exception\IOException If unable to read the sheet data XML
     */
    public function next()
    {
        $rowData = [];
        $cellValue = null;
        $numColumnsRepeated = 1;
        $numCellsRead = 0;
        $hasAlreadyReadOneCell = false;

        try {
            while ($this->xmlReader->read()) {
                if ($this->xmlReader->isPositionedOnStartingNode(self::XML_NODE_CELL)) {
                    // Start of a cell description
                    $currentNumColumnsRepeated = $this->getNumColumnsRepeatedForCurrentNode();

                    $node = $this->xmlReader->expand();
                    $currentCellValue = $this->getCellValue($node);

                    // process cell N only after having read cell N+1 (see below why)
                    if ($hasAlreadyReadOneCell) {
                        for ($i = 0; $i < $numColumnsRepeated; $i++) {
                            $rowData[] = $cellValue;
                        }
                    }

                    $cellValue = $currentCellValue;
                    $numColumnsRepeated = $currentNumColumnsRepeated;

                    $numCellsRead++;
                    $hasAlreadyReadOneCell = true;

                } else if ($this->xmlReader->isPositionedOnEndingNode(self::XML_NODE_ROW)) {
                    // End of the row description
                    $isEmptyRow = ($numCellsRead <= 1 && empty($cellValue));
                    if ($isEmptyRow) {
                        // skip empty rows
                        $this->next();
                        return;
                    }

                    // Only add value if the last read cell is not empty or does not need to repeat cell values.
                    // This is to avoid creating a lot of empty cells, as Excel adds a last empty "<table:table-cell>"
                    // with a number-columns-repeated value equals to the number of (supported columns - used columns).
                    // In Excel, the number of supported columns is 16384, but we don't want to returns rows with always 16384 cells.
                    if (!empty($cellValue) || $numColumnsRepeated === 1) {
                        for ($i = 0; $i < $numColumnsRepeated; $i++) {
                            $rowData[] = $cellValue;
                        }

                        $this->numReadRows++;
                    }
                    break;

                } else if ($this->xmlReader->isPositionedOnEndingNode(self::XML_NODE_TABLE)) {
                    // The closing "</table:table>" marks the end of the file
                    $this->hasReachedEndOfFile = true;
                    break;
                }
            }

        } catch (XMLProcessingException $exception) {
            throw new IOException("The sheet's data cannot be read. [{$exception->getMessage()}]");
        }

        $this->rowDataBuffer = $rowData;
    }

    /**
     * @return int The value of "table:number-columns-repeated" attribute of the current node, or 1 if attribute missing
     */
    protected function getNumColumnsRepeatedForCurrentNode()
    {
        $numColumnsRepeated = $this->xmlReader->getAttribute(self::XML_ATTRIBUTE_NUM_COLUMNS_REPEATED);
        return ($numColumnsRepeated !== null) ? intval($numColumnsRepeated) : 1;
    }

    /**
     * Returns the (unescaped) correctly marshalled, cell value associated to the given XML node.
     * @TODO Add other types !!
     *
     * @param \DOMNode $node
     * @return string|int|float|bool The value associated with the cell (or empty string if cell's type is undefined)
     */
    protected function getCellValue($node)
    {
        $cellType = $node->getAttribute(self::XML_ATTRIBUTE_TYPE);
        $pNodeValue = $this->getTextPNodeValue($node);

        switch ($cellType) {
            case self::CELL_TYPE_STRING:
                return $this->formatStringCellValue($node);
            case self::CELL_TYPE_FLOAT:
                return $this->formatFloatCellValue($pNodeValue);
            case self::CELL_TYPE_BOOLEAN:
                return $this->formatBooleanCellValue($pNodeValue);
            default:
                return '';
        }
    }

    /**
     * Returns the value of the first "<text:p>" node within the given node.
     *
     * @param \DOMNode $node
     * @return string Value for the first "<text:p>" node or empty string if no "<text:p>" found
     */
    protected function getTextPNodeValue($node)
    {
        $nodeValue = '';
        $pNodes = $node->getElementsByTagName(self::XML_NODE_P);

        if ($pNodes->length > 0) {
            $nodeValue = $pNodes->item(0)->nodeValue;
        }

        return $nodeValue;
    }

    /**
     * Returns the cell String value.
     *
     * @param \DOMNode $node
     * @return string The value associated with the cell
     */
    protected function formatStringCellValue($node)
    {
        $pNodeValues = [];
        $pNodes = $node->getElementsByTagName(self::XML_NODE_P);

        foreach ($pNodes as $pNode) {
            $currentPValue = '';

            foreach ($pNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMText) {
                    $currentPValue .= $childNode->nodeValue;
                } else if ($childNode->nodeName === self::XML_NODE_S) {
                    $spaceAttribute = $childNode->getAttribute(self::XML_ATTRIBUTE_C);
                    $numSpaces = (!empty($spaceAttribute)) ? intval($spaceAttribute) : 1;
                    $currentPValue .= str_repeat(' ', $numSpaces);
                }
            }

            $pNodeValues[] = $currentPValue;
        }

        $escapedCellValue = implode("\n", $pNodeValues);
        $cellValue = $this->escaper->unescape($escapedCellValue);
        return $cellValue;
    }

    /**
     * Returns the cell Numeric value from string of nodeValue.
     *
     * @param string $pNodeValue
     * @return int|float The value associated with the cell
     */
    protected function formatFloatCellValue($pNodeValue)
    {
        $cellValue = is_int($pNodeValue) ? intval($pNodeValue) : floatval($pNodeValue);
        return $cellValue;
    }

    /**
     * Returns the cell Boolean value from a specific node's Value.
     *
     * @param string $pNodeValue
     * @return bool The value associated with the cell
     */
    protected function formatBooleanCellValue($pNodeValue)
    {
        // !! is similar to boolval()
        $cellValue = !!$pNodeValue;
        return $cellValue;
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
