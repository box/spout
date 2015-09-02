<?php

namespace Box\Spout\Reader\ODS\Helper;

/**
 * Class CellValueFormatter
 * This class provides helper functions to format cell values
 *
 * @package Box\Spout\Reader\ODS\Helper
 */
class CellValueFormatter
{
    /** Definition of all possible cell types */
    const CELL_TYPE_STRING = 'string';
    const CELL_TYPE_BOOLEAN = 'boolean';
    const CELL_TYPE_FLOAT = 'float';

    /** Definition of XML nodes names used to parse data */
    const XML_NODE_P = 'p';
    const XML_NODE_S = 'text:s';

    /** Definition of XML attribute used to parse data */
    const XML_ATTRIBUTE_TYPE = 'office:value-type';
    const XML_ATTRIBUTE_C = 'text:c';

    /** @var \Box\Spout\Common\Escaper\ODS Used to unescape XML data */
    protected $escaper;

    /**
     *
     */
    public function __construct()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->escaper = new \Box\Spout\Common\Escaper\ODS();
    }

    /**
     * Returns the (unescaped) correctly marshalled, cell value associated to the given XML node.
     * @TODO Add other types !!
     *
     * @param \DOMNode $node
     * @return string|int|float|bool The value associated with the cell (or empty string if cell's type is undefined)
     */
    public function extractAndFormatNodeValue($node)
    {
        $cellType = $node->getAttribute(self::XML_ATTRIBUTE_TYPE);
        $pNodeValue = $this->getFirstPNodeValue($node);

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
    protected function getFirstPNodeValue($node)
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
}
