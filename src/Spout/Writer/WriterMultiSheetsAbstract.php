<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Creator\ManagerFactoryInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Common\Entity\Worksheet;
use Box\Spout\Writer\Common\Manager\WorkbookManagerInterface;
use Box\Spout\Writer\Exception\SheetNotFoundException;
use Box\Spout\Writer\Exception\WriterAlreadyOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;

/**
 * Class WriterMultiSheetsAbstract
 *
 * @abstract
 */
abstract class WriterMultiSheetsAbstract extends WriterAbstract
{
    /** @var ManagerFactoryInterface */
    private $managerFactory;

    /** @var WorkbookManagerInterface */
    private $workbookManager;

    /**
     * @param OptionsManagerInterface $optionsManager
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     * @param HelperFactory $helperFactory
     * @param ManagerFactoryInterface $managerFactory
     */
    public function __construct(
        OptionsManagerInterface $optionsManager,
        GlobalFunctionsHelper $globalFunctionsHelper,
        HelperFactory $helperFactory,
        ManagerFactoryInterface $managerFactory
    ) {
        parent::__construct($optionsManager, $globalFunctionsHelper, $helperFactory);
        $this->managerFactory = $managerFactory;
    }

    /**
     * Sets whether new sheets should be automatically created when the max rows limit per sheet is reached.
     * This must be set before opening the writer.
     *
     * @param bool $shouldCreateNewSheetsAutomatically Whether new sheets should be automatically created when the max rows limit per sheet is reached
     * @throws WriterAlreadyOpenedException If the writer was already opened
     * @return WriterMultiSheetsAbstract
     */
    public function setShouldCreateNewSheetsAutomatically($shouldCreateNewSheetsAutomatically)
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');

        $this->optionsManager->setOption(
            Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY,
            $shouldCreateNewSheetsAutomatically
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function openWriter()
    {
        if (!$this->workbookManager) {
            $this->workbookManager = $this->managerFactory->createWorkbookManager($this->optionsManager);
            $this->workbookManager->addNewSheetAndMakeItCurrent();
        }
    }

    /**
     * Returns all the workbook's sheets
     *
     * @throws WriterNotOpenedException If the writer has not been opened yet
     * @return Sheet[] All the workbook's sheets
     */
    public function getSheets()
    {
        $this->throwIfWorkbookIsNotAvailable();

        $externalSheets = [];
        $worksheets = $this->workbookManager->getWorksheets();

        /** @var Worksheet $worksheet */
        foreach ($worksheets as $worksheet) {
            $externalSheets[] = $worksheet->getExternalSheet();
        }

        return $externalSheets;
    }

    /**
     * Creates a new sheet and make it the current sheet. The data will now be written to this sheet.
     *
     * @throws IOException
     * @throws WriterNotOpenedException If the writer has not been opened yet
     * @return Sheet The created sheet
     */
    public function addNewSheetAndMakeItCurrent()
    {
        $this->throwIfWorkbookIsNotAvailable();
        $worksheet = $this->workbookManager->addNewSheetAndMakeItCurrent();

        return $worksheet->getExternalSheet();
    }

    /**
     * Returns the current sheet
     *
     * @throws WriterNotOpenedException If the writer has not been opened yet
     * @return Sheet The current sheet
     */
    public function getCurrentSheet()
    {
        $this->throwIfWorkbookIsNotAvailable();

        return $this->workbookManager->getCurrentWorksheet()->getExternalSheet();
    }

    /**
     * Sets the given sheet as the current one. New data will be written to this sheet.
     * The writing will resume where it stopped (i.e. data won't be truncated).
     *
     * @param Sheet $sheet The sheet to set as current
     * @throws SheetNotFoundException If the given sheet does not exist in the workbook
     * @throws WriterNotOpenedException If the writer has not been opened yet
     * @return void
     */
    public function setCurrentSheet($sheet)
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setCurrentSheet($sheet);
    }

    /**
     * @param float $width
     * @throws WriterAlreadyOpenedException
     */
    public function setDefaultColumnWidth(float $width)
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');
        $this->optionsManager->setOption(
            Options::DEFAULT_COLUMN_WIDTH,
            $width
        );
    }

    /**
     * @param float $height
     * @throws WriterAlreadyOpenedException
     */
    public function setDefaultRowHeight(float $height)
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');
        $this->optionsManager->setOption(
            Options::DEFAULT_ROW_HEIGHT,
            $height
        );
    }

    /**
     * @param float|null $width
     * @param array $columns One or more columns with this width
     * @throws WriterNotOpenedException
     */
    public function setColumnWidth($width, ...$columns)
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setColumnWidth($width, ...$columns);
    }

    /**
     * @param float $width The width to set
     * @param int $start First column index of the range
     * @param int $end Last column index of the range
     * @throws WriterNotOpenedException
     */
    public function setColumnWidthForRange(float $width, int $start, int $end)
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setColumnWidthForRange($width, $start, $end);
    }

    /**
     * Checks if the workbook has been created. Throws an exception if not created yet.
     *
     * @throws WriterNotOpenedException If the workbook is not created yet
     * @return void
     */
    protected function throwIfWorkbookIsNotAvailable()
    {
        if (empty($this->workbookManager) || !$this->workbookManager->getWorkbook()) {
            throw new WriterNotOpenedException('The writer must be opened before performing this action.');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\WriterException
     */
    protected function addRowToWriter(Row $row)
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->addRowToCurrentWorksheet($row);
    }

    /**
     * {@inheritdoc}
     */
    protected function closeWriter()
    {
        if ($this->workbookManager) {
            $this->workbookManager->close($this->filePointer);
        }
    }
}
