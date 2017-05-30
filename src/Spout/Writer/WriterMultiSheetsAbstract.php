<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Worksheet;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use Box\Spout\Writer\Exception\SheetNotFoundException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Box\Spout\Writer\Common\Creator\InternalFactoryInterface;
use Box\Spout\Writer\Common\Manager\WorkbookManagerInterface;

/**
 * Class WriterMultiSheetsAbstract
 *
 * @package Box\Spout\Writer
 * @abstract
 */
abstract class WriterMultiSheetsAbstract extends WriterAbstract
{

    /** @var InternalFactoryInterface */
    private $internalFactory;

    /** @var WorkbookManagerInterface */
    private $workbookManager;

    /**
     * @param OptionsManagerInterface $optionsManager
     * @param StyleMerger $styleMerger
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     * @param InternalFactoryInterface $internalFactory
     */
    public function __construct(
        OptionsManagerInterface $optionsManager,
        StyleMerger $styleMerger,
        GlobalFunctionsHelper $globalFunctionsHelper,
        InternalFactoryInterface $internalFactory)
    {
        parent::__construct($optionsManager, $styleMerger, $globalFunctionsHelper);
        $this->internalFactory = $internalFactory;
    }

    /**
     * Sets whether new sheets should be automatically created when the max rows limit per sheet is reached.
     * This must be set before opening the writer.
     *
     * @api
     * @param bool $shouldCreateNewSheetsAutomatically Whether new sheets should be automatically created when the max rows limit per sheet is reached
     * @return WriterMultiSheetsAbstract
     * @throws WriterAlreadyOpenedException If the writer was already opened
     */
    public function setShouldCreateNewSheetsAutomatically($shouldCreateNewSheetsAutomatically)
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');

        $this->optionsManager->setOption(Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY, $shouldCreateNewSheetsAutomatically);
        return $this;
    }

    /**
     * Configures the write and sets the current sheet pointer to a new sheet.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If unable to open the file for writing
     */
    protected function openWriter()
    {
        if (!$this->workbookManager) {
            $this->workbookManager = $this->internalFactory->createWorkbookManager($this->optionsManager);
            $this->workbookManager->addNewSheetAndMakeItCurrent();
        }
    }

    /**
     * Returns all the workbook's sheets
     *
     * @api
     * @return Sheet[] All the workbook's sheets
     * @throws WriterNotOpenedException If the writer has not been opened yet
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
     * @api
     * @return Sheet The created sheet
     * @throws WriterNotOpenedException If the writer has not been opened yet
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
     * @api
     * @return Sheet The current sheet
     * @throws WriterNotOpenedException If the writer has not been opened yet
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
     * @api
     * @param Sheet $sheet The sheet to set as current
     * @return void
     * @throws WriterNotOpenedException If the writer has not been opened yet
     * @throws SheetNotFoundException If the given sheet does not exist in the workbook
     */
    public function setCurrentSheet($sheet)
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setCurrentSheet($sheet);
    }

    /**
     * Checks if the workbook has been created. Throws an exception if not created yet.
     *
     * @return void
     * @throws WriterNotOpenedException If the workbook is not created yet
     */
    protected function throwIfWorkbookIsNotAvailable()
    {
        if (!$this->workbookManager->getWorkbook()) {
            throw new WriterNotOpenedException('The writer must be opened before performing this action.');
        }
    }

    /**
     * Adds data to the currently opened writer.
     * If shouldCreateNewSheetsAutomatically option is set to true, it will handle pagination
     * with the creation of new worksheets if one worksheet has reached its maximum capicity.
     *
     * @param Row $row
     * @return void
     * @throws WriterNotOpenedException If the book is not created yet
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    protected function addRowToWriter(Row $row)
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->addRowToCurrentWorksheet($row);
    }

    /**
     * Closes the writer, preventing any additional writing.
     *
     * @return void
     */
    protected function closeWriter()
    {
        if ($this->workbookManager) {
            $this->workbookManager->close($this->filePointer);
        }
    }
}

