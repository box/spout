<?php

namespace Box\Spout\Writer\Common\Entity;

/**
 * Class Worksheet
 * Entity describing a Worksheet
 */
class Worksheet
{
    /** @var string Path to the XML file that will contain the sheet data */
    private $filePath;

    /** @var resource|null Pointer to the sheet data file (e.g. xl/worksheets/sheet1.xml) */
    private $filePointer;

    /** @var Sheet The "external" sheet */
    private $externalSheet;

    /** @var int Maximum number of columns among all the written rows */
    private $maxNumColumns;

    /** @var int Index of the last written row */
    private $lastWrittenRowIndex;
    
    /** @var array Array of the column widths */
    protected $columnWidths;
    
    /** @var int Width calculation style */
    protected $widthCalcuationStyle;
    
    /** @var int Fixed sheet width for fixed width calculation style */
    protected $fixedSheetWidth;

    public const W_FULL = 1;
    public const W_FIXED = 2;
    public const W_NONE = 0;
    public const DEFAULT_COL_WIDTH = 30;
    public const DEFAULT_FIXED_WIDTH = 1068;

    /**
     * Worksheet constructor.
     *
     * @param string $worksheetFilePath
     * @param Sheet $externalSheet
     */
    public function __construct($worksheetFilePath, Sheet $externalSheet)
    {
        $this->filePath = $worksheetFilePath;
        $this->filePointer = null;
        $this->externalSheet = $externalSheet;
        $this->maxNumColumns = 0;
        $this->lastWrittenRowIndex = 0;
        $this->columnWidths = [];
        $this->widthCalcuationStyle = 0;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return resource
     */
    public function getFilePointer()
    {
        return $this->filePointer;
    }

    /**
     * @param resource $filePointer
     */
    public function setFilePointer($filePointer)
    {
        $this->filePointer = $filePointer;
    }

    /**
     * @return Sheet
     */
    public function getExternalSheet()
    {
        return $this->externalSheet;
    }

    /**
     * @return int
     */
    public function getMaxNumColumns()
    {
        return $this->maxNumColumns;
    }

    /**
     * @return array
     */
    public function getColumnWidths()
    {
        return $this->columnWidths;
    }

    /**
     * Gets the calculated max column width for the specified index
     * @param int $zeroBasedIndex
     * @return int
     */
    public function getMaxColumnWidth($zeroBasedIndex)
    {
        if (isset($this->columnWidths[$zeroBasedIndex])) {
            return $this->columnWidths[$zeroBasedIndex];
        }

        $this->columnWidths[$zeroBasedIndex] = self::DEFAULT_COL_WIDTH;
        return $this->columnWidths[$zeroBasedIndex];
    }

    /**
     * Sets the calculated max column width for the specified index
     * @param int $zeroBasedIndex
     * @param int $value Value to set to
     * @return void
     */
    public function setMaxColumnWidth($zeroBasedIndex, $value)
    {
        $curSize = $this->columnWidths[$zeroBasedIndex] ?? 0;
        if ($curSize < $value) {
            $this->columnWidths[$zeroBasedIndex] = $value;
        }
    }

    /**
     * Automatically calculates and sets the max column width for the specified cell
     * @param Cell $cell The cell
     * @param Style $style Row/Cell style
     * @param int $zeroBasedIndex of cell
     * @return void
     */
    public function autoSetWidth($cell, $style, $zeroBasedIndex)
    {
        $size = 1 + strlen($cell->getValue());//ensure we have at least 1 space
        $size *= $style->isFontBold() ? 1.2 : 1.0;
        if ($this->getWidthCalculation() == Worksheet::W_FIXED) {
            $total = array_sum($this->getColumnWidths());
            $total = $total ?: $size;
            $size = ($size / $total) * $this->getFixedSheetWidth();
        }
        $this->setMaxColumnWidth($zeroBasedIndex, $size);
    }

    /**
     * Gets the fixed sheet width or returns the default if not available
     * @return int
     */
    public function getFixedSheetWidth()
    {
        if (!$this->fixedSheetWidth) {
            return Worksheet::DEFAULT_FIXED_WIDTH;
        }
        return $this->fixedSheetWidth;
    }

    /**
     * Sets the fixed sheet width
     * @param int $width
     * @return void
     */
    public function setFixedSheetWidth($width)
    {
        $this->fixedSheetWidth = $width;
    }

    /**
     * @param int $maxNumColumns
     */
    public function setMaxNumColumns($maxNumColumns)
    {
        $this->maxNumColumns = $maxNumColumns;
    }

    /**
     * Set the with calculation style for this sheet.
     * 1=FullExpand,2=FixedWidth,0=None
     *
     * @return Worksheet Enable method chaining for easy set width
     */
    public function setWidthCalculation($widthStyle)
    {
        $this->widthCalcuationStyle = $widthStyle;
        return $this;
    }

    /**
     * Get the with calculation style for this sheet.
     * 1=FullExpand,2=FixedWidth,0=None
     *
     * @return void
     */
    public function getWidthCalculation()
    {
        return $this->widthCalcuationStyle;
    }

    /**
     * @return int
     */
    public function getLastWrittenRowIndex()
    {
        return $this->lastWrittenRowIndex;
    }

    /**
     * @param int $lastWrittenRowIndex
     */
    public function setLastWrittenRowIndex($lastWrittenRowIndex)
    {
        $this->lastWrittenRowIndex = $lastWrittenRowIndex;
    }

    /**
     * @return int The ID of the worksheet
     */
    public function getId()
    {
        // sheet index is zero-based, while ID is 1-based
        return $this->externalSheet->getIndex() + 1;
    }
}
