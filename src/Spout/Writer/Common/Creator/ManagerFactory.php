<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Manager\CellManager;
use Box\Spout\Writer\Common\Manager\RowManager;
use Box\Spout\Writer\Common\Manager\SheetManager;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class ManagerFactory
 * Factory to create managers
 *
 * @package Box\Spout\Writer\Common\Creator
 */
class ManagerFactory
{
    /**
     * @return CellManager
     */
    public function createCellManager()
    {
        $styleMerger = new StyleMerger();
        return new CellManager($styleMerger);
    }

    /**
     * @return RowManager
     */
    public function createRowManager()
    {
        $styleMerger = new StyleMerger();
        return new RowManager($styleMerger);
    }

    /**
     * @return SheetManager
     */
    public function createSheetManager()
    {
        $stringHelper = new StringHelper();
        return new SheetManager($stringHelper);
    }
}