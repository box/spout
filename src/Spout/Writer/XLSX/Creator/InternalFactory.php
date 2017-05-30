<?php

namespace Box\Spout\Writer\XLSX\Creator;

use Box\Spout\Common\Escaper;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Creator\InternalFactoryInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\XLSX\Helper\FileSystemHelper;
use Box\Spout\Writer\XLSX\Helper\SharedStringsHelper;
use Box\Spout\Writer\XLSX\Manager\Style\StyleManager;
use Box\Spout\Writer\XLSX\Manager\Style\StyleRegistry;
use Box\Spout\Writer\XLSX\Manager\WorkbookManager;
use Box\Spout\Writer\XLSX\Manager\WorksheetManager;

/**
 * Class InternalFactory
 * Factory for all useful types of objects needed by the XLSX Writer
 *
 * @package Box\Spout\Writer\XLSX\Creator
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

        $styleManager = $this->createStyleManager($optionsManager);
        $worksheetManager = $this->createWorksheetManager($optionsManager, $styleManager, $sharedStringsHelper);

        return new WorkbookManager($workbook, $optionsManager, $worksheetManager, $styleManager, $fileSystemHelper, $this->entityFactory);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @param StyleManager $styleManager
     * @param SharedStringsHelper $sharedStringsHelper
     * @return WorksheetManager
     */
    private function createWorksheetManager(
        OptionsManagerInterface $optionsManager,
        StyleManager $styleManager,
        SharedStringsHelper $sharedStringsHelper
    )
    {
        $stringsEscaper = $this->createStringsEscaper();
        $stringsHelper = $this->createStringHelper();

        return new WorksheetManager($optionsManager, $styleManager, $sharedStringsHelper, $stringsEscaper, $stringsHelper);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return StyleManager
     */
    private function createStyleManager(OptionsManagerInterface $optionsManager)
    {
        $styleRegistry = $this->createStyleRegistry($optionsManager);
        return new StyleManager($styleRegistry);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return StyleRegistry
     */
    private function createStyleRegistry(OptionsManagerInterface $optionsManager)
    {
        $defaultRowStyle = $optionsManager->getOption(Options::DEFAULT_ROW_STYLE);
        return new StyleRegistry($defaultRowStyle);
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