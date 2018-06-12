<?php

namespace Box\Spout\Common\Helper\Escaper;

/**
 * Class ODS
 * Provides functions to escape and unescape data for ODS files
 */
class ODS implements EscaperInterface
{
    /**
     * Escapes the given string to make it compatible with ODS
     *
     * @param string $string The string to escape
     * @return string The escaped string
     */
    public function escape($string)
    {
        // 'ENT_DISALLOWED' ensures that invalid characters in the given document type are replaced.
        // Otherwise control characters like a vertical tab "\v" will make the XML document unreadable
        // by the XML processor
        // @link https://github.com/box/spout/issues/329
        return htmlspecialchars($string, ENT_NOQUOTES | ENT_DISALLOWED);
    }

    /**
     * Unescapes the given string to make it compatible with ODS
     *
     * @param string $string The string to unescape
     * @return string The unescaped string
     */
    public function unescape($string)
    {
        // ==============
        // =   WARNING  =
        // ==============
        // It is assumed that the given string has already had its XML entities decoded.
        // This is true if the string is coming from a DOMNode (as DOMNode already decode XML entities on creation).
        // Therefore there is no need to call "htmlspecialchars_decode()".
        return $string;
    }
}
