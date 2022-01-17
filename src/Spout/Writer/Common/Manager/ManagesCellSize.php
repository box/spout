<?php

namespace Box\Spout\Writer\Common\Manager;

trait ManagesCellSize
{
    /** @var float|null The default column width to use */
    private $defaultColumnWidth;

    /** @var float|null The default row height to use */
    private $defaultRowHeight;

    /** @var array Array of min-max-width arrays */
    private $columnWidths = [];

    /**
     * @param float|null $width
     */
    public function setDefaultColumnWidth($width)
    {
        $this->defaultColumnWidth = $width;
    }

    /**
     * @param float|null $height
     */
    public function setDefaultRowHeight($height)
    {
        $this->defaultRowHeight = $height;
    }

    /**
     * @param float $width
     * @param array $columns One or more columns with this width
     */
    public function setColumnWidth(float $width, ...$columns)
    {
        // Gather sequences
        $sequence = [];
        foreach ($columns as $i) {
            $sequenceLength = count($sequence);
            if ($sequenceLength > 0) {
                $previousValue = $sequence[$sequenceLength - 1];
                if ($i !== $previousValue + 1) {
                    $this->setColumnWidthForRange($width, $sequence[0], $previousValue);
                    $sequence = [];
                }
            }
            $sequence[] = $i;
        }
        $this->setColumnWidthForRange($width, $sequence[0], $sequence[count($sequence) - 1]);
    }

    /**
     * @param float $width The width to set
     * @param int $start First column index of the range
     * @param int $end Last column index of the range
     */
    public function setColumnWidthForRange(float $width, int $start, int $end)
    {
        $this->columnWidths[] = [$start, $end, $width];
    }
}
