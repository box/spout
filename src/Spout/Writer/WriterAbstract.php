<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\SpoutException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use Box\Spout\Writer\Exception\WriterAlreadyOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;

/**
 * Class WriterAbstract
 *
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

    /** @var HelperFactory $helperFactory */
    protected $helperFactory;

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
     * @param HelperFactory $helperFactory
     */
    public function __construct(
        OptionsManagerInterface $optionsManager,
        StyleMerger $styleMerger,
        GlobalFunctionsHelper $globalFunctionsHelper,
        HelperFactory $helperFactory
    ) {
        $this->optionsManager = $optionsManager;
        $this->styleMerger = $styleMerger;
        $this->globalFunctionsHelper = $globalFunctionsHelper;
        $this->helperFactory = $helperFactory;
    }

    /**
     * Opens the streamer and makes it ready to accept data.
     *
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened
     * @return void
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
     * Sets the default styles for all rows added with "addRow"
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
     * {@inheritdoc}
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
     * @codeCoverageIgnore
     *
     * {@inheritdoc}
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
     * @throws \Box\Spout\Common\Exception\IOException If the pointer is not available
     * @return void
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
     * @throws \Box\Spout\Writer\Exception\WriterAlreadyOpenedException If the writer was already opened and must not be.
     * @return void
     */
    protected function throwIfWriterAlreadyOpened($message)
    {
        if ($this->isWriterOpened) {
            throw new WriterAlreadyOpenedException($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addRow(Row $row)
    {
        if ($this->isWriterOpened) {
            if ($row->hasCells()) {
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
     * {@inheritdoc}
     */
    public function addRows(array $dataRows)
    {
        foreach ($dataRows as $dataRow) {
            if (!$dataRow instanceof Row) {
                $this->closeAndAttemptToCleanupAllFiles();
                throw new InvalidArgumentException();
            }

            $this->addRow($dataRow);
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
        if ($defaultRowStyle === null) {
            return $this;
        }

        $mergedStyle = $this->styleMerger->merge($row->getStyle(), $defaultRowStyle);
        $row->setStyle($mergedStyle);
    }

    /**
     * Closes the writer. This will close the streamer as well, preventing new data
     * to be written to the file.
     *
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
            $fileSystemHelper = $this->helperFactory->createFileSystemHelper($outputFolderPath);
            $fileSystemHelper->deleteFile($this->outputFilePath);
        }
    }
}
