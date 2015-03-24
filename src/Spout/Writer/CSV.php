<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\IOException;

/**
 * Class CSV
 * This class provides support to write data to CSV files
 *
 * @package Box\Spout\Writer
 */
class CSV extends AbstractWriter
{
    /** Number of rows to write before flushing */
    const FLUSH_THRESHOLD = 500;
    const UTF8_BOM = "\xEF\xBB\xBF";

    /** @var string Content-Type value for the header */
    protected static $headerContentType = 'text/csv; charset=UTF-8';

    /** @var string Defines the character used to delimit fields (one character only) */
    protected $fieldDelimiter = ',';

    /** @var string Defines the character used to enclose fields (one character only) */
    protected $fieldEnclosure = '"';

    /** @var int */
    protected $lastWrittenRowIndex = 0;

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
     * Opens the CSV streamer and makes it ready to accept data.
     *
     * @return void
     */
    protected function openWriter()
    {
        // Adds UTF-8 BOM for Unicode compatibility
        $this->globalFunctionsHelper->fputs($this->filePointer, self::UTF8_BOM);
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
        $wasWriteSuccessful = fputcsv($this->filePointer, $dataRow, $this->fieldDelimiter, $this->fieldEnclosure);
        if ($wasWriteSuccessful === false) {
            throw new IOException('Unable to write data');
        }

        $this->lastWrittenRowIndex++;
        if ($this->lastWrittenRowIndex % self::FLUSH_THRESHOLD === 0) {
            $this->globalFunctionsHelper->fflush($this->filePointer);
        }
    }

    /**
     * Closes the CSV streamer, preventing any additional writing.
     * If set, sets the headers and redirects output to the browser.
     *
     * @return void
     */
    protected function closeWriter()
    {
        if ($this->filePointer) {
            $this->globalFunctionsHelper->fclose($this->filePointer);
        }

        $this->lastWrittenRowIndex = 0;
    }
}
