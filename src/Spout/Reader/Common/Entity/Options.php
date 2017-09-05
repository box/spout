<?php

namespace Box\Spout\Reader\Common\Entity;

/**
 * Class Options
 * Readers' options holder
 */
abstract class Options
{
    // Common options
    const SHOULD_FORMAT_DATES = 'shouldFormatDates';
    const SHOULD_PRESERVE_EMPTY_ROWS = 'shouldPreserveEmptyRows';

    // CSV specific options
    const FIELD_DELIMITER = 'fieldDelimiter';
    const FIELD_ENCLOSURE = 'fieldEnclosure';
    const ENCODING = 'encoding';

    // XLSX specific options
    const TEMP_FOLDER = 'tempFolder';
}
