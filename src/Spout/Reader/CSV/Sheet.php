<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Reader\CSV\Creator\EntityFactory;
use Box\Spout\Reader\SheetInterface;

/**
 * Class Sheet
 *
 * @package Box\Spout\Reader\CSV
 */
class Sheet implements SheetInterface
{
    /** @var \Box\Spout\Reader\CSV\RowIterator To iterate over the CSV's rows */
    protected $rowIterator;

    /**
     * @param resource $filePointer Pointer to the CSV file to read
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface $optionsManager
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     * @param EntityFactory $entityFactory Factory to create entities
     */
    public function __construct($filePointer, $optionsManager, $globalFunctionsHelper, $entityFactory)
    {
        $this->rowIterator = $entityFactory->createRowIterator($filePointer, $optionsManager, $globalFunctionsHelper);
    }

    /**
     * @api
     * @return \Box\Spout\Reader\CSV\RowIterator
     */
    public function getRowIterator()
    {
        return $this->rowIterator;
    }

    /**
     * @api
     * @return int Index of the sheet
     */
    public function getIndex()
    {
        return 0;
    }

    /**
     * @api
     * @return string Name of the sheet - empty string since CSV does not support that
     */
    public function getName()
    {
        return '';
    }

    /**
     * @api
     * @return bool Always TRUE as there is only one sheet
     */
    public function isActive()
    {
        return true;
    }
}
