<?php

namespace Box\Spout\Writer\Creator;

use Box\Spout\Writer\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Manager\WorkbookManagerInterface;

/**
 * Interface GeneralFactoryInterface
 *
 * @package Box\Spout\Writer\Creator
 */
interface InternalFactoryInterface
{
    /**
     * @param OptionsManagerInterface $optionsManager
     * @return WorkbookManagerInterface
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager);
}