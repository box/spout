<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Manager\WorkbookManagerInterface;

/**
 * Interface InternalFactoryInterface
 *
 * @package Box\Spout\Writer\Common\Creator
 */
interface InternalFactoryInterface
{
    /**
     * @param OptionsManagerInterface $optionsManager
     * @return WorkbookManagerInterface
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager);
}