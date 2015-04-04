<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Exception\IOException;

/**
 * Class CSV
 * This class provides support to read data from a CSV file.
 *
 * @package Box\Spout\Reader
 */
class CSV extends AbstractReader
{
    const UTF8_BOM = "\xEF\xBB\xBF";

    /** @var resource Pointer to the file to be written */
    protected $filePointer;

    /** @var string Defines the character used to delimit fields (one character only) */
    protected $fieldDelimiter = ',';

    /** @var string Defines the character used to enclose fields (one character only) */
    protected $fieldEnclosure = '"';

    /**
     * Sets the field delimiter for the CSV
     *
     * @param string $fieldDelimiter Character that delimits fields
     * @return CSV
     */
    public function setFieldDelimiter($fieldDelimiter)
    {
        $this->fieldDelimiter = $fieldDelimiter;
        return $this;
    }

    /**
     * Sets the field enclosure for the CSV
     *
     * @param string $fieldEnclosure Character that enclose fields
     * @return CSV
     */
    public function setFieldEnclosure($fieldEnclosure)
    {
        $this->fieldEnclosure = $fieldEnclosure;
        return $this;
    }

    /**
     * Opens the file at the given path to make it ready to be read.
     * The file must be UTF-8 encoded.
     * @TODO add encoding detection/conversion
     *
     * @param  string $filePath Path of the CSV file to be read
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException
     */
    protected function openReader($filePath)
    {
        $this->filePointer = $this->globalFunctionsHelper->fopen($filePath, 'r');
        if (!$this->filePointer) {
            throw new IOException('Could not open file ' . $filePath . ' for reading.');
        }

        $this->skipUtf8Bom();
    }

    /**
     * This skips the UTF-8 BOM if inserted at the beginning of the file
     * by moving the file pointer after it, so that it is not read.
     *
     * @return void
     */
    protected function skipUtf8Bom()
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
     * Reads and returns next row if available.
     * Empty rows are skipped.
     *
     * @return array|null Array that contains the data for the read row or null at the end of the file
     */
    protected function read()
    {
        $lineData = null;

        if ($this->filePointer) {
            do {
                $lineData = $this->globalFunctionsHelper->fgetcsv($this->filePointer, 0, $this->fieldDelimiter, $this->fieldEnclosure);
            } while ($lineData && $this->isEmptyLine($lineData));
        }

        // When reaching the end of the file, return null instead of false
        return ($lineData !== false) ? $lineData : null;
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
     * Closes the reader. To be used after reading the file.
     *
     * @return void
     */
    protected function closeReader()
    {
        if ($this->filePointer) {
            $this->globalFunctionsHelper->fclose($this->filePointer);
        }
    }
}
