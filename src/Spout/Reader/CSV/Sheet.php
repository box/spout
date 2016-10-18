<?php

namespace Box\Spout\Reader\CSV;

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
     * @param \Box\Spout\Reader\CSV\ReaderOptions $options
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct($filePointer, $options, $globalFunctionsHelper)
    {
        $this->rowIterator = new RowIterator($filePointer, $options, $globalFunctionsHelper);
    }

    /**
     * @api
     * @return \Box\Spout\Reader\CSV\RowIterator
     */
    public function getRowIterator()
    {
        return $this->rowIterator;
    }
}
