<?php

namespace Box\Spout\Writer\Common\Helper;

class AppendHelper {

    /**
     * Instead of seeking and re-writing from position, a better hack might be to write dummy empty data
     * Enough to take care of any length, then carefully overwrite
     * 
     */

    /**
     * This function will truncate from specified position
     * Write data to be inserted and re-append the truncated data
     *  
     * @param $fp Pointer to file only
     * @param $pos Position to insert
     * @param $content Content to insert
     */
    public static function insertToFile($fp, $pos, $content)
    {
        fseek($fp, $pos);
        $trailer = stream_get_contents($fp);
        ftruncate($fp, $pos);
        fseek($fp, $pos);
        fwrite($fp, $content);
        fwrite($fp, $trailer);
        return $fp;
    }
}
