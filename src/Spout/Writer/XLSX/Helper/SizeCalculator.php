<?php

namespace Box\Spout\Writer\XLSX\Helper;

class SizeCalculator
{
    /** @var SizeCollection */
    private $sizeCollection;

    /** @var array */
    private $characterSizes;

    /**
     * SizeCalculator constructor.
     *
     * @param SizeCollection $sizeCollection
     */
    public function __construct(SizeCollection $sizeCollection)
    {
        $this->sizeCollection = $sizeCollection;
    }

    /**
     * Return the estimated width of a cell value.
     *
     * @param mixed $value
     * @param int   $fontSize
     * @return float
     */
    public function getCellWidth($value, $fontSize)
    {
        $width = 1;
        foreach ($this->getSingleCharacterArray($value) as $character) {
            if (isset($this->characterSizes[$character])) {
                $width += $this->characterSizes[$character];
            } elseif (strlen($character)) {
                $width += 0.1 * $fontSize;
            }
        }

        return $width;
    }

    /**
     * Set proper font sizes by font.
     *
     * @param string $fontName
     * @param string $fontSize
     */
    public function setFont($fontName, $fontSize)
    {
        $this->characterSizes = $this->sizeCollection->get($fontName, $fontSize);
    }

    /**
     * Split value into individual characters.
     *
     * @param mixed $value
     * @return array
     */
    private function getSingleCharacterArray($value)
    {
        if (mb_strlen($value) == strlen($value)) {
            return str_split($value);
        }

        return preg_split('~~u', $value, -1, PREG_SPLIT_NO_EMPTY);
    }
}