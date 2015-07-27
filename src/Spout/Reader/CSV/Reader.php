<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Reader\AbstractReader;
use Box\Spout\Common\Exception\IOException;

/**
 * Class Reader
 * This class provides support to read data from a CSV file.
 *
 * @package Box\Spout\Reader\CSV
 */
class Reader extends AbstractReader
{
    /** @var resource Pointer to the file to be written */
    protected $filePointer;

    /** @var SheetIterator To iterator over the CSV unique "sheet" */
    protected $sheetIterator;

    /** @var string Defines the character used to delimit fields (one character only) */
    protected $fieldDelimiter = ',';

    /** @var string Defines the character used to enclose fields (one character only) */
    protected $fieldEnclosure = '"';

    /**
     * Sets the field delimiter for the CSV.
     * Needs to be called before opening the reader.
     *
     * @param string $fieldDelimiter Character that delimits fields
     * @return Reader
     */
    public function setFieldDelimiter($fieldDelimiter)
    {
        $this->fieldDelimiter = $fieldDelimiter;
        return $this;
    }

    /**
     * Sets the field enclosure for the CSV.
     * Needs to be called before opening the reader.
     *
     * @param string $fieldEnclosure Character that enclose fields
     * @return Reader
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

        $this->sheetIterator = new SheetIterator($this->filePointer, $this->fieldDelimiter, $this->fieldEnclosure, $this->globalFunctionsHelper);
    }

    /**
     * Returns an iterator to iterate over sheets.
     *
     * @return SheetIterator To iterate over sheets
     */
    public function getConcreteSheetIterator()
    {
        return $this->sheetIterator;
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
