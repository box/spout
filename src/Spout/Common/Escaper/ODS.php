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

    /**
     * Escapes the given string to make it compatible with XLSX
     *
     * @param string $string The string to escape
     * @return string The escaped string
     */
    public function escape($string)
    {
        if (defined('ENT_DISALLOWED')) {
            return htmlspecialchars($string, ENT_QUOTES | ENT_DISALLOWED);
        } else {
            // We are on hhvm or any other engine that does not support ENT_DISALLOWED
            // https://github.com/box/spout/issues/329
            $escapedString =  htmlspecialchars($string, ENT_QUOTES);
            $replacedString = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', '�', $escapedString);
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
