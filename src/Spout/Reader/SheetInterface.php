<?php

namespace Box\Spout\Reader;

/**
 * Interface SheetInterface
 */
interface SheetInterface
{
    /**
     * Returns an iterator to iterate over the sheet's rows.
     *
     * @return IteratorInterface
     */
    public function getRowIterator();
}
