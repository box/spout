<?php

namespace Box\Spout\Writer\Common\Entity;

/**
 * Class Options
 * Writers' options holder
 */
abstract class Options
{
    // CSV specific options
    const FIELD_DELIMITER = 'fieldDelimiter';
    const FIELD_ENCLOSURE = 'fieldEnclosure';
    const SHOULD_ADD_BOM = 'shouldAddBOM';

    // Multisheets options
    const TEMP_FOLDER = 'tempFolder';
    const DEFAULT_ROW_STYLE = 'defaultRowStyle';
    const SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = 'shouldCreateNewSheetsAutomatically';

    // XLSX specific options
    const SHOULD_USE_INLINE_STRINGS = 'shouldUseInlineStrings';

    // Cell size options
    const DEFAULT_COLUMN_WIDTH = 'defaultColumnWidth';
    const DEFAULT_ROW_HEIGHT = 'defaultRowHeight';
    const COLUMN_WIDTHS = 'columnWidthDefinition';
}
