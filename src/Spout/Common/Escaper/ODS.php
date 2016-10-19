<?php

namespace Box\Spout\Common\Escaper;

use Box\Spout\Common\Singleton;

/**
 * Class ODS
 * Provides functions to escape and unescape data for ODS files
 *
 * @package Box\Spout\Common\Escaper
 */
class ODS implements EscaperInterface
{
    use Singleton;

    /** @var string Regex pattern to detect control characters that need to be escaped */
    protected $escapableControlCharactersPattern;

    /**
     * Initializes the singleton instance
     */
    protected function init()
    {
        $this->escapableControlCharactersPattern = $this->getEscapableControlCharactersPattern();
    }

    /**
     * @return string Regex pattern containing all escapable control characters
     */
    protected function getEscapableControlCharactersPattern()
    {
        // control characters values are from 0 to 1F (hex values) in the ASCII table
        // some characters should not be escaped though: "\t", "\r" and "\n".
        return '[\x00-\x08' .
        // skipping "\t" (0x9) and "\n" (0xA)
        '\x0B-\x0C' .
        // skipping "\r" (0xD)
        '\x0E-\x1F]';
    }

    /**
     * Escapes the given string to make it compatible with XLSX
     *
     * @param string $string The string to escape
     * @return string The escaped string
     */
    public function escape($string)
    {
        if (defined('ENT_DISALLOWED')) {
            // 'ENT_DISALLOWED' ensures that invalid characters in the given document type are replaced.
            // Otherwise characters like a vertical tab "\v" will make the XML document unreadable by the XML processor
            // @link https://github.com/box/spout/issues/329
            return htmlspecialchars($string, ENT_QUOTES | ENT_DISALLOWED);
        } else {
            // We are on hhvm or any other engine that does not support ENT_DISALLOWED
            $escapedString =  htmlspecialchars($string, ENT_QUOTES);
            $replacedString = preg_replace('/'.$this->escapableControlCharactersPattern.'/', '�', $escapedString);
            return $replacedString;
        }
    }

    /**
     * Unescapes the given string to make it compatible with XLSX
     *
     * @param string $string The string to unescape
     * @return string The unescaped string
     */
    public function unescape($string)
    {
        return htmlspecialchars_decode($string, ENT_QUOTES);
    }
}
