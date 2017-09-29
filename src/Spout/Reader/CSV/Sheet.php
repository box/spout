<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Reader\SheetInterface;

/**
 * Class Sheet
 */
class Sheet implements SheetInterface
{
    /** @var \Box\Spout\Reader\CSV\RowIterator To iterate over the CSV's rows */
    protected $rowIterator;

    /**
     * @param RowIterator $rowIterator Corresponding row iterator
     */
    public function __construct(RowIterator $rowIterator)
    {
        $this->rowIterator = $rowIterator;
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
