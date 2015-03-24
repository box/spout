<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;

/**
 * Class AbstractWriter
 *
 * @package Box\Spout\Writer
 * @abstract
 */
abstract class AbstractWriter implements WriterInterface
{
    /** @var string Path to the output file */
    protected $outputFilePath;

    /** @var resource Pointer to the file/stream we will write to */
    protected $filePointer;

    /** @var bool Indicates whether the writer has been opened or not */
    protected $isWriterOpened = false;

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var string Content-Type value for the header - to be defined by child class */
    protected static $headerContentType;

    /**
     * Opens the streamer and makes it ready to accept data.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened
     */
    abstract protected function openWriter();

    /**
     * Adds data to the currently opened writer.
     *
     * @param  array $dataRow Array containing data to be streamed.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param  array $metaData Array containing meta-data maps for individual cells, such as 'url'
     * @return void
     */
    abstract protected function addRowToWriter(array $dataRow, array $metaData);

    /**
     * Closes the streamer, preventing any additional writing.
     *
     * @return void
     */
    abstract protected function closeWriter();

    /**
     * @param $globalFunctionsHelper
     * @return AbstractWriter
     */
    public function setGlobalFunctionsHelper($globalFunctionsHelper)
    {
        $this->globalFunctionsHelper = $globalFunctionsHelper;
        return $this;
    }

    /**
     * Inits the writer and opens it to accept data.
     * By using this method, the data will be written to a file.
     *
     * @param  string $outputFilePath Path of the output file that will contain the data
     * @return \Box\Spout\Writer\AbstractWriter
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened or if the given path is not writable
     */
    public function openToFile($outputFilePath)
    {
        $this->outputFilePath = $outputFilePath;

        $this->filePointer = $this->globalFunctionsHelper->fopen($this->outputFilePath, 'wb+');
        $this->throwIfFilePointerIsNotAvailable();

        $this->openWriter();
        $this->isWriterOpened = true;

        return $this;
    }

    /**
     * Inits the writer and opens it to accept data.
     * By using this method, the data will be outputted directly to the browser.
     *
     * @param  string $outputFileName Name of the output file that will contain the data. If a path is passed in, only the file name will be kept
     * @return \Box\Spout\Writer\AbstractWriter
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened
     */
    public function openToBrowser($outputFileName)
    {
        $this->outputFilePath = $this->globalFunctionsHelper->basename($outputFileName);

        $this->filePointer = $this->globalFunctionsHelper->fopen('php://output', 'w');
        $this->throwIfFilePointerIsNotAvailable();

        // Set headers
        $this->globalFunctionsHelper->header('Content-Type: ' . static::$headerContentType);
        $this->globalFunctionsHelper->header('Content-Disposition: attachment; filename="' . $this->outputFilePath . '"');

        /*
         * When forcing the download of a file over SSL,IE8 and lower browsers fail
         * if the Cache-Control and Pragma headers are not set.
         *
         * @see http://support.microsoft.com/KB/323308
         * @see https://github.com/liuggio/ExcelBundle/issues/45
         */
        $this->globalFunctionsHelper->header('Cache-Control: max-age=0');
        $this->globalFunctionsHelper->header('Pragma: public');

        $this->openWriter();
        $this->isWriterOpened = true;

        return $this;
    }

    /**
     * Checks if the pointer to the file/stream to write to is available.
     * Will throw an exception if not available.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the pointer is not available
     */
    protected function throwIfFilePointerIsNotAvailable()
    {
        if (!$this->filePointer) {
            throw new IOException('File pointer has not be opened');
        }
    }

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param  array $dataRow Array containing data to be streamed.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param  array $metaData Array containing meta-data maps for individual cells, such as 'url'
     *
     * @return \Box\Spout\Writer\AbstractWriter
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException If this function is called before opening the writer
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    public function addRow(array $dataRow, array $metaData = array())
    {
        if ($this->isWriterOpened) {
            $this->addRowToWriter($dataRow, $metaData);
        } else {
            throw new WriterNotOpenedException('The writer needs to be opened before adding row.');
        }

        return $this;
    }

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param  array $dataRows Array of array containing data to be streamed.
     *          Example $dataRow = [
     *              ['data11', 12, , '', 'data13'],
     *              ['data21', 'data22', null],
     *          ];
     *
     * @return \Box\Spout\Writer\AbstractWriter
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If the input param is not valid
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException If this function is called before opening the writer
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    public function addRows(array $dataRows)
    {
        if (!empty($dataRows)) {
            if (!is_array($dataRows[0])) {
                throw new InvalidArgumentException('The input should be an array of arrays');
            }

            foreach ($dataRows as $dataRow) {
                $this->addRow($dataRow);
            }
        }

        return $this;
    }

    /**
     * Closes the writer. This will close the streamer as well, preventing new data
     * to be written to the file.
     *
     * @return void
     */
    public function close()
    {
        $this->closeWriter();
        $this->isWriterOpened = false;
    }
}

