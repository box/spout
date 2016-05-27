<?php

namespace Box\Spout\Reader;

/**
 * Class ReaderOptions
 * This helper class is used to hold common reader options.
 *
 * @package Box\Spout\Reader
 */
class ReaderOptions
{

    /** @var bool Whether date/time values should be returned as PHP objects or be formatted as strings */
    protected $shouldFormatDates = false;

    /** @var bool Whether to skip "empty" rows. The exact definition of empty may depend on the reader implementation. */
    protected $shouldPreserveEmptyRows = false;

    /**
     * Sets whether date/time values should be returned as PHP objects or be formatted as strings.
     *
     * @param bool $shouldFormatDates
     * @return ReaderOptions
     */
    public function setShouldFormatDates($shouldFormatDates)
    {
        $this->shouldFormatDates = (bool)$shouldFormatDates;
        return $this;
    }

    /**
     * Sets whether to skip or return "empty" rows.
     *
     * @param bool $shouldPreserveEmptyRows
     * @return ReaderOptions
     */
    public function setShouldPreserveEmptyRows($shouldPreserveEmptyRows)
    {
        $this->shouldPreserveEmptyRows = (bool)$shouldPreserveEmptyRows;
        return $this;
    }

    /**
     * @see setShouldFormatDates
     * @return bool
     */
    public function shouldFormatDates()
    {
        return $this->shouldFormatDates;
    }

    /**
     * @see setShouldPreserveEmptyRows
     * @return bool
     */
    public function shouldPreserveEmptyRows()
    {
        return $this->shouldPreserveEmptyRows;
    }

}
