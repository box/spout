<?php

namespace Box\Spout\Writer\ODS\Creator;

use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Creator\InternalFactoryInterface;
use Box\Spout\Writer\Common\Manager\StyleManager;
use Box\Spout\Writer\ODS\Helper\FileSystemHelper;
use Box\Spout\Writer\ODS\Helper\StyleHelper;
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

        $styleHelper = $this->createStyleHelper($optionsManager);
        $worksheetManager = $this->createWorksheetManager($styleHelper);

        return new WorkbookManager($workbook, $optionsManager, $worksheetManager, $styleHelper, $fileSystemHelper, $this->entityFactory);
    }

    /**
     * @param StyleHelper $styleHelper
     * @return WorksheetManager
     */
    private function createWorksheetManager(StyleHelper $styleHelper)
    {
        $stringsEscaper = $this->createStringsEscaper();
        $stringsHelper = $this->createStringHelper();

        return new WorksheetManager($styleHelper, $stringsEscaper, $stringsHelper);
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return FileSystemHelper
     */
    public function createFileSystemHelper(OptionsManagerInterface $optionsManager)
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
        $styleManager = $this->createStyleManager();

        return new StyleHelper($defaultRowStyle, $styleManager);
    }

    /**
     * @return StyleManager
     */
    private function createStyleManager()
    {
        return new StyleManager();
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