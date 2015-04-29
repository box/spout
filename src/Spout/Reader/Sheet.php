<?php

namespace Box\Spout\Reader;

/**
 * Class Sheet
 * Represents a worksheet within a XLSX file
 *
 * @package Box\Spout\Reader
 */
class Sheet
{
    /** @var int ID of the sheet */
    protected $id;

    /** @var int Index of the sheet, based on order of creation (zero-based) */
    protected $index;

    /** @var string Name of the sheet */
    protected $name;

    /**
     * @param int $sheetId ID of the sheet
     * @param int $sheetIndex Index of the sheet, based on order of creation (zero-based)
     * @param string $sheetName Name of the sheet
     */
    function __construct($sheetId, $sheetIndex, $sheetName)
    {
        $this->id = $sheetId;
        $this->index = $sheetIndex;
        $this->name = $sheetName;
    }

    /**
     * @return int ID of the sheet
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int Index of the sheet, based on order of creation (zero-based)
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string Name of the sheet
     */
    public function getName()
    {
        return $this->name;
    }
}
