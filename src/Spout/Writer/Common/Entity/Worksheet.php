<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Common\Exception\IOException;

/**
 * Class Worksheet
 * Entity describing a Worksheet
 */
class Worksheet
{
    /** @var string Path to the XML file that will contain the sheet data */
    private $filePath;

    /** @var resource Pointer to the sheet data file (e.g. xl/worksheets/sheet1.xml) */
    private $filePointer;

    /** @var Sheet The "external" sheet */
    private $externalSheet;

    /** @var int Maximum number of columns among all the written rows */
    private $maxNumColumns;

    /** @var int Index of the last written row */
    private $lastWrittenRowIndex;

    private $colWidths;

    private $defaultColWidth;

    private $defaultRowHeight;

    private $merges;

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
     * @param int $maxNumColumns
     */
    public function setMaxNumColumns($maxNumColumns)
    {
        $this->maxNumColumns = $maxNumColumns;
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

    /**
     * sets default column width --- Must be set before WorksheetManager->startSheet() is called on this sheet
     * @param string $col in letter format eg A or AC
     * @param float $width
     * @throws IOException
     */
    public function setColWidth(string $col, float $width)
    {
        $this->throwIfSheetFilePointerIsAlreadyCreated();
        $this->colWidths[$col] = $width;
    }

    /**
     * sets default column width --- Must be set before WorksheetManager->startSheet() is called on this sheet
     * @param float $width
     * @throws IOException
     */
    public function setDefaultColWidth(float $width)
    {
        $this->throwIfSheetFilePointerIsAlreadyCreated();
        $this->defaultColWidth = $width;
    }

    /**
     * sets default row height --- Must be set before WorksheetManager->startSheet() is called on this sheet
     * @param float $height
     * @throws IOException
     */
    public function setDefaultRowHeight(float $height)
    {
        $this->throwIfSheetFilePointerIsAlreadyCreated();
        $this->defaultRowHeight = $height;
    }

    /**
     * merge cells params should be letter and number cell reference eg A3, A5
     * @param string $leftCell
     * @param string $rightCell
     */
    public function mergeCells(string $leftCell, string $rightCell)
    {
        $this->merges[] = $leftCell . ':' . $rightCell;
    }

    /**
     * remove merged cell reference.
     * @param string $leftCell
     * @param string $rightCell
     */
    public function unMergeCells(string $leftCell, string $rightCell)
    {
        $this->merges = array_diff($this->merges,[$leftCell . ':' . $rightCell]);
    }

    /**
     * used by WorksheetManager to get default row height and width xml to inject into worksheet xml file
     * @return string
     */
    public function getDefaultXML() : string
    {
        if (empty($this->defaultColWidth) && empty($this->defaultRowHeight)) {
            return '';
        }
        return '<sheetFormatPr' .
            (empty($this->defaultColWidth) ? '' : ' defaultColWidth="'.$this->defaultColWidth.'"') .
            (empty($this->defaultRowHeight) ? '' : ' defaultRowHeight="'.$this->defaultRowHeight.'"') .
            '/>';
    }

    /**
     * used by WorksheetManager to get column width references xml to inject into worksheet xml file
     * @return string
     */
    public function getColWidthXML()
    {
        if (empty($colWidths)) {
            return '';
        }
        $xml = '<cols>';
        foreach ($this->colWidths as $col => $width) {
            $xml .= '<col min="'.$col.'" max="'.$col.'" width="'.$width.'" style="1" customWidth="1"/>'; //style and customWidth may be unnecessary ??
        }
        $xml .= '</cols>';
        return $xml;
    }

    /**
     * used by WorksheetManager to get merged cell references xml to inject into worksheet xml file
     * @return string
     */
    public function getMergeXML() : string
    {
        if (empty($this->merges)) {
            return '';
        }
        $xml = '<mergeCells count="'.count($this->merges).'">';
        foreach ($this->merges as $merge) {
            $xml .= '<mergeCell ref="'.$merge.'"/>';
        }
        $xml .= '</mergeCells>';
        return $xml;
    }

    /**
     * Checks if the sheet has already been started - throws exception
     *
     * @throws IOException If the sheet data file is already opened
     * @return void
     */
    private function throwIfSheetFilePointerIsAlreadyCreated()
    {
        if (!empty($this->filePointer)) {
            throw new IOException('Trying to add default or column width settings after sheet is created');
        }
    }
}
