<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;

/**
 * Class AbstractReader
 *
 * @package Box\Spout\Reader
 * @abstract
 */
abstract class AbstractReader implements ReaderInterface
{
    /** @var bool Indicates whether the stream is currently open */
    protected $isStreamOpened = false;

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
     * Returns an iterator to iterate over sheets.
     *
     * @return \Iterator To iterate over sheets
     */
    abstract public function getConcreteSheetIterator();

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
     * @api
     * @param  string $filePath Path of the file to be read
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the file at the given path does not exist, is not readable or is corrupted
     */
    public function open($filePath)
    {
        if (!$this->isPhpStream($filePath)) {
            // we skip the checks if the provided file path points to a PHP stream
            if (!$this->globalFunctionsHelper->file_exists($filePath)) {
                throw new IOException("Could not open $filePath for reading! File does not exist.");
            } else if (!$this->globalFunctionsHelper->is_readable($filePath)) {
                throw new IOException("Could not open $filePath for reading! File is not readable.");
            }
        }

        try {
            // Need to use realpath to fix "Can't open file" on some Windows setup
            $fileRealPath = realpath($filePath);
            $this->openReader($fileRealPath);
            $this->isStreamOpened = true;
        } catch (\Exception $exception) {
            throw new IOException("Could not open $filePath for reading! ({$exception->getMessage()})");
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
     * Returns an iterator to iterate over sheets.
     *
     * @api
     * @return \Iterator To iterate over sheets
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException If called before opening the reader
     */
    public function getSheetIterator()
    {
        if (!$this->isStreamOpened) {
            throw new ReaderNotOpenedException('Reader should be opened first.');
        }

        return $this->getConcreteSheetIterator();
    }

    /**
     * Closes the reader, preventing any additional reading
     *
     * @api
     * @return void
     */
    public function close()
    {
        if ($this->isStreamOpened) {
            $this->closeReader();

            $sheetIterator = $this->getConcreteSheetIterator();
            if ($sheetIterator) {
                $sheetIterator->end();
            }

            $this->isStreamOpened = false;
        }
    }
}
