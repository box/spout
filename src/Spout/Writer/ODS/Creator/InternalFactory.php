<?php

namespace Box\Spout\Writer\ODS\Creator;

use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Helper\ZipHelper;
use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Creator\InternalFactoryInterface;
use Box\Spout\Writer\ODS\Helper\FileSystemHelper;
use Box\Spout\Writer\ODS\Manager\Style\StyleManager;
use Box\Spout\Writer\ODS\Manager\Style\StyleRegistry;
use Box\Spout\Writer\ODS\Manager\WorkbookManager;
use Box\Spout\Writer\ODS\Manager\WorksheetManager;
use \Box\Spout\Common\Escaper;

/**
 * Class InternalFactory
 * Factory for all useful types of objects needed by the ODS Writer
 *
 * @package Box\Spout\Writer\ODS\Creator
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

        $styleManager = $this->createStyleManager($optionsManager);
        $worksheetManager = $this->createWorksheetManager($styleManager);

        return new WorkbookManager($workbook, $optionsManager, $worksheetManager, $styleManager, $fileSystemHelper, $this->entityFactory);
    }

    /**
     * @param StyleManager $styleManager
     * @return WorksheetManager
     */
    private function createWorksheetManager(StyleManager $styleManager)
    {
        $stringsEscaper = $this->createStringsEscaper();
        $stringsHelper = $this->createStringHelper();


        return new WorksheetManager($styleManager, $stringsEscaper, $stringsHelper);
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
     * @param OptionsManagerInterface $optionsManager
     * @return FileSystemHelper
     */
    public function createFileSystemHelper(OptionsManagerInterface $optionsManager)
    {
        $tempFolder = $optionsManager->getOption(Options::TEMP_FOLDER);
        $zipHelper = $this->createZipHelper();

        return new FileSystemHelper($tempFolder, $zipHelper);
    }

    /**
     * @return ZipHelper
     */
    private function createZipHelper()
    {
        return new ZipHelper();
    }

    /**
     * @return Escaper\ODS
     */
    private function createStringsEscaper()
    {
        return Escaper\ODS::getInstance();
    }

    /**
     * @return StringHelper
     */
    private function createStringHelper()
    {
        return new StringHelper();
    }
}