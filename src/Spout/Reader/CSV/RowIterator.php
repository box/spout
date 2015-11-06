<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Common\Helper\EncodingHelper;

/**
 * Class RowIterator
 * Iterate over CSV rows.
 *
 * @package Box\Spout\Reader\CSV
 */
class RowIterator implements IteratorInterface
{
    /**
     * If no value is given to stream_get_line(), it defaults to 8192 (which may be too low).
     * Alignement with other functions like fgets() is discussed here: https://bugs.php.net/bug.php?id=48421
     */
    const MAX_READ_BYTES_PER_LINE = 32768;

    /** @var resource Pointer to the CSV file to read */
    protected $filePointer;

    /** @var int Number of read rows */
    protected $numReadRows = 0;

    /** @var array|null Buffer used to store the row data, while checking if there are more rows to read */
    protected $rowDataBuffer = null;

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /** @var string Defines the character used to delimit fields (one character only) */
    protected $fieldDelimiter;

    /** @var string Defines the character used to enclose fields (one character only) */
    protected $fieldEnclosure;

    /** @var string Encoding of the CSV file to be read */
    protected $encoding;

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var \Box\Spout\Common\Helper\EncodingHelper Helper to work with different encodings */
    protected $encodingHelper;

    /** @var string End of line delimiter, encoded using the same encoding as the CSV */
    protected $encodedEOLDelimiter;

    /**
     * @param resource $filePointer Pointer to the CSV file to read
     * @param string $fieldDelimiter Character that delimits fields
     * @param string $fieldEnclosure Character that enclose fields
     * @param string $encoding Encoding of the CSV file to be read
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct($filePointer, $fieldDelimiter, $fieldEnclosure, $encoding, $globalFunctionsHelper)
    {
        $this->filePointer = $filePointer;
        $this->fieldDelimiter = $fieldDelimiter;
        $this->fieldEnclosure = $fieldEnclosure;
        $this->encoding = $encoding;
        $this->globalFunctionsHelper = $globalFunctionsHelper;

        $this->encodingHelper = new EncodingHelper($globalFunctionsHelper);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     */
    public function rewind()
    {
        $this->rewindAndSkipBom();

        $this->numReadRows = 0;
        $this->rowDataBuffer = null;

        $this->next();
    }

    /**
     * This rewinds and skips the BOM if inserted at the beginning of the file
     * by moving the file pointer after it, so that it is not read.
     *
     * @return void
     */
    protected function rewindAndSkipBom()
    {
        $byteOffsetToSkipBom = $this->encodingHelper->getBytesOffsetToSkipBOM($this->filePointer, $this->encoding);

        // sets the cursor after the BOM (0 means no BOM, so rewind it)
        $this->globalFunctionsHelper->fseek($this->filePointer, $byteOffsetToSkipBom);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return boolean
     */
    public function valid()
    {
        return ($this->filePointer && !$this->hasReachedEndOfFile);
    }

    /**
     * Move forward to next element. Empty rows are skipped.
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\EncodingConversionException If unable to convert data to UTF-8
     */
    public function next()
    {
        $this->hasReachedEndOfFile = $this->globalFunctionsHelper->feof($this->filePointer);

        if ($this->hasReachedEndOfFile) {
            return;
        }

        do {
            $lineData = false;
            $utf8EncodedLineData = $this->getNextUTF8EncodedLine();
            if ($utf8EncodedLineData !== false) {
                $lineData = $this->globalFunctionsHelper->str_getcsv($utf8EncodedLineData, $this->fieldDelimiter, $this->fieldEnclosure);
            }
            $hasNowReachedEndOfFile = $this->globalFunctionsHelper->feof($this->filePointer);
        } while (($lineData === false && !$hasNowReachedEndOfFile) || $this->isEmptyLine($lineData));

        if ($lineData !== false) {
            $this->rowDataBuffer = $lineData;
            $this->numReadRows++;
        } else {
            // If we reach this point, it means end of file was reached.
            // This happens when the last lines are empty lines.
            $this->hasReachedEndOfFile = $hasNowReachedEndOfFile;
        }
    }

    /**
     * Returns the next line, converted if necessary to UTF-8.
     * Neither fgets nor fgetcsv don't work with non UTF-8 data... so we need to do some things manually.
     *
     * @return string|false The next line for the current file pointer, encoded in UTF-8 or FALSE if nothing to read
     * @throws \Box\Spout\Common\Exception\EncodingConversionException If unable to convert data to UTF-8
     */
    protected function getNextUTF8EncodedLine()
    {
        // Read until the EOL delimiter or EOF is reached. The delimiter's encoding needs to match the CSV's encoding.
        $encodedEOLDelimiter = $this->getEncodedEOLDelimiter();
        $encodedLineData = $this->globalFunctionsHelper->stream_get_line($this->filePointer, self::MAX_READ_BYTES_PER_LINE, $encodedEOLDelimiter);

        // If the line could have been read, it can be converted to UTF-8
        $utf8EncodedLineData = ($encodedLineData !== false) ?
            $this->encodingHelper->attemptConversionToUTF8($encodedLineData, $this->encoding) :
            false;

        return $utf8EncodedLineData;
    }

    /**
     * Returns the end of line delimiter, encoded using the same encoding as the CSV.
     * The return value is cached.
     *
     * @return string
     */
    protected function getEncodedEOLDelimiter()
    {
        if (!isset($this->encodedEOLDelimiter)) {
            $this->encodedEOLDelimiter = $this->encodingHelper->attemptConversionFromUTF8("\n", $this->encoding);
        }

        return $this->encodedEOLDelimiter;
    }

    /**
     * @param array $lineData Array containing the cells value for the line
     * @return bool Whether the given line is empty
     */
    protected function isEmptyLine($lineData)
    {
        return (is_array($lineData) && count($lineData) === 1 && $lineData[0] === null);
    }

    /**
     * Return the current element from the buffer
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
        // do nothing
    }
}
