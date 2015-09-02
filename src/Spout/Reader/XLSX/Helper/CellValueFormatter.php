<?php

namespace Box\Spout\Reader\XLSX\Helper;

/**
 * Class CellValueFormatter
 * This class provides helper functions to format cell values
 *
 * @package Box\Spout\Reader\XLSX\Helper
 */
class CellValueFormatter
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
    const XML_NODE_VALUE = 'v';
    const XML_NODE_INLINE_STRING_VALUE = 't';

    /** Definition of XML attributes used to parse data */
    const XML_ATTRIBUTE_TYPE = 't';

    /** @var SharedStringsHelper Helper to work with shared strings */
    protected $sharedStringsHelper;

    /** @var \Box\Spout\Common\Escaper\XLSX Used to unescape XML data */
    protected $escaper;

    /**
     * @param SharedStringsHelper $sharedStringsHelper Helper to work with shared strings
     */
    public function __construct($sharedStringsHelper)
    {
        $this->sharedStringsHelper = $sharedStringsHelper;

        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->escaper = new \Box\Spout\Common\Escaper\XLSX();
    }

    /**
     * Returns the (unescaped) correctly marshalled, cell value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @return string|int|float|bool|\DateTime|null The value associated with the cell (null when the cell has an error)
     */
    public function extractAndFormatNodeValue($node)
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
        return ($vNode !== null) ? $vNode->nodeValue : '';
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
     * @return \DateTime|null The value associated with the cell or NULL if invalid date value
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
}
