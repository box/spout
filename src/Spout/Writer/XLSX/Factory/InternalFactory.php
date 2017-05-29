<?php

namespace Box\Spout\Writer\XLSX\Factory;

use Box\Spout\Common\Escaper;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Options;
use Box\Spout\Writer\Factory\EntityFactory;
use Box\Spout\Writer\Factory\InternalFactoryInterface;
use Box\Spout\Writer\Factory\WorkbookFactory;
use Box\Spout\Writer\Factory\WorksheetFactory;
use Box\Spout\Writer\XLSX\Helper\FileSystemHelper;
use Box\Spout\Writer\XLSX\Helper\SharedStringsHelper;
use Box\Spout\Writer\XLSX\Helper\StyleHelper;
use Box\Spout\Writer\XLSX\Manager\WorkbookManager;
use Box\Spout\Writer\XLSX\Manager\WorksheetManager;

/**
 * Class InternalFactory
 * Factory for all useful types of objects needed by the XLSX Writer
 *
 * @package Box\Spout\Writer\XLSX\Factory
 */
class InternalFactory implements InternalFactoryInterface
{
    /** @var EntityFactory */
    private $entityFactory;

    /**
     * InternalFactory constructor.
     *
     * @param EntityFactory $entityFactory
     */
    public function __construct(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return WorkbookManager
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager)
    {
        $workbook = $this->entityFactory->createWorkbook();

        $fileSystemHelper = $this->createFileSystemHelper($optionsManager);
        $fileSystemHelper->createBaseFilesAndFolders();

        $xlFolder = $fileSystemHelper->getXlFolder();
        $sharedStringsHelper = $this->createSharedStringsHelper($xlFolder);

        $styleHelper = $this->createStyleHelper($optionsManager);

        $worksheetManager = $this->createWorksheetManager($optionsManager, $sharedStringsHelper, $styleHelper);

        return new WorkbookManager($workbook, $optionsManager, $worksheetManager, $styleHelper, $fileSystemHelper, $this->entityFactory);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @param SharedStringsHelper $sharedStringsHelper
     * @param StyleHelper $styleHelper
     * @return WorksheetManager
     */
    private function createWorksheetManager(
        OptionsManagerInterface $optionsManager,
        SharedStringsHelper $sharedStringsHelper,
        StyleHelper $styleHelper
    )
    {
        $stringsEscaper = $this->createStringsEscaper();
        $stringsHelper = $this->createStringHelper();

        return new WorksheetManager($optionsManager, $sharedStringsHelper, $styleHelper, $stringsEscaper, $stringsHelper);
    }

    /**
     * @param string $xlFolder Path to the "xl" folder
     * @return SharedStringsHelper
     */
    private function createSharedStringsHelper($xlFolder)
    {
        return new SharedStringsHelper($xlFolder);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return FileSystemHelper
     */
    private function createFileSystemHelper(OptionsManagerInterface $optionsManager)
    {
        $tempFolder = $optionsManager->getOption(Options::TEMP_FOLDER);
        return new FileSystemHelper($tempFolder);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return StyleHelper
     */
    private function createStyleHelper(OptionsManagerInterface $optionsManager)
    {
        $defaultRowStyle = $optionsManager->getOption(Options::DEFAULT_ROW_STYLE);
        return new StyleHelper($defaultRowStyle);
    }

    /**
     * @return Escaper\XLSX
     */
    private function createStringsEscaper()
    {
        return Escaper\XLSX::getInstance();
    }

    /**
     * @return StringHelper
     */
    private function createStringHelper()
    {
        return new StringHelper();
    }
}