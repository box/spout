<?php

namespace Box\Spout\Writer\Common\Helper;

/**
 * Interface StyleHelperInterface
 *
 * @package Box\Spout\Writer\Common\Helper
 */
interface StyleHelperInterface
{
    /**
     * Registers the given style as a used style.
     * Duplicate styles won't be registered more than once.
     *
     * @param \Box\Spout\Writer\Common\Entity\Style\Style $style The style to be registered
     * @return \Box\Spout\Writer\Common\Entity\Style\Style The registered style, updated with an internal ID.
     */
    public function registerStyle($style);

    /**
     * Apply additional styles if the given row needs it.
     * Typically, set "wrap text" if a cell contains a new line.
     *
     * @param \Box\Spout\Writer\Common\Entity\Style\Style $style The original style
     * @param array $dataRow The row the style will be applied to
     * @return \Box\Spout\Writer\Common\Entity\Style\Style The updated style
     */
    public function applyExtraStylesIfNeeded($style, $dataRow);
}
