<?php

namespace Box\Spout\Writer\Factory;

use Box\Spout\Writer\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Manager\WorkbookManagerInterface;

/**
 * Interface GeneralFactoryInterface
 *
 * @package Box\Spout\Writer\Factory
 */
interface InternalFactoryInterface
{
    /**
     * @param OptionsManagerInterface $optionsManager
     * @return WorkbookManagerInterface
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager);
}