<?php

namespace Box\Spout\Reader\XLSX\Creator;

use Box\Spout\Reader\XLSX\Helper\CellValueFormatter;
use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\XLSX\Helper\SharedStringsHelper;
use Box\Spout\Reader\XLSX\Helper\SheetHelper;
use Box\Spout\Reader\XLSX\Helper\StyleHelper;


/**
 * Class EntityFactory
 * Factory to create helpers
 *
 * @package Box\Spout\Reader\XLSX\Creator
 */
class HelperFactory extends \Box\Spout\Common\Creator\HelperFactory
{
    /** @var CachingStrategyFactory */
    private $cachingStrategyFactory;

    /**
     * @param CachingStrategyFactory $cachingStrategyFactory Factory to create shared strings caching strategies
     */
    public function __construct(CachingStrategyFactory $cachingStrategyFactory)
    {
        $this->cachingStrategyFactory = $cachingStrategyFactory;
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string $tempFolder Temporary folder where the temporary files to store shared strings will be stored
     * @param EntityFactory $entityFactory Factory to create entities
     * @return SharedStringsHelper
     */
    public function createSharedStringsHelper($filePath, $tempFolder, $entityFactory)
    {
        return new SharedStringsHelper($filePath, $tempFolder, $entityFactory, $this, $this->cachingStrategyFactory);
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface $optionsManager Reader's options manager
     * @param \Box\Spout\Reader\XLSX\Helper\SharedStringsHelper Helper to work with shared strings
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     * @param EntityFactory $entityFactory Factory to create entities
     * @return SheetHelper
     */
    public function createSheetHelper($filePath, $optionsManager, $sharedStringsHelper, $globalFunctionsHelper, $entityFactory)
    {
        return new SheetHelper($filePath, $optionsManager, $sharedStringsHelper, $globalFunctionsHelper, $entityFactory);
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param EntityFactory $entityFactory Factory to create entities
     * @return StyleHelper
     */
    public function createStyleHelper($filePath, $entityFactory)
    {
        return new StyleHelper($filePath, $entityFactory);
    }

    /**
     * @param SharedStringsHelper $sharedStringsHelper Helper to work with shared strings
     * @param StyleHelper $styleHelper Helper to work with styles
     * @param bool $shouldFormatDates Whether date/time values should be returned as PHP objects or be formatted as strings
     * @return CellValueFormatter
     */
    public function createCellValueFormatter($sharedStringsHelper, $styleHelper, $shouldFormatDates)
    {
        return new CellValueFormatter($sharedStringsHelper, $styleHelper, $shouldFormatDates);
    }
}
