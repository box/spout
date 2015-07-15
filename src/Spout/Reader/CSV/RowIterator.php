<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Reader\IteratorInterface;

/**
 * Class RowIterator
 * Iterate over CSV rows.
 *
 * @package Box\Spout\Reader\CSV
 */
class RowIterator implements IteratorInterface
{
    const UTF8_BOM = "\xEF\xBB\xBF";

    /** @var resource Pointer to the CSV file to read */
    protected $filePointer;

    /** @var int Number of read rows */
    protected $numReadRows = 0;

    /** @var array|null Buffer used to store the row data, while checking if there are more rows to read */
    protected $rowDataBuffer = null;

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /** @var string Defines the character used to delimit fields (one character only) */
    protected $fieldDelimiter = ',';

    /** @var string Defines the character used to enclose fields (one character only) */
    protected $fieldEnclosure = '"';

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /**
     * @param resource $filePointer Pointer to the CSV file to read
     * @param string $fieldDelimiter Character that delimits fields
     * @param string $fieldEnclosure Character that enclose fields
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct($filePointer, $fieldDelimiter, $fieldEnclosure, $globalFunctionsHelper)
    {
        $this->filePointer = $filePointer;
        $this->fieldDelimiter = $fieldDelimiter;
        $this->fieldEnclosure = $fieldEnclosure;
        $this->globalFunctionsHelper = $globalFunctionsHelper;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     */
    public function rewind()
    {
        $this->rewindAndSkipUtf8Bom();

        $this->numReadRows = 0;
        $this->rowDataBuffer = null;

        $this->next();
    }

    /**
     * This rewinds and skips the UTF-8 BOM if inserted at the beginning of the file
     * by moving the file pointer after it, so that it is not read.
     *
     * @return void
     */
    protected function rewindAndSkipUtf8Bom()
    {
        $this->globalFunctionsHelper->rewind($this->filePointer);

        $hasUtf8Bom = ($this->globalFunctionsHelper->fgets($this->filePointer, 4) === self::UTF8_BOM);

        if ($hasUtf8Bom) {
            // we skip the 2 first bytes (so start from the 3rd byte)
            $this->globalFunctionsHelper->fseek($this->filePointer, 3);
        } else {
            // if no BOM, reset the pointer to read from the beginning
            $this->globalFunctionsHelper->fseek($this->filePointer, 0);
        }
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
     */
    public function next()
    {
        $lineData = null;
        $this->hasReachedEndOfFile = feof($this->filePointer);

        if (!$this->hasReachedEndOfFile) {
            do {
               $lineData = $this->globalFunctionsHelper->fgetcsv($this->filePointer, 0, $this->fieldDelimiter, $this->fieldEnclosure);
           } while ($lineData && $this->isEmptyLine($lineData));

            if ($lineData !== null) {
                $this->rowDataBuffer = $lineData;
                $this->numReadRows++;
            }
        }
    }

    /**
     * @param array $lineData Array containing the cells value for the line
     * @return bool Whether the given line is empty
     */
    protected function isEmptyLine($lineData)
    {
        return (count($lineData) === 1 && $lineData[0] === null);
    }

    /**
     * Return the current element from the buffer
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return array
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
