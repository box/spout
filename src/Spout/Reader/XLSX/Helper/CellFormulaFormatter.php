<?php

namespace Box\Spout\Reader\XLSX\Helper;

/**
 * Class CellFormulaFormatter
 * This class provides helper functions to format cell formulas
 */
class CellFormulaFormatter
{
    /** Definition of XML nodes names used to parse data */
    const XML_NODE_FORMULA = 'f';
    
    /**
     * Returns the cell formula associated to the given XML node.
     *
     * @param \DOMNode $node
     * @return string The formula associated with the cell
     */
    public function extractNodeFormula($node)
    {
        // for cell types having a "f" tag containing the formula.
        // if not, the returned formula should be empty string.
        $vNode = $node->getElementsByTagName(self::XML_NODE_FORMULA)->item(0);

        return ($vNode !== null) ? $vNode->nodeValue : '';
    }
}