<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Reader\Exception\EndOfFileReachedException;

/**
 * Class AbstractReader
 *
 * @package Box\Spout\Reader
 * @abstract
 */
abstract class AbstractReader implements ReaderInterface
{
    /** @var int Used to keep track of the row number */
    protected $currentRowIndex = 0;

    /** @var bool Indicates whether the stream is currently open */
    protected $isStreamOpened = false;

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /** @var array Buffer used to store the row data, while checking if there are more rows to read */
    protected $rowDataBuffer = null;

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /**
     * Opens the file at the given file path to make it ready to be read
     *
     * @param  string $filePath Path of the file to be read
     * @return void
     */
    abstract protected function openReader($filePath);

    /**
     * Reads and returns next row if available.
     *
     * @return array|null Array that contains the data for the read row or null at the end of the file
     */
    abstract protected function read();

    /**
     * Closes the reader. To be used after reading the file.
     *
     * @return AbstractReader
     */
    abstract protected function closeReader();

    /**
     * @param $globalFunctionsHelper
     * @return AbstractReader
     */
    public function setGlobalFunctionsHelper($globalFunctionsHelper)
    {
        $this->globalFunctionsHelper = $globalFunctionsHelper;
        return $this;
    }

    /**
     * Prepares the reader to read the given file. It also makes sure
     * that the file exists and is readable.
     *
     * @param  string $filePath Path of the file to be read
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the file at the given path does not exist, is not readable or is corrupted
     */
    public function open($filePath)
    {
        if (!$this->isPhpStream($filePath)) {
            // we skip the checks if the provided file path points to a PHP stream
            if (!$this->globalFunctionsHelper->file_exists($filePath)) {
                throw new IOException('Could not open ' . $filePath . ' for reading! File does not exist.');
            } else if (!$this->globalFunctionsHelper->is_readable($filePath)) {
                throw new IOException('Could not open ' . $filePath . ' for reading! File is not readable.');
            }
        }

        $this->currentRowIndex = 0;
        $this->hasReachedEndOfFile = false;

        try {
            $this->openReader($filePath);
            $this->isStreamOpened = true;
        } catch (\Exception $exception) {
            throw new IOException('Could not open ' . $filePath . ' for reading! (' . $exception->getMessage() . ')');
        }
    }

    /**
     * Checks if a path is a PHP stream (like php://output, php://memory, ...)
     *
     * @param string $filePath Path of the file to be read
     * @return bool Whether the given path maps to a PHP stream
     */
    protected function isPhpStream($filePath)
    {
        return (strpos($filePath, 'php://') === 0);
    }

    /**
     * Returns whether all rows have been read (i.e. if we are at the end of the file).
     * To know if the end of file has been reached, it uses a buffer. If the buffer is
     * empty (meaning, nothing has been read or previous read line has been consumed), then
     * it reads the next line, store it in the buffer for the next time or flip a variable if
     * the end of file has been reached.
     *
     * @return bool Whether all rows have been read (i.e. if we are at the end of the file)
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException If the stream was not opened first
     */
    public function hasNextRow()
    {
        if (!$this->isStreamOpened) {
            throw new ReaderNotOpenedException('Stream should be opened first.');
        }

        if ($this->hasReachedEndOfFile) {
            return false;
        }

        // if the buffer contains unprocessed row
        if (!$this->isRowDataBufferEmpty()) {
            return true;
        }

        // otherwise, try to read the next line line, and store it in the buffer
        $this->rowDataBuffer = $this->read();

        // if the buffer is still empty after reading a row, it means end of file was reached
        $this->hasReachedEndOfFile = $this->isRowDataBufferEmpty();

        return (!$this->hasReachedEndOfFile);
    }

    /**
     * Returns next row if available. The row is either retrieved from the buffer if it is not empty or fetched by
     * actually reading the file.
     *
     * @return array Array that contains the data for the read row
     * @throws \Box\Spout\Common\Exception\IOException If the stream was not opened first
     * @throws \Box\Spout\Reader\Exception\EndOfFileReachedException
     */
    public function nextRow()
    {
        if (!$this->hasNextRow()) {
            throw new EndOfFileReachedException('End of file was reached. Cannot read more rows.');
        }

        // Get data from buffer (if the buffer was empty, it was filled by the call to hasNextRow())
        $rowData = $this->rowDataBuffer;

        // empty buffer to mark the row as consumed
        $this->emptyRowDataBuffer();

        $this->currentRowIndex++;

        return $rowData;
    }

    /**
     * Returns whether the buffer where the row data is stored is empty
     *
     * @return bool
     */
    protected function isRowDataBufferEmpty()
    {
        return ($this->rowDataBuffer === null);
    }

    /**
     * Empty the buffer that stores row data
     *
     * @return void
     */
    protected function emptyRowDataBuffer()
    {
        $this->rowDataBuffer = null;
    }

    /**
     * Closes the reader, preventing any additional reading
     *
     * @return void
     */
    public function close()
    {
        if ($this->isStreamOpened) {
            $this->closeReader();
            $this->isStreamOpened = false;
        }
    }
}
