<?php

namespace Box\Spout\Writer\Common\Entity;

/**
 * Class Options
 * Writers' options holder
 */
abstract class Options
{
    // CSV specific options
    public const FIELD_DELIMITER = 'fieldDelimiter';
    public const FIELD_ENCLOSURE = 'fieldEnclosure';
    public const SHOULD_ADD_BOM = 'shouldAddBOM';

    // Multisheets options
    public const TEMP_FOLDER = 'tempFolder';
    public const DEFAULT_ROW_STYLE = 'defaultRowStyle';
    public const SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = 'shouldCreateNewSheetsAutomatically';

    // XLSX specific options
    public const SHOULD_USE_INLINE_STRINGS = 'shouldUseInlineStrings';
}
