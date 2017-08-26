<?php

namespace Box\Spout\Reader\CSV\Creator;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Reader\Common\Creator\EntityFactoryInterface;
use Box\Spout\Reader\CSV\RowIterator;
use Box\Spout\Reader\CSV\Sheet;
use Box\Spout\Reader\CSV\SheetIterator;

/**
 * Class EntityFactory
 * Factory to create entities
 *
 * @package Box\Spout\Reader\CSV\Creator
 */
class EntityFactory implements EntityFactoryInterface
{
    /** @var HelperFactory */
    private $helperFactory;

    /**
     * @param HelperFactory $helperFactory
     */
    public function __construct(HelperFactory $helperFactory)
    {
        $this->helperFactory = $helperFactory;
    }

    /**
     * @param resource $filePointer Pointer to the CSV file to read
     * @param OptionsManagerInterface $optionsManager
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     * @return SheetIterator
     */
    public function createSheetIterator($filePointer, $optionsManager, $globalFunctionsHelper)
    {
        return new SheetIterator($filePointer, $optionsManager, $globalFunctionsHelper, $this);
    }

    /**
     * @param resource $filePointer Pointer to the CSV file to read
     * @param OptionsManagerInterface $optionsManager
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     * @return Sheet
     */
    public function createSheet($filePointer, $optionsManager, $globalFunctionsHelper)
    {
        return new Sheet($filePointer, $optionsManager, $globalFunctionsHelper, $this);
    }

    /**
     * @param resource $filePointer Pointer to the CSV file to read
     * @param OptionsManagerInterface $optionsManager
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     * @return RowIterator
     */
    public function createRowIterator($filePointer, $optionsManager, $globalFunctionsHelper)
    {
        $encodingHelper = $this->helperFactory->createEncodingHelper($globalFunctionsHelper);
        return new RowIterator($filePointer, $optionsManager, $encodingHelper, $globalFunctionsHelper);
    }
}
