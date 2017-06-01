<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\SpoutException;
use Box\Spout\Common\Helper\FileSystemHelper;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use Box\Spout\Writer\Exception\WriterAlreadyOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;

/**
 * Class WriterAbstract
 *
 * @package Box\Spout\Writer
 * @abstract
 */
abstract class WriterAbstract implements WriterInterface
{
    /** @var string Path to the output file */
    protected $outputFilePath;

    /** @var resource Pointer to the file/stream we will write to */
    protected $filePointer;

    /** @var bool Indicates whether the writer has been opened or not */
    protected $isWriterOpened = false;

    /** @var GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var OptionsManagerInterface Writer options manager */
    protected $optionsManager;

    /** @var StyleMerger Helps merge styles together */
    protected $styleMerger;

    /** @var string Content-Type value for the header - to be defined by child class */
    protected static $headerContentType;

    /**
     * @param OptionsManagerInterface $optionsManager
     * @param StyleMerger $styleMerger
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct(
        OptionsManagerInterface $optionsManager,
        StyleMerger $styleMerger,
        GlobalFunctionsHelper $globalFunctionsHelper)
    {
        $this->optionsManager = $optionsManager;
        $this->styleMerger = $styleMerger;
        $this->globalFunctionsHelper = $globalFunctionsHelper;
    }

    /**
     * Opens the streamer and makes it ready to accept data.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened
     */
    abstract protected function openWriter();

    /**
     * Adds a row to the currently opened writer.
     *
     * @param Row $row The row containing cells and styles
     * @return void
     */
    abstract protected function addRowToWriter(Row $row);

    /**
     * Closes the streamer, preventing any additional writing.
     *
     * @return void
     */
    abstract protected function closeWriter();

    /**
     * Sets the default styles for all rows added with "addRow".
     * Overriding the default style instead of using "addRowWithStyle" improves performance by 20%.
     * @see https://github.com/box/spout/issues/272
     *
     * @param Style $defaultStyle
     * @return WriterAbstract
     */
    public function setDefaultRowStyle($defaultStyle)
    {
        $this->optionsManager->setOption(Options::DEFAULT_ROW_STYLE, $defaultStyle);
        return $this;
    }

    /**
     * Inits the writer and opens it to accept data.
     * By using this method, the data will be written to a file.
     *
     * @api
     * @param  string $outputFilePath Path of the output file that will contain the data
     * @return WriterAbstract
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
     * @codeCoverageIgnore
     *
     * @api
     * @param  string $outputFileName Name of the output file that will contain the data. If a path is passed in, only the file name will be kept
     * @return WriterAbstract
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened
     */
    public function openToBrowser($outputFileName)
    {
        $this->outputFilePath = $this->globalFunctionsHelper->basename($outputFileName);

        $this->filePointer = $this->globalFunctionsHelper->fopen('php://output', 'w');
        $this->throwIfFilePointerIsNotAvailable();

        // Clear any previous output (otherwise the generated file will be corrupted)
        // @see https://github.com/box/spout/issues/241
        $this->globalFunctionsHelper->ob_end_clean();

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
     * Checks if the writer has already been opened, since some actions must be done before it gets opened.
     * Throws an exception if already opened.
     *
     * @param string $message Error message
     * @return void
     * @throws \Box\Spout\Writer\Exception\WriterAlreadyOpenedException If the writer was already opened and must not be.
     */
    protected function throwIfWriterAlreadyOpened($message)
    {
        if ($this->isWriterOpened) {
            throw new WriterAlreadyOpenedException($message);
        }
    }

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param array|\Box\Spout\Writer\Common\Entity\Row $row The row to be appended to the stream
     * @return WriterInterface
     * @internal param array $row Array containing data to be streamed.
     *          Example $row= ['data1', 1234, null, '', 'data5'];
     * @internal param \Box\Spout\Writer\Common\Entity\Row $row A Row object with cells and styles
     *          Example $row = (new Row())->addCell('data1');
     *
     * @throws SpoutException If anything else goes wrong while writing data
     * @throws WriterNotOpenedException If this function is called before opening the writer
     *
     * @api
     */
    public function addRow($row)
    {
        if (!is_array($row) && !$row instanceof Row) {
            throw new InvalidArgumentException('addRow accepts an array with scalar values or a Row object');
        }

        if (is_array($row) && !empty($row)) {
            $row = $this->createRowFromArray($row, null);
        }

        if ($this->isWriterOpened) {
            if (!empty($row)) {
                try {
                    $this->applyDefaultRowStyle($row);
                    $this->addRowToWriter($row);
                } catch (SpoutException $e) {
                    // if an exception occurs while writing data,
                    // close the writer and remove all files created so far.
                    $this->closeAndAttemptToCleanupAllFiles();
                    // re-throw the exception to alert developers of the error
                    throw $e;
                }
            }
        } else {
            throw new WriterNotOpenedException('The writer needs to be opened before adding row.');
        }
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @api
     */
    public function withRow(\Closure $callback)
    {
        return $this->addRow($callback(new Row()));
    }

    /**
     * Write given data to the output and apply the given style.
     * @see addRow
     *
     * @param array|\Box\Spout\Writer\Common\Entity\Row $row The row to be appended to the stream
     * @param Style $style Style to be applied to the row.
     * @return WriterInterface
     * @internal param array $row Array containing data to be streamed.
     *          Example $row= ['data1', 1234, null, '', 'data5'];
     * @internal param \Box\Spout\Writer\Common\Entity\Row $row A Row object with cells and styles
     *          Example $row = (new Row())->addCell('data1');
     * @api
     * @throws InvalidArgumentException If the input param is not valid
     */
    public function addRowWithStyle($row, $style)
    {
        if (!is_array($row) && !$row instanceof Row) {
            throw new InvalidArgumentException('addRowWithStyle accepts an array with scalar values or a Row object');
        }

        if (!$style instanceof Style) {
            throw new InvalidArgumentException('The "$style" argument must be a Style instance and cannot be NULL.');
        }

        if (is_array($row)) {
            $row = $this->createRowFromArray($row, $style);
        }

        $this->addRow($row);
        return $this;
    }

    /**
     * @param array $dataRows
     * @param Style|null $style
     * @return Row
     */
    protected function createRowFromArray(array $dataRows, Style $style = null)
    {
        $row = (new Row())->setCells(array_map(function ($value) {
            if ($value instanceof Cell) {
                return $value;
            }
            return new Cell($value);
        }, $dataRows));

        if ($style !== null) {
            $row->setStyle($style);
        }

        return $row;
    }

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @api
     * @param  array $dataRows Array of array containing data to be streamed.
     *                         If a row is empty, it won't be added (i.e. not even as a blank row)
     *                         Example: $dataRows = [
     *                             ['data11', 12, , '', 'data13'],
     *                             ['data21', 'data22', null, false],
     *                         ];
     * @return WriterAbstract
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If the input param is not valid
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException If this function is called before opening the writer
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    public function addRows(array $dataRows)
    {
        if (!empty($dataRows)) {
            $firstRow = reset($dataRows);
            if (!is_array($firstRow) && !$firstRow instanceof Row) {
                throw new InvalidArgumentException('The input should be an array of arrays or row objects');
            }
            foreach ($dataRows as $dataRow) {
                $this->addRow($dataRow);
            }
        }
        return $this;
    }

    /**
     * Write given data to the output and apply the given style.
     * @see addRows
     *
     * @api
     * @param array $dataRows Array of array containing data to be streamed.
     * @param Style $style Style to be applied to the rows.
     * @return WriterAbstract
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If the input param is not valid
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException If this function is called before opening the writer
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    public function addRowsWithStyle(array $dataRows, $style)
    {
        if (!$style instanceof Style) {
            throw new InvalidArgumentException('The "$style" argument must be a Style instance and cannot be NULL.');
        }

        foreach($dataRows as $row) {

            if (is_array($row)) {
                $row = $this->createRowFromArray($row, $style);
            } elseif ($row instanceof Row) {
                $row->setStyle($style);
            } else {
                throw new InvalidArgumentException();
            }

            $this->addRow($row);
        }

        return $this;
    }

    /**
     * @TODO: Move this into styleMerger
     *
     * @param Row $row
     * @return $this
     */
    private function applyDefaultRowStyle(Row $row)
    {
        $defaultRowStyle = $this->optionsManager->getOption(Options::DEFAULT_ROW_STYLE);
        if (null === $defaultRowStyle) {
            return $this;
        }
        $mergedStyle = $this->styleMerger->merge($row->getStyle(), $defaultRowStyle);
        $row->setStyle($mergedStyle);
    }

    /**
     * Closes the writer. This will close the streamer as well, preventing new data
     * to be written to the file.
     *
     * @api
     * @return void
     */
    public function close()
    {
        if (!$this->isWriterOpened) {
            return;
        }

        $this->closeWriter();

        if (is_resource($this->filePointer)) {
            $this->globalFunctionsHelper->fclose($this->filePointer);
        }

        $this->isWriterOpened = false;
    }

    /**
     * Closes the writer and attempts to cleanup all files that were
     * created during the writing process (temp files & final file).
     *
     * @return void
     */
    private function closeAndAttemptToCleanupAllFiles()
    {
        // close the writer, which should remove all temp files
        $this->close();

        // remove output file if it was created
        if ($this->globalFunctionsHelper->file_exists($this->outputFilePath)) {
            $outputFolderPath = dirname($this->outputFilePath);
            $fileSystemHelper = new FileSystemHelper($outputFolderPath);
            $fileSystemHelper->deleteFile($this->outputFilePath);
        }
    }
}
