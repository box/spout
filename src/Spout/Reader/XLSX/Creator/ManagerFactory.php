<?php

namespace Box\Spout\Reader\XLSX\Creator;

use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\XLSX\Manager\SharedStringsManager;
use Box\Spout\Reader\XLSX\Manager\SheetManager;
use Box\Spout\Reader\XLSX\Manager\StyleManager;

/**
 * Class ManagerFactory
 * Factory to create managers
 */
class ManagerFactory
{
    /** @var HelperFactory */
    private $helperFactory;

    /** @var CachingStrategyFactory */
    private $cachingStrategyFactory;

    /**
     * @param HelperFactory $helperFactory Factory to create helpers
     * @param CachingStrategyFactory $cachingStrategyFactory Factory to create shared strings caching strategies
     */
    public function __construct(HelperFactory $helperFactory, CachingStrategyFactory $cachingStrategyFactory)
    {
        $this->helperFactory = $helperFactory;
        $this->cachingStrategyFactory = $cachingStrategyFactory;
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string $tempFolder Temporary folder where the temporary files to store shared strings will be stored
     * @param EntityFactory $entityFactory Factory to create entities
     * @return SharedStringsManager
     */
    public function createSharedStringsManager($filePath, $tempFolder, $entityFactory)
    {
        return new SharedStringsManager($filePath, $tempFolder, $entityFactory, $this->helperFactory, $this->cachingStrategyFactory);
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface $optionsManager Reader's options manager
     * @param \Box\Spout\Reader\XLSX\Manager\SharedStringsManager $sharedStringsManager Manages shared strings
     * @param EntityFactory $entityFactory Factory to create entities
     * @return SheetManager
     */
    public function createSheetManager($filePath, $optionsManager, $sharedStringsManager, $entityFactory)
    {
        $escaper = $this->helperFactory->createStringsEscaper();

        return new SheetManager($filePath, $optionsManager, $sharedStringsManager, $escaper, $entityFactory);
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param EntityFactory $entityFactory Factory to create entities
     * @return StyleManager
     */
    public function createStyleManager($filePath, $entityFactory)
    {
        return new StyleManager($filePath, $entityFactory);
    }
}
