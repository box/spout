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

    /** @var int Number of the sheet, based on order of creation (zero-based) */
    protected $number;

    /** @var string Name of the sheet */
    protected $name;

    /**
     * @param int $sheetId ID of the sheet
     * @param int $sheetNumber Number of the sheet, based on order of creation (zero-based)
     * @param string $sheetName Name of the sheet
     */
    function __construct($sheetId, $sheetNumber, $sheetName)
    {
        $this->id = $sheetId;
        $this->number = $sheetNumber;
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
     * @return int Number of the sheet, based on order of creation (zero-based)
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string Name of the sheet
     */
    public function getName()
    {
        return $this->name;
    }
}
