<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Manager\SheetManager;

/**
 * Class ManagerFactory
 * Factory to create managers
 *
 * @package Box\Spout\Writer\Common\Creator
 */
class ManagerFactory
{
    /**
     * @return SheetManager
     */
    public function createSheetManager()
    {
        $stringHelper = new StringHelper();
        return new SheetManager($stringHelper);
    }
}