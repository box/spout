<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\IOException;

// Based on https://gist.github.com/ihumanable/929039
//  ... with some bug fixes by Chris Graham, ocProducts.

/**
 * Class XLS
 * This class provides support to write data to XLS files
 *
 * @package Box\Spout\Writer
 */
class XLS extends AbstractWriter
{
    /** Number of rows to write before flushing */
    const FLUSH_THRESHOLD = 500;

    /** @var string Content-Type value for the header */
    protected static $headerContentType = 'application/vnd.ms-excel; charset=UTF-8';

    /** @var int */
    private $col;

    /** @var int */
    private $row;

    /**
     * Opens the XLS streamer and makes it ready to accept data.
     *
     * @return void
     */
    protected function openWriter()
    {
        $this->col = 0;
        $this->row = 0;
        $this->bofMarker();
    }

    /**
     * Writes the Excel Beginning of File marker
     *
     * @see pack()
     * @return nothing
     */
    private function bofMarker()
    {
        fwrite($this->filePointer, pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0));
    }

    /**
     * Moves internal cursor all the way left, col = 0
     *
     * @return nothing
     */
    function home()
    {
        $this->col = 0;
    }

    /**
     * Moves internal cursor right by the amount specified
     *
     * @param optional integer $amount The amount to move right by, defaults to 1
     * @return integer The current column after the move
     */
    function right($amount = 1)
    {
        $this->col += $amount;
        return $this->col;
    }

    /**
     * Moves internal cursor down by amount
     *
     * @param optional integer $amount The amount to move down by, defaults to 1
     * @return integer The current row after the move
     */
    function down($amount = 1)
    {
        $this->row += $amount;
        return $this->row;
    }

    /**
     * Writes a number to the Excel Spreadsheet
     *
     * @see pack()
     * @param integer $value The value to write out
     * @return boolean Success status
     */
    function number($value)
    {
        $wasWriteSuccessful = true;
        $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, pack("sssss", 0x203, 14, $this->row, $this->col, 0x0));
        $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, pack("d", $value));
        return $wasWriteSuccessful;
    }

    /**
     * Writes a string (or label) to the Excel Spreadsheet
     *
     * @see pack()
     * @param string $value The value to write out
     * @return boolean Success status
     */
    function label($value)
    {
        $value = str_replace(array("\r\n", "\r"), "\n", $value);

        // We're doing BIFF5, not BIFF8 - meaning a 255 char limit. If you want something good, use XLSX, else XLS for compatibility.
        if (strlen($value) >= 255) {
            $value = substr($value, 0, 255);
        }

        // We are doing simple Western European encoding (utf-8 storage in BIFF-5 is very complex)
        if (function_exists('utf8_decode')) {
            $test = @utf8_decode($value);
            if (is_string($test)) {
                $value = $test;
            }
        }
        elseif (function_exists('iconv')) {
    		$test = @iconv('utf-8', 'iso-8859-1' . '//TRANSLIT', $value);
            if (is_string($test)) {
                $value = $test;
            }
        }
        elseif (function_exists('mb_convert_encoding')) {
            $test = @mb_convert_encoding($value, 'iso-8859-1', 'utf-8');
            if (is_string($test)) {
                $value = $test;
            }
        }

        $length = strlen($value);
        $wasWriteSuccessful = true;
        $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, pack("ssssss", 0x204, 8 + $length, $this->row, $this->col, 0x0, $length));
        if ($value !='') {
            $wasWriteSuccessful = $wasWriteSuccessful && fwrite($this->filePointer, $value);
        }
        return $wasWriteSuccessful;
    }

    /**
     * Adds data to the currently opened writer.
     *
     * @param  array $dataRow Array containing data to be written.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param  array $metaData Array containing meta-data maps for individual cells, such as 'url'
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    protected function addRowToWriter(array $dataRow, array $metaData)
    {
        if ($this->row == 65536) {
            // Hit the limit. You should have chosen XLSX
            return;
        }

        $wasWriteSuccessful = true;

        $this->home();
        foreach ($dataRow as $cell) {
            if ($cell != '' && trim(ltrim($cell, '-'), '0123456789.') == '' /*similar to is_numeric without having PHPs regular quirkiness*/) {
                $wasWriteSuccessful = $wasWriteSuccessful && $this->number($cell);
            } else
            {
                $wasWriteSuccessful = $wasWriteSuccessful && $this->label($cell);
            }
            $this->right();
        }
        $this->down();

        if ($wasWriteSuccessful === false) {
            throw new IOException('Unable to write data');
        }

        if ($this->row % self::FLUSH_THRESHOLD === 0) {
            $this->globalFunctionsHelper->fflush($this->filePointer);
        }
    }

    /**
     * Writes the Excel End of File marker
     *
     * @see pack()
     * @return nothing
     */
    private function eofMarker()
    {
        fwrite($this->filePointer, pack("ss", 0x0A, 0x00));
    }

    /**
     * Closes the XLS streamer, preventing any additional writing.
     * If set, sets the headers and redirects output to the browser.
     *
     * @return void
     */
    protected function closeWriter()
    {
        if ($this->filePointer) {
            $this->eofMarker();

            $this->globalFunctionsHelper->fclose($this->filePointer);
        }

        $this->row = 0;
        $this->col = 0;
    }
}
