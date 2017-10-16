<?php

namespace Box\Spout\Writer\ODS\Creator;

use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Creator\ManagerFactoryInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Manager\SheetManager;
use Box\Spout\Writer\ODS\Manager\Style\StyleManager;
use Box\Spout\Writer\ODS\Manager\Style\StyleRegistry;
use Box\Spout\Writer\ODS\Manager\WorkbookManager;
use Box\Spout\Writer\ODS\Manager\WorksheetManager;

/**
 * Class ManagerFactory
 * Factory for managers needed by the ODS Writer
 */
class ManagerFactory implements ManagerFactoryInterface
{
    /** @var EntityFactory */
    protected $entityFactory;

    /** @var HelperFactory $helperFactory */
    protected $helperFactory;

    /**
     * @param EntityFactory $entityFactory
     * @param HelperFactory $helperFactory
     */
    public function __construct(EntityFactory $entityFactory, HelperFactory $helperFactory)
    {
        $this->entityFactory = $entityFactory;
        $this->helperFactory = $helperFactory;
    }

    /**
     * @param OptionsManagerInterface $optionsManager
     * @return WorkbookManager
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager)
    {
        $workbook = $this->entityFactory->createWorkbook();

        $fileSystemHelper = $this->helperFactory->createSpecificFileSystemHelper($optionsManager, $this->entityFactory);
        $fileSystemHelper->createBaseFilesAndFolders();

        $styleManager = $this->createStyleManager($optionsManager);
        $worksheetManager = $this->createWorksheetManager($styleManager);

        return new WorkbookManager(
            $workbook,
            $optionsManager,
            $worksheetManager,
            $styleManager,
            $fileSystemHelper,
            $this->entityFactory,
            $this
        );
    }

    /**
     * @param StyleManager $styleManager
     * @return WorksheetManager
     */
    private function createWorksheetManager(StyleManager $styleManager)
    {
        $stringsEscaper = $this->helperFactory->createStringsEscaper();
        $stringsHelper = $this->helperFactory->createStringHelper();

        return new WorksheetManager($styleManager, $stringsEscaper, $stringsHelper);

        return new WorksheetManager($stringsEscaper, $stringsHelper, $this->entityFactory);
    }

    /**
     * @return SheetManager
     */
    public function createSheetManager()
    {
        $stringHelper = $this->helperFactory->createStringHelper();

        return new SheetManager($stringHelper);
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
}
