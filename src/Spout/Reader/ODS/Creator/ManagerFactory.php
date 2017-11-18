<?php

namespace Box\Spout\Reader\ODS\Creator;

use Box\Spout\Reader\ODS\Manager\RowManager;

/**
 * Class ManagerFactory
 * Factory to create managers
 */
class ManagerFactory
{
    /**
     * @return RowManager
     */
    public function createRowManager()
    {
        return new RowManager();
    }
}
